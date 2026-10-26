<?php

namespace App\Jobs;

use App\Models\QueueLog;
use App\Services\SemanticSearchService;
use App\Services\SentimentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Job nền: xây lại vector index tìm kiếm ngữ nghĩa / phân tích lại cảm xúc review.
 * $task: 'search_index' | 'sentiment' | 'all'
 */
class RefreshAiData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;

    public function __construct($task = 'all')
    {
        $this->task = $task;
    }

    public function handle(SemanticSearchService $search, SentimentService $sentiment)
    {
        if (in_array($this->task, ['search_index', 'all'])) {
            $n = $search->rebuildIndex();
            Cache::forget('reco_copurchase');
            QueueLog::write('RefreshAiData', "Đã xây lại vector index cho {$n} đầu sách");
        }
        if (in_array($this->task, ['sentiment', 'all'])) {
            $n = $sentiment->analyzeAll();
            QueueLog::write('RefreshAiData', "Đã phân tích cảm xúc {$n} đánh giá");
        }
    }
}
