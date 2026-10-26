<?php

namespace App\Console\Commands;

use App\Services\CustomerSegmentationService;
use App\Services\FraudDetectionService;
use App\Services\SemanticSearchService;
use App\Services\SentimentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AiRefresh extends Command
{
    protected $signature = 'motsach:ai-refresh';
    protected $description = 'Xây lại vector index, phân tích cảm xúc, phân cụm khách hàng, quét đơn bất thường';

    public function handle(SemanticSearchService $search, SentimentService $sentiment,
                           CustomerSegmentationService $seg, FraudDetectionService $fraud)
    {
        Cache::forget('reco_copurchase');
        $this->info('Vector index: ' . $search->rebuildIndex() . ' sách');
        $this->info('Cảm xúc: ' . $sentiment->analyzeAll() . ' đánh giá');
        $this->info('Phân cụm: ' . count($seg->run()['rfm']) . ' khách hàng');
        $this->info('Quét bất thường: ' . $fraud->scanAll() . ' đơn');
        return 0;
    }
}
