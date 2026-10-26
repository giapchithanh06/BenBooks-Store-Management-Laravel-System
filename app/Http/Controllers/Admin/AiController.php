<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshAiData;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\QueueLog;
use App\Models\Review;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\CustomerSegmentationService;
use App\Services\ForecastService;
use App\Services\FraudDetectionService;
use App\Services\SemanticSearchService;
use App\Services\SentimentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Nhóm chức năng nâng cao (cột C): phân tích hành vi, phân cụm, dự báo, phát hiện bất thường,
 * cảm xúc đánh giá, knowledge graph, lịch sử chatbot, thử nghiệm semantic search, hàng đợi.
 */
class AiController extends Controller
{
    public function behavior(Request $request, AnalyticsService $analytics)
    {
        $days = (int) $request->input('days', 30);
        return view('admin.ai.behavior', [
            'days'       => $days,
            'funnel'     => $analytics->funnel($days),
            'keywords'   => $analytics->topKeywords($days),
            'viewedNotBought' => $analytics->viewedNotBought($days),
            'hourly'     => $analytics->hourly($days),
            'categories' => $analytics->categoryInterest($days),
            'actions'    => $analytics->actionCounts($days),
        ]);
    }

    public function segments(Request $request, CustomerSegmentationService $seg)
    {
        $result = $seg->run();
        $users = User::whereIn('id', array_keys($result['rfm']))->get()->keyBy('id');
        $points = [];
        foreach ($result['rfm'] as $id => $r) {
            if ($u = $users->get($id)) {
                $points[] = ['name' => $u->name, 'segment' => $u->segment, 'x' => $r['frequency'], 'y' => round($r['monetary'] / 1000), 'r' => $r['recency']];
            }
        }
        return view('admin.ai.segments', [
            'stats'  => $result['stats'],
            'points' => $points,
            'rows'   => collect($result['rfm'])->map(function ($r, $id) use ($users) {
                return $r + ['user' => $users->get($id)];
            })->filter(function ($r) { return $r['user']; })->sortByDesc('monetary')->values(),
            'noOrder' => User::where('role', 'customer')->where('segment', CustomerSegmentationService::NO_ORDER)->count(),
        ]);
    }

    public function forecast(ForecastService $forecast)
    {
        return view('admin.ai.forecast', [
            'revenue' => $forecast->revenue(12, 3),
            'demand'  => $forecast->productDemand(8, 4),
        ]);
    }

    public function anomalies(Request $request)
    {
        $orders = Order::with('user')->where('fraud_score', '>', 0)
            ->when($request->input('min', 0), function ($q, $m) { $q->where('fraud_score', '>=', $m); })
            ->orderByDesc('fraud_score')->paginate(20)->withQueryString();
        $dist = [
            'Thấp (1-29)' => Order::whereBetween('fraud_score', [1, 29])->count(),
            'Trung bình (30-49)' => Order::whereBetween('fraud_score', [30, 49])->count(),
            'Cao (≥50)' => Order::where('fraud_score', '>=', 50)->count(),
        ];
        return view('admin.ai.anomalies', compact('orders', 'dist'));
    }

    public function scanAnomalies(FraudDetectionService $fraud)
    {
        $n = $fraud->scanAll();
        return back()->with('success', "Đã quét lại {$n} đơn hàng.");
    }

    public function sentiment(Request $request, SentimentService $svc)
    {
        if ($request->boolean('reanalyze')) {
            $svc->analyzeAll();
        }
        $counts = Review::select('sentiment', DB::raw('COUNT(*) as c'))->groupBy('sentiment')->pluck('c', 'sentiment');
        $aspects = [];
        foreach (Review::whereNotNull('aspects')->get(['aspects']) as $r) {
            foreach ((array) $r->aspects as $key => $a) {
                if (!isset($aspects[$key])) $aspects[$key] = ['label' => $a['label'], 'positive' => 0, 'neutral' => 0, 'negative' => 0];
                $aspects[$key][$a['sentiment']]++;
            }
        }
        $byBook = Review::join('books', 'books.id', '=', 'reviews.book_id')
            ->select('books.title', DB::raw('COUNT(*) as total'), DB::raw('AVG(reviews.sentiment_score) as score'),
                     DB::raw("SUM(CASE WHEN reviews.sentiment = 'negative' THEN 1 ELSE 0 END) as negatives"))
            ->groupBy('books.title')->orderBy('score')->take(15)->get();

        $test = null;
        if ($text = $request->input('text')) {
            $test = ['text' => $text, 'score' => round($svc->scoreText($text), 3), 'aspects' => $svc->aspects($text)];
        }
        return view('admin.ai.sentiment', [
            'counts' => $counts, 'aspects' => $aspects, 'byBook' => $byBook, 'test' => $test,
            'latest' => Review::with(['book', 'user'])->latest()->take(15)->get(),
        ]);
    }

    public function graph(Request $request, AnalyticsService $analytics)
    {
        return view('admin.ai.graph', [
            'graph'      => $analytics->knowledgeGraph($request->input('category_id')),
            'facts'      => $analytics->graphFacts(),
            'summary'    => $analytics->graphSummary(),
            'categories' => Category::whereNull('parent_id')->with('children')->get(),
        ]);
    }

    public function chatLogs()
    {
        $messages = ChatMessage::with('user')->latest()->paginate(30);
        $intents = ChatMessage::select('intent', DB::raw('COUNT(*) as c'))->groupBy('intent')->pluck('c', 'intent');
        return view('admin.ai.chatbot', compact('messages', 'intents'));
    }

    public function searchLab(Request $request, SemanticSearchService $search)
    {
        if ($request->boolean('rebuild')) {
            $n = $search->rebuildIndex();
            return redirect()->route('admin.ai.search')->with('success', "Đã xây lại vector index cho {$n} đầu sách.");
        }
        $q = (string) $request->input('q');
        $result = $q !== '' ? $search->search($q, 15) : null;
        return view('admin.ai.search', [
            'q' => $q, 'result' => $result,
            'understood' => $result ? $search->explain($result['filters']) : [],
            'tokens' => $q !== '' ? \App\Services\TextAnalyzer::tokenize($q) : [],
            'expanded' => $q !== '' ? \App\Services\TextAnalyzer::expand($q) : [],
            'indexSize' => \App\Models\BookVector::count(),
        ]);
    }

    /* ----------------------------------------------- Message Queue */

    public function queue()
    {
        return view('admin.ai.queue', [
            'pending' => DB::table('jobs')->orderBy('id')->get()->map(function ($j) {
                $payload = json_decode($j->payload, true);
                $j->name = $payload['displayName'] ?? '?';
                return $j;
            }),
            'failed' => DB::table('failed_jobs')->latest('failed_at')->take(20)->get(),
            'logs'   => QueueLog::latest()->take(30)->get(),
        ]);
    }

    public function queueDispatch(Request $request)
    {
        $task = $request->input('task', 'all');
        RefreshAiData::dispatch($task)->onConnection('database');
        return back()->with('success', 'Đã đưa job "' . $task . '" vào hàng đợi.');
    }

    /** Chạy worker xử lý hết job đang chờ (thay cho việc mở terminal chạy queue:work) */
    public function queueWork()
    {
        // Dùng --once lặp lại (an toàn trong request web, worker không gọi exit())
        $done = 0;
        while ($done < 50 && DB::table('jobs')->whereNull('reserved_at')->where('available_at', '<=', time())->exists()) {
            Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 3]);
            $done++;
        }
        return back()->with('success', "Đã xử lý {$done} job trong hàng đợi.");
    }
}
