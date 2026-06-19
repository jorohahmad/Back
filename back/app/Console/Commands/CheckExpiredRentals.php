<?php

namespace App\Console\Commands;

use App\Models\ProductItem;
use App\Models\RentalItem;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckExpiredRentals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rentals:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired user rentals and release the sample items to make them available again';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');
        //php artisan schedule:work
        $expiredRentalItems = RentalItem::where('end_date', '<', $today)
            ->where('status', 'rented')
            ->get();

        if ($expiredRentalItems->isEmpty()) {
            $this->info('There are no rentals ending today.');
            return;
        }
        $rentalItemIds = $expiredRentalItems->pluck('id')->toArray();
        $productItemIds = $expiredRentalItems->pluck('product_item_id')->toArray();

        try {
            DB::transaction(function () use ($rentalItemIds, $productItemIds) {
                // 2. تحرير القطع العينية لتعود متاحة في المخزن للإيجار
                ProductItem::whereIn('id', $productItemIds)->update(['status' => 'active']);

                // 3. تحديث حالة سطور الإيجار إلى "منتهية"
                RentalItem::whereIn('id', $rentalItemIds)->update(['status' => 'completed']);
            });

            // طباعة رسالة نجاح في السيرفر
            $this->info('Successfully closed' . count($rentalItemIds) . ' Lease contracts and the release of the associated plots.');
        } catch (Exception $e) {
            $this->error('An error occurred while updating the rents: ' . $e->getMessage());
        }
    }
}
