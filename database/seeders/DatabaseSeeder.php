<?php

namespace Database\Seeders;

use App\Services\CustomerSegmentationService;
use App\Services\FraudDetectionService;
use App\Services\SemanticSearchService;
use App\Services\SentimentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     */
    public function run()
    {
        $this->call([
            CatalogSeeder::class,
            UserSeeder::class,
            ShopSeeder::class,
            OrderSeeder::class,
        ]);

        // Khởi tạo dữ liệu cho các chức năng AI / phân tích
        Cache::forget('reco_copurchase');
        $this->command->info('Vector index: ' . app(SemanticSearchService::class)->rebuildIndex() . ' sách');
        $this->command->info('Phân tích cảm xúc: ' . app(SentimentService::class)->analyzeAll() . ' đánh giá');
        $this->command->info('Phân cụm khách hàng: ' . count(app(CustomerSegmentationService::class)->run()['rfm']) . ' khách');
        $this->command->info('Quét đơn bất thường: ' . app(FraudDetectionService::class)->scanAll() . ' đơn');
    }
}
