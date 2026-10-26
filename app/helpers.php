<?php

/*
 * Hàm tiện ích dùng chung (được nạp trong AppServiceProvider – không cần sửa composer.json).
 */

if (!function_exists('money')) {
    /** 125000 => "125.000₫" */
    function money($amount)
    {
        return number_format((float) $amount, 0, ',', '.') . '₫';
    }
}

if (!function_exists('vn_slug')) {
    /** Bỏ dấu tiếng Việt + tạo slug */
    function vn_slug($text)
    {
        return \Illuminate\Support\Str::slug(vn_unaccent($text));
    }
}

if (!function_exists('vn_unaccent')) {
    /** Bỏ dấu tiếng Việt, giữ nguyên chữ hoa/thường */
    function vn_unaccent($text)
    {
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'A' => 'ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ',
            'd' => 'đ', 'D' => 'Đ',
            'e' => 'éèẻẽẹêếềểễệ', 'E' => 'ÉÈẺẼẸÊẾỀỂỄỆ',
            'i' => 'íìỉĩị', 'I' => 'ÍÌỈĨỊ',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ', 'O' => 'ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ',
            'u' => 'úùủũụưứừửữự', 'U' => 'ÚÙỦŨỤƯỨỪỬỮỰ',
            'y' => 'ýỳỷỹỵ', 'Y' => 'ÝỲỶỸỴ',
        ];
        foreach ($map as $plain => $chars) {
            $text = str_replace(preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY), $plain, $text);
        }
        return $text;
    }
}

if (!function_exists('stars')) {
    /** HTML 5 ngôi sao (Font Awesome) – đặt trong thẻ có class "sb-stars" để có màu vàng */
    function stars($rating)
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $html .= '<i class="fas fa-star"></i>';
            } elseif ($rating >= $i - 0.5) {
                $html .= '<i class="fas fa-star-half-alt"></i>';
            } else {
                $html .= '<i class="far fa-star"></i>';
            }
        }
        return $html;
    }
}
