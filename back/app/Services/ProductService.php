<?php

namespace App\Services;

use App\Jobs\ProcessProductImageJob;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProduct(int $userId, array $data)
    {
        return DB::transaction(function () use ($data, $userId) {

            $product = new Product();
            $product->owner_id = $userId;
            $product->title = $data['title'];
            $product->description = $data['description'];
            $product->stock = $data['count'];
            $tempUploads = [];
            for ($i = 1; $i <= 3; $i++) {
                if (isset($data['image' . $i])) {
                    // 1. حفظ سريع جداً في المجلد المؤقت
                    $tempPath = saveTempFile($data['image' . $i]);

                    $tempUploads['image' . $i] = $tempPath; // يمكنك وضع صورة "جاري التحميل" افتراضية
                    // 2. تعيين قيمة مبدئية لكي لا يبقى الحقل فارغاً
                    $product->{'image' . $i} = 'processing.png';
                }
            }
            if ($data['audio']) {
                $fileName = saveFile($data['audio'], 'audioInst');
                $product->audio = $fileName;
            }
            if ($data['video']) {
                $videoPath = saveFile($data['video'], 'vedioInst');
                $product->video = $videoPath;
            }

            $product->is_for_sale = $data['is_for_sale'] ?? false;
            $product->sale_price = $data['sale_price'] ?? 0;
            $product->is_for_rent = $data['is_for_rent'] ?? false;
            $product->rent_price_daily = $data['rent_price_daily'] ?? 0;
            $product->save();

            foreach ($tempUploads as $columnName => $tempPath) {
                ProcessProductImageJob::dispatch($tempPath, $product->id, $columnName);
            }
            // create product items for rent
            if ($data['is_for_rent']) {
                for ($i = 0; $i < $data['count']; $i++) {
                    $p = $product->items()->create([
                        'serial_number' => rand(1, 999999),
                        'condition' => $data['condition'],
                        'status' => 'active',
                    ]);

                    $p->update([
                        'serial_number' => $p->id
                    ]);
                }
            }
            return $product;
        });
    }
}
