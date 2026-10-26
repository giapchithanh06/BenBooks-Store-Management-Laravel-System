<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

/** Thể loại, tác giả, nhà xuất bản, sách (đọc từ database/seeders/data/books.json) */
class CatalogSeeder extends Seeder
{
    public function run()
    {
        $tree = [
            ['Sách', 'sach', 'Sách chữ các thể loại', [
                ['Văn học', 'van-hoc', 'Tiểu thuyết, truyện ngắn, văn học Việt Nam và thế giới'],
                ['Kinh tế', 'kinh-te', 'Kinh doanh, quản trị, tài chính – đầu tư'],
                ['Kỹ năng sống', 'ky-nang-song', 'Phát triển bản thân, giao tiếp, thói quen'],
                ['Thiếu nhi', 'thieu-nhi', 'Sách cho trẻ em và tuổi học trò'],
                ['Khoa học', 'khoa-hoc', 'Khoa học phổ thông, vũ trụ, lịch sử loài người'],
                ['Lịch sử', 'lich-su', 'Lịch sử Việt Nam và thế giới'],
            ]],
            ['Truyện tranh', 'truyen-tranh', 'Manga, manhwa, manhua, comic và truyện tranh Việt', [
                ['Manga', 'manga', 'Truyện tranh Nhật Bản'],
                ['Manhwa', 'manhwa', 'Truyện tranh Hàn Quốc'],
                ['Manhua', 'manhua', 'Truyện tranh Trung Quốc'],
                ['Comic', 'comic', 'Truyện tranh Âu – Mỹ'],
                ['Truyện tranh Việt', 'truyen-tranh-viet', 'Truyện tranh do tác giả Việt Nam sáng tác'],
            ]],
        ];
        foreach ($tree as $i => $root) {
            $parent = Category::create(['name' => $root[0], 'slug' => $root[1], 'description' => $root[2], 'sort_order' => $i]);
            foreach ($root[3] as $j => $c) {
                Category::create(['name' => $c[0], 'slug' => $c[1], 'description' => $c[2], 'parent_id' => $parent->id, 'sort_order' => $j]);
            }
        }

        $bios = [
            'Nguyễn Nhật Ánh' => 'Nhà văn Việt Nam nổi tiếng với các tác phẩm về tuổi thơ và tuổi học trò.',
            'Paulo Coelho' => 'Nhà văn Brazil, tác giả của nhiều tiểu thuyết mang màu sắc triết lý.',
            'Haruki Murakami' => 'Nhà văn đương đại Nhật Bản, văn phong giàu chất cô đơn và siêu thực.',
            'Vũ Trọng Phụng' => 'Nhà văn hiện thực trào phúng Việt Nam giai đoạn 1930–1939.',
            'Tô Hoài' => 'Nhà văn Việt Nam, tác giả nhiều tác phẩm thiếu nhi kinh điển.',
            'Dale Carnegie' => 'Tác giả người Mỹ chuyên về kỹ năng giao tiếp và phát triển bản thân.',
            'Eiichiro Oda' => 'Họa sĩ manga Nhật Bản, tác giả bộ truyện phiêu lưu hải tặc nổi tiếng.',
            'Gosho Aoyama' => 'Họa sĩ manga Nhật Bản chuyên thể loại trinh thám.',
            'Fujiko F. Fujio' => 'Bút danh của họa sĩ manga Nhật Bản sáng tạo chú mèo máy đến từ tương lai.',
            'Akira Toriyama' => 'Họa sĩ manga Nhật Bản, cha đẻ của nhiều nhân vật võ thuật kinh điển.',
        ];
        $publishers = [
            'NXB Trẻ' => '161B Lý Chính Thắng, Quận 3, TP.HCM', 'NXB Kim Đồng' => '55 Quang Trung, Hai Bà Trưng, Hà Nội',
            'Nhã Nam' => '59 Đỗ Quang, Cầu Giấy, Hà Nội', 'First News' => '11H Nguyễn Thị Minh Khai, Quận 1, TP.HCM',
            'Alpha Books' => '176 Thái Hà, Đống Đa, Hà Nội', 'NXB Văn Học' => '18 Nguyễn Trường Tộ, Ba Đình, Hà Nội',
            'NXB Hồng Đức' => '65 Tràng Thi, Hoàn Kiếm, Hà Nội', 'IPM' => 'Hà Nội', 'Omega Plus' => 'Hà Nội', 'Phan Thị' => 'TP.HCM',
        ];

        $books = json_decode(file_get_contents(database_path('seeders/data/books.json')), true);
        $authorIds = [];
        $publisherIds = [];
        foreach ($books as $i => $b) {
            $ids = [];
            foreach ($b['authors'] as $name) {
                if (!isset($authorIds[$name])) {
                    $authorIds[$name] = Author::create(['name' => $name, 'slug' => vn_slug($name), 'bio' => $bios[$name] ?? null])->id;
                }
                $ids[] = $authorIds[$name];
            }
            $pub = $b['publisher'];
            if (!isset($publisherIds[$pub])) {
                $publisherIds[$pub] = Publisher::create(['name' => $pub, 'slug' => vn_slug($pub), 'address' => $publishers[$pub] ?? null])->id;
            }

            $book = Book::create([
                'title'        => $b['title'],
                'slug'         => $b['slug'],
                'category_id'  => Category::where('slug', $b['category'])->value('id'),
                'publisher_id' => $publisherIds[$pub],
                'isbn'         => $b['isbn'],
                'publish_year' => $b['year'],
                'pages'        => $b['pages'],
                'language'     => 'Tiếng Việt',
                'description'  => $b['description'],
                'tags'         => $b['tags'],
                'cover_image'  => 'images/books/' . $b['cover'],
                'is_active'    => true,
                'is_featured'  => !empty($b['featured']),
            ]);
            $book->authors()->sync($ids);

            foreach ($b['variants'] as $j => $v) {
                $variant = $book->variants()->create([
                    'name'           => $v['name'],
                    'sku'            => 'MS' . str_pad($book->id, 3, '0', STR_PAD_LEFT) . '-' . ($j + 1),
                    'price'          => $v['price'],
                    'original_price' => $v['original_price'],
                    'stock'          => 0,
                ]);
                // vài sản phẩm sắp hết / hết hàng để demo cảnh báo tồn kho
                $stock = $v['stock'];
                if ($i % 11 === 5 && $j === 0) $stock = 4;
                if ($i % 13 === 7 && $j === count($b['variants']) - 1) $stock = 0;
                $variant->stock = $stock;
                $variant->save();
                StockMovement::create(['variant_id' => $variant->id, 'type' => 'import', 'quantity' => $stock,
                                       'stock_after' => $stock, 'note' => 'Tồn đầu kỳ']);
            }
        }
    }
}
