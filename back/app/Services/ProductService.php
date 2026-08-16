<?php

namespace App\Services;

use App\Jobs\ProcessProductImageJob;
use App\Jobs\ProcessProductVideoAndAudioJob;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
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
            $product->condition = $data['condition'];
            $tempUploads = [];
            $mediaData = [
                'audio_temp' => null,
                'video_temp' => null,
            ];
            for ($i = 1; $i <= 3; $i++) {
                if (isset($data['image' . $i])) {
                    // 1. حفظ سريع جداً في المجلد المؤقت
                    $tempPath = saveTempFile($data['image' . $i]);

                    $tempUploads['image' . $i] = $tempPath;
                    // 2. تعيين قيمة مبدئية لكي لا يبقى الحقل فارغاً
                    $product->{'image' . $i} = 'processing.png';
                }
            }
            if (isset($data['audio']) && $data['audio']) {
                // حفظ سريع في مجلد مؤقت
                $mediaData['audio_temp'] = saveFile($data['audio'], 'temp_media');
            }

            if (isset($data['video']) && $data['video']) {
                // حفظ سريع في مجلد مؤقت
                $mediaData['video_temp'] = saveFile($data['video'], 'temp_media');
            }

            $product->is_for_sale = $data['is_for_sale'] ?? false;
            $product->sale_price = $data['sale_price'] ?? 0;
            $product->is_for_rent = $data['is_for_rent'] ?? false;
            $product->rent_price_daily = $data['rent_price_daily'] ?? 0;
            $product->is_active = $data['is_active'] ?? false;
            $product->save();

            foreach ($tempUploads as $columnName => $tempPath) {
                ProcessProductImageJob::dispatch($tempPath, $product->id, $columnName);
            }
            if ($mediaData['audio_temp'] || $mediaData['video_temp']) {
                ProcessProductVideoAndAudioJob::dispatch($product->id, $mediaData);
            } //ffmpeg
            // create product items for rent
            if ($data['is_for_rent'] && $data['count'] > 0) {
                $itemsData = [];
                $now = Carbon::now(); 
                for ($i = 0; $i < $data['count']; $i++) {
                    $itemsData[] = [
                        'product_id'    => $product->id,
                        'serial_number' => 'SN-' . strtoupper(Str::random(8)) . '-' . rand(1000, 9999),
                        'condition'     => $data['condition'],
                        'status'        => 'active',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                // إدخال جميع القطع في قاعدة البيانات باستعلام واحد فقط (Bulk Insert)
                \App\Models\ProductItem::insert($itemsData);
            }
            return $product;
        });
    }
}
