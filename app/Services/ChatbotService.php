<?php

namespace App\Services;

use App\Models\Book;
use App\Models\FlashSale;
use App\Models\KnowledgeDoc;
use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Chatbot tư vấn sản phẩm + RAG hỏi đáp chính sách.
 *
 * - Nhận diện ý định (intent) bằng từ khóa: chào hỏi, tra cứu đơn, voucher, flash sale, chính sách, hỏi về 1 cuốn sách,
 *   tư vấn/tìm sách.
 * - RAG (Retrieval-Augmented Generation): truy xuất các câu liên quan nhất trong kho tri thức (knowledge_docs) bằng
 *   TF-IDF + cosine, rồi sinh câu trả lời từ ngữ cảnh đã truy xuất.
 * - Mặc định chạy OFFLINE (trả lời trích xuất). Nếu cấu hình LLM_API_KEY trong .env thì ngữ cảnh RAG được gửi cho
 *   mô hình ngôn ngữ (Anthropic Messages API) để diễn đạt tự nhiên hơn; lỗi mạng sẽ tự quay về chế độ offline.
 */
class ChatbotService
{
    protected $search;
    protected $reco;

    public function __construct(SemanticSearchService $search, RecommendationService $reco)
    {
        $this->search = $search;
        $this->reco = $reco;
    }

    protected function has($q, array $words)
    {
        foreach ($words as $w) {
            if (strpos(' ' . $q . ' ', ' ' . $w . ' ') !== false) return true;
        }
        return false;
    }

    /**
     * @return array ['answer' => html-safe text, 'books' => [...], 'intent' => string, 'sources' => [...]]
     */
    public function reply($message, $user = null)
    {
        $q = TextAnalyzer::normalize($message);

        if ($q === '' || $this->has($q, ['xin chao', 'chao', 'hello', 'hi', 'alo', 'chao shop'])) {
            if (mb_strlen($q) < 20) {
                return $this->out('greeting', "Xin chào" . ($user ? ' ' . $user->name : '') . "! Mình là trợ lý BenBooks 📚. Bạn có thể hỏi:\n• \"Gợi ý truyện tranh hành động dưới 50k\"\n• \"Sách Đắc Nhân Tâm giá bao nhiêu?\"\n• \"Chính sách đổi trả thế nào?\"\n• \"Đơn hàng của tôi đang ở đâu?\"");
            }
        }

        // 1. Tra cứu đơn hàng
        if ($this->has($q, ['don cua toi', 'don hang cua toi', 'don toi', 'kiem tra don', 'tra cuu don', 'giao toi dau', 'don hang dang o dau', 'don dang o dau', 'tinh trang don'])
            || preg_match('/\bms\d{6}[a-z0-9]{5}\b/', $q)) {
            return $this->orderAnswer($q, $user);
        }

        // 2. Voucher / khuyến mãi
        if ($this->has($q, ['voucher', 'ma giam gia', 'ma giam', 'coupon', 'khuyen mai', 'giam gia', 'flash sale', 'sale'])) {
            return $this->promoAnswer();
        }

        // 3. Hỏi về một cuốn sách cụ thể (giá, còn hàng, tác giả)
        if ($book = $this->findMentionedBook($q)) {
            if ($this->has($q, ['gia', 'bao nhieu', 'con hang', 'con khong', 'tac gia', 'ai viet', 'nxb', 'xuat ban', 'thong tin', 'noi dung', 'review', 'hay khong', 'co hay'])) {
                return $this->bookAnswer($book, $q);
            }
        }

        // 4. Chính sách / FAQ -> RAG
        $policyWords = ['doi tra', 'tra hang', 'hoan tien', 'bao hanh', 'giao hang', 'van chuyen', 'phi ship', 'ship', 'thanh toan',
                        'cod', 'chuyen khoan', 'bao mat', 'tai khoan', 'dang ky', 'mat khau', 'lien he', 'hotline', 'dia chi cua hang',
                        'chinh sach', 'bao lau', 'mien phi', 'huy don', 'tich diem', 'gio mo cua', 'the thanh vien'];
        if ($this->has($q, $policyWords)) {
            $rag = $this->rag($message);
            if ($rag) return $rag;
        }

        // 5. Tư vấn / tìm sách bằng ngôn ngữ tự nhiên
        $isAdvice = $this->has($q, ['goi y', 'tu van', 'nen doc', 'nen mua', 'tim', 'co sach', 'co truyen', 'muon doc', 'can sach', 'sach ve', 'truyen ve', 'hay nhat', 'ban chay', 'moi nhat']);
        $res = $this->search->search($message, 4);
        if ($res['results']->isNotEmpty() && ($isAdvice || $res['results']->first()->score > 0.08 || $res['filters']['category_ids'] || $res['filters']['author_ids'])) {
            $understood = $this->search->explain($res['filters']);
            $text = 'Mình tìm được vài cuốn phù hợp' . ($understood ? ' (' . implode(' · ', $understood) . ')' : '') . ':';
            return $this->out('recommend', $this->maybeLlm($message, $text, $res['results']), $res['results']);
        }
        if ($isAdvice) {
            $books = $user ? $this->reco->forUser($user->id, null, 4) : $this->reco->bestSellers(4);
            return $this->out('recommend', 'Mình chưa tìm thấy sách khớp chính xác, bạn tham khảo các cuốn đang được yêu thích nhé:', $books);
        }

        // 6. Không rõ ý -> thử RAG, cuối cùng là câu mặc định
        $rag = $this->rag($message);
        if ($rag) return $rag;

        return $this->out('fallback', "Xin lỗi, mình chưa hiểu câu hỏi 😅. Bạn thử hỏi theo dạng: \"tìm sách kinh tế dưới 150k\", \"chính sách đổi trả\", \"mã giảm giá đang có\" hoặc gọi hotline 1900 6868 nhé.");
    }

    protected function out($intent, $answer, $books = null, $sources = [])
    {
        $list = [];
        foreach ($books ?: [] as $b) {
            $v = $b->display_variant;
            $list[] = [
                'title'  => $b->title,
                'author' => $b->author_names,
                'price'  => $v ? money($v->effective_price) : '',
                'url'    => route('books.show', $b->slug),
                'cover'  => $b->cover_url,
            ];
        }
        return ['intent' => $intent, 'answer' => $answer, 'books' => $list, 'sources' => $sources];
    }

    /* ---------------------------------------------------- Các nhánh trả lời */

    protected function orderAnswer($q, $user)
    {
        $order = null;
        if (preg_match('/\b(ms\d{6}[a-z0-9]{5})\b/', $q, $m)) {
            $order = Order::where('code', strtoupper($m[1]))->first();
            if ($order && (!$user || ($order->user_id != $user->id && !$user->isBackend()))) {
                return $this->out('order', 'Vì lý do bảo mật, bạn vui lòng đăng nhập đúng tài khoản đặt đơn hoặc dùng trang "Tra cứu đơn hàng" (cần mã đơn + số điện thoại).');
            }
        } elseif ($user) {
            $order = $user->orders()->first();
        } else {
            return $this->out('order', 'Bạn vui lòng đăng nhập để mình kiểm tra đơn hàng, hoặc vào mục "Tra cứu đơn hàng" ở cuối trang và nhập mã đơn + số điện thoại.');
        }
        if (!$order) return $this->out('order', 'Mình không tìm thấy đơn hàng nào phù hợp.');

        $text = "Đơn {$order->code} (" . $order->created_at->format('d/m/Y') . "): **{$order->status_name}**, tổng " . money($order->total)
              . ', ' . mb_strtolower($order->payment_status_name) . '.';
        if ($order->tracking_code) $text .= " Mã vận đơn: {$order->shipping_carrier} – {$order->tracking_code}.";
        $text .= "\nXem chi tiết: " . route('orders.show', $order->code);
        return $this->out('order', $text);
    }

    protected function promoAnswer()
    {
        $now = now();
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($now) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now); })
            ->where(function ($q) use ($now) { $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now); })
            ->where(function ($q) { $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'); })
            ->take(5)->get();
        $lines = [];
        foreach ($vouchers as $v) $lines[] = "• **{$v->code}**: {$v->description}";
        $text = $lines ? "Các mã giảm giá đang có:\n" . implode("\n", $lines) : 'Hiện chưa có mã giảm giá nào.';
        $fs = FlashSale::current();
        if ($fs) {
            $text .= "\n⚡ Flash Sale \"{$fs->name}\" đang diễn ra đến " . $fs->ends_at->format('H:i d/m') . ': ' . route('flash-sale');
        }
        return $this->out('promotion', $text);
    }

    /** Tìm sách được nhắc tên trong câu (khớp tiêu đề đã bỏ dấu, ưu tiên tên dài) */
    protected function findMentionedBook($q)
    {
        $best = null; $bestLen = 0;
        foreach (Book::active()->get(['id', 'title']) as $b) {
            $t = TextAnalyzer::normalize(preg_replace('/\s*-\s*tập.*$/iu', '', $b->title));
            if (mb_strlen($t) >= 4 && strpos(' ' . $q . ' ', ' ' . $t . ' ') !== false && mb_strlen($t) > $bestLen) {
                $best = $b->id; $bestLen = mb_strlen($t);
            }
        }
        return $best ? Book::with(['authors', 'variants', 'publisher', 'category'])->find($best) : null;
    }

    protected function bookAnswer(Book $book, $q)
    {
        $lines = ["📖 **{$book->title}** – {$book->author_names}"];
        foreach ($book->variants as $v) {
            $lines[] = "• {$v->name}: " . money($v->effective_price) . ($v->stock > 0 ? " (còn {$v->stock} cuốn)" : ' (tạm hết hàng)');
        }
        $lines[] = 'Thể loại: ' . optional($book->category)->name . ' · NXB: ' . optional($book->publisher)->name . ' · Năm ' . $book->publish_year;
        if ($book->rating_count) {
            $lines[] = 'Đánh giá: ' . number_format($book->rating_avg, 1) . "/5 từ {$book->rating_count} lượt.";
        }
        if ($this->has($q, ['noi dung', 'hay khong', 'co hay', 'review', 'thong tin']) && $book->description) {
            $lines[] = $book->description;
        }
        return $this->out('book_info', implode("\n", $lines), [$book]);
    }

    /* ---------------------------------------------------- RAG */

    /** Truy xuất top-k câu liên quan từ kho tri thức */
    public function retrieve($question, $k = 3)
    {
        $qTokens = TextAnalyzer::tokenize($question . ' ' . implode(' ', TextAnalyzer::expand($question)));
        if (!$qTokens) return [];

        $chunks = [];
        foreach (KnowledgeDoc::where('is_active', true)->get() as $doc) {
            foreach (TextAnalyzer::sentences($doc->content) as $s) {
                $chunks[] = ['doc' => $doc, 'text' => $s, 'tf' => TextAnalyzer::termFrequency(TextAnalyzer::tokenize($doc->title . ' ' . $s))];
            }
        }
        if (!$chunks) return [];

        $df = [];
        foreach ($chunks as $c) foreach (array_keys($c['tf']) as $t) $df[$t] = ($df[$t] ?? 0) + 1;
        $n = count($chunks);
        $weigh = function ($tf) use ($df, $n) {
            $v = [];
            foreach ($tf as $t => $c) $v[$t] = (1 + log($c)) * (log(($n + 1) / (($df[$t] ?? 0) + 1)) + 1);
            return $v;
        };
        $qv = $weigh(TextAnalyzer::termFrequency($qTokens));
        foreach ($chunks as &$c) {
            $c['score'] = TextAnalyzer::cosine($qv, $weigh($c['tf']));
        }
        unset($c);
        usort($chunks, function ($a, $b) { return $b['score'] <=> $a['score']; });
        return array_values(array_filter(array_slice($chunks, 0, $k), function ($c) { return $c['score'] > 0.08; }));
    }

    public function rag($question)
    {
        $hits = $this->retrieve($question, 3);
        if (!$hits) return null;
        $sources = [];
        $lines = [];
        foreach ($hits as $h) {
            $lines[] = '• ' . $h['text'];
            $sources[$h['doc']->id] = $h['doc']->title;
        }
        $answer = "Theo quy định của BenBooks:\n" . implode("\n", $lines);
        $answer = $this->maybeLlm($question, $answer, null, implode("\n", $lines));
        return $this->out('rag', $answer, null, array_values($sources));
    }

    /* ---------------------------------------------------- LLM tùy chọn */

    protected function maybeLlm($question, $fallback, $books = null, $context = null)
    {
        $key = env('LLM_API_KEY');
        if (!$key) return $fallback;

        $ctx = $context ?: '';
        foreach ($books ?: [] as $b) {
            $v = $b->display_variant;
            $ctx .= "\n- {$b->title} ({$b->author_names}), " . ($v ? money($v->effective_price) : '') . ': ' . mb_substr((string) $b->description, 0, 200);
        }
        try {
            $res = Http::timeout(15)->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->post(env('LLM_API_URL', 'https://api.anthropic.com/v1/messages'), [
                'model' => env('LLM_MODEL', 'claude-haiku-5-5'),
                'max_tokens' => 400,
                'system' => 'Bạn là trợ lý bán sách của nhà sách BenBooks. Chỉ trả lời dựa trên NGỮ CẢNH được cung cấp, ngắn gọn, tiếng Việt. Nếu ngữ cảnh không có thông tin thì nói không biết.',
                'messages' => [['role' => 'user', 'content' => "NGỮ CẢNH:\n{$ctx}\n\nCÂU HỎI: {$question}"]],
            ]);
            if ($res->successful()) {
                $text = $res->json('content.0.text');
                if ($text) return $text;
            }
        } catch (\Throwable $e) {
            Log::warning('LLM chatbot lỗi, dùng chế độ offline: ' . $e->getMessage());
        }
        return $fallback;
    }
}
