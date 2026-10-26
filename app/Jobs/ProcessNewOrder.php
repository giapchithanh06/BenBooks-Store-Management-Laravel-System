<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\QueueLog;
use App\Services\CustomerSegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Message Queue: xử lý nền sau khi có đơn mới (gửi email xác nhận, cập nhật phân cụm khách hàng).
 * Chạy worker: php artisan queue:work database   (hoặc bấm "Xử lý hàng đợi" trong trang Admin > Hàng đợi)
 */
class ProcessNewOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(CustomerSegmentationService $segmentation)
    {
        $order = Order::with(['user', 'items'])->find($this->orderId);
        if (!$order) {
            return;
        }

        // Email xác nhận – dùng mailer "log" (ghi vào storage/logs/laravel.log) để chạy được không cần SMTP
        $lines = [];
        foreach ($order->items as $it) {
            $lines[] = "- {$it->book_title} ({$it->variant_name}) x{$it->quantity}: " . money($it->total);
        }
        $body = "Xin chào {$order->receiver_name},\n\nBenBooks đã nhận đơn hàng {$order->code}:\n" . implode("\n", $lines)
              . "\nTổng thanh toán: " . money($order->total) . "\n\nCảm ơn bạn đã mua sắm!";
        try {
            Mail::mailer('log')->raw($body, function ($m) use ($order) {
                $m->to(optional($order->user)->email ?: 'khach@motsach.local')->subject('Xác nhận đơn hàng ' . $order->code);
            });
        } catch (\Throwable $e) {
            Log::info('[BenBooks] Email xác nhận ' . $order->code . "\n" . $body);
        }

        // Cập nhật phân cụm khách hàng sau mỗi đơn
        $segmentation->run();

        QueueLog::write('ProcessNewOrder', "Đã gửi email xác nhận & cập nhật phân cụm cho đơn {$order->code}");
    }
}
