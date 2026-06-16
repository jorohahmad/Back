<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $productId;
    protected $columnName;

    /**
     * Create a new job instance.
     */
    public function __construct($tempPath, $productId, $columnName)
    {
        $this->tempPath = $tempPath;
        $this->productId = $productId;
        $this->columnName = $columnName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. المسار الكامل للملف المؤقت الخام
        $rawFilePath = Storage::disk('local')->path($this->tempPath);

        // 2. تجهيز اسم ومسار الملف النهائي في الـ Public
        $finalFileName = time() . '_' . rand(1, 1000) . '.webp'; // صيغة webp ممتازة للضغط
        $finalFolder = 'imageInst';
        $finalPath = storage_path('app/public/' . $finalFolder . '/' . $finalFileName);

        // 3. تهيئة مكتبة Intervention والبدء بمعالجة الصورة (العمل الثقيل)
        $manager = new ImageManager(new Driver());
        $image = $manager->read($rawFilePath);

        // تصغير الصورة إذا كانت ضخمة (مثلاً أقصى عرض 800 بكسل مع الحفاظ على الأبعاد)
        $image->scale(width: 800);

        // حفظ الصورة النهائية مضغوطة بنسبة 80% (ستصبح حجمها كيلوبايتات بسيطة)
        $image->toWebp(80)->save($finalPath);

        // 4. تحديث حقل الصورة في الداتا بيز للمنتج
        $product = Product::find($this->productId);
        if ($product) {
            $product->{$this->columnName} = $finalFolder . '/' . $finalFileName;
            $product->save();
        }

        // 5. مسح الملف المؤقت الضخم لتنظيف مساحة السيرفر
        Storage::disk('local')->delete($this->tempPath);
    }
}
