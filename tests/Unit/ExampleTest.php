<?php

namespace Tests\Unit;

use App\Services\ForecastService;
use App\Services\TextAnalyzer;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/helpers.php';

class ExampleTest extends TestCase
{
    public function test_bo_dau_tieng_viet()
    {
        $this->assertSame('dac nhan tam', TextAnalyzer::normalize('Đắc Nhân Tâm!'));
    }

    public function test_hoi_quy_tuyen_tinh()
    {
        list($a, $b) = (new ForecastService())->linearRegression([10, 12, 14, 16]);
        $this->assertEquals(10, $a);
        $this->assertEquals(2, $b);
    }

    public function test_dinh_dang_tien()
    {
        $this->assertSame('125.000₫', money(125000));
    }
}
