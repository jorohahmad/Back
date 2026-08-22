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
        $rawFilePath = Storage::disk('local')->path($this->tempPath);

        $finalFileName = time() . '_' . rand(1, 1000) . '.webp'; 
        $finalFolder = 'imageInst';
        $finalPath = storage_path('app/public/' . $finalFolder . '/' . $finalFileName);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($rawFilePath);

        $image->scale(width: 800);

        $image->toWebp(80)->save($finalPath);

        $product = Product::find($this->productId);
        if ($product) {
            $product->{$this->columnName} = $finalFolder . '/' . $finalFileName;
            $product->save();
        }

        Storage::disk('local')->delete($this->tempPath);
    }
}
