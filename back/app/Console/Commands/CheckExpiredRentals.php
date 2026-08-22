<?php

namespace App\Console\Commands;

use App\Models\ProductItem;
use App\Models\RentalItem;
use App\Models\Rental; 
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Notifications\RentalReturnedNotification;

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
        
        // php artisan schedule:work
        $expiredRentalItems = RentalItem::where('end_date', '<', $today)
            ->where('status', 'rented')
            ->get();

        if ($expiredRentalItems->isEmpty()) {
            $this->info('There are no rentals ending today.');
            return;
        }

        $rentalItemIds = $expiredRentalItems->pluck('id')->toArray();
        $productItemIds = $expiredRentalItems->pluck('product_item_id')->toArray();
        
        $rentalIds = $expiredRentalItems->pluck('rental_id')->unique()->toArray();

        try {
            DB::transaction(function () use ($rentalItemIds, $productItemIds, $rentalIds) {
                ProductItem::whereIn('id', $productItemIds)->update(['status' => 'active']);
                RentalItem::whereIn('id', $rentalItemIds)->update(['status' => 'completed']);

                $rentalsWithActiveItems = RentalItem::whereIn('rental_id', $rentalIds)
                    ->where('status', 'rented')
                    ->pluck('rental_id')
                    ->toArray();

                $rentalsToClose = array_diff($rentalIds, $rentalsWithActiveItems);

                if (!empty($rentalsToClose)) {
                    Rental::whereIn('id', $rentalsToClose)->update(['status' => 'returned']);
                    
                    $closedRentals = Rental::with('renter')->whereIn('id', $rentalsToClose)->get();
                    
                    foreach ($closedRentals as $rental) {
                        if ($rental->renter) {
                            $rental->renter->notify(new RentalReturnedNotification($rental->id));
                        }
                    }
                }
            });

            $this->info('Successfully closed ' . count($rentalItemIds) . ' Lease contracts and the release of the associated plots.');
        } catch (Exception $e) {
            $this->error('An error occurred while updating the rents: ' . $e->getMessage());
        }
    }
}