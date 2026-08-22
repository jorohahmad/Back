<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:clear-abandoned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear abandoned carts that are older than 24 hours to free up reserved items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeLimit = Carbon::now()->subMinutes(5);
        $deletedCount = Cart::where('created_at', '<', $timeLimit)->delete();

        if ($deletedCount > 0) {
            $this->info("Successfully cleared {$deletedCount} abandoned cart item(s). The reserved items are now available again.");
        } else {
            $this->info('No abandoned cart items found. Everything is clean.');
        }
    }
}