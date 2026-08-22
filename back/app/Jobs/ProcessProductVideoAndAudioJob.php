<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessProductVideoAndAudioJob implements ShouldQueue
{
    use Queueable;

    protected $productId;
    protected $mediaData;
    /**
     * Create a new job instance.
     */
    public function __construct(int $productId, array $mediaData)
    {
        $this->productId = $productId;
        $this->mediaData = $mediaData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (!$product) {
            return;
        }
        $updateFields = [];
        if ($this->mediaData['audio_temp']) {
            $tempAudioPath = $this->mediaData['audio_temp'];
            $finalAudioName = basename($tempAudioPath);
            $finalAudioPath = 'audioInst/' . $finalAudioName;

            if (Storage::disk('public')->exists($tempAudioPath)) {
                Storage::disk('public')->move($tempAudioPath, $finalAudioPath);
                $updateFields['audio'] = $finalAudioPath;
            }
        }

        if ($this->mediaData['video_temp']) {
            $tempVideoPath = $this->mediaData['video_temp'];
            $finalVideoName = basename($tempVideoPath);
            $finalVideoPath = 'videoInst/' . $finalVideoName;

            if (Storage::disk('public')->exists($tempVideoPath)) {
                Storage::disk('public')->move($tempVideoPath, $finalVideoPath);
                $updateFields['video'] = $finalVideoPath;
            }
        }
        if (!empty($updateFields)) {
            $product->update($updateFields);
        }
    }
}
