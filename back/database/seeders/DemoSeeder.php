<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\NewAdminRegisteredNotification;
use App\Notifications\NewCheckoutAdminNotification;
use App\Notifications\NewImportantNoticeNotification;
use App\Notifications\NewProductCreatedAdminNotification;
use App\Notifications\NewUserRegisteredAdminNotification;
use App\Notifications\VerificationRequestAdminNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * بيانات وهمية للنسخة التجريبية من الداشبورد.
 * ⚠️ هذا الـ Seeder يمسح كل بيانات قاعدة البيانات ويعيد إنشاءها، لذلك لا يعمل إلا عند تفعيل DEMO_MODE=true
 * التشغيل: php artisan db:seed --class=DemoSeeder --force
 */
class DemoSeeder extends Seeder
{
    private const GOVERNORATES = ['Damascus', 'Aleppo', 'Homs', 'Latakia', 'Tartus', 'Hama'];

    private const OFFICES = [
        'Damascus' => ['Mazzeh Office', 'Midan Office'],
        'Aleppo'   => ['Aziziyeh Office', 'Shahba Office'],
        'Homs'     => ['Inshaat Office'],
        'Latakia'  => ['Corniche Office'],
        'Tartus'   => ['Port Office'],
        'Hama'     => ['Orontes Office'],
    ];

    // [الاسم, التصنيف, للبيع, للإيجار, سعر البيع, سعر الإيجار اليومي, الحالة]
    private const PRODUCTS = [
        ['Yamaha C40 Classical Guitar', 'Guitar', true, true, 180, 6, 'new'],
        ['Fender Player Stratocaster', 'Guitar', true, false, 850, null, 'new'],
        ['Ibanez GSR200 Bass Guitar', 'Bass', true, true, 320, 10, 'used'],
        ['Roland FP-30X Digital Piano', 'Piano', true, true, 720, 22, 'new'],
        ['Yamaha U1 Upright Piano', 'Piano', false, true, null, 45, 'used'],
        ['Casio CT-S300 Keyboard', 'Keyboard', true, true, 160, 5, 'new'],
        ['Korg Minilogue XD Synth', 'Synth', true, false, 640, null, 'new'],
        ['Pearl Export Drum Kit', 'Drums', true, true, 780, 25, 'used'],
        ['Alesis Nitro Mesh E-Drums', 'Drums', true, true, 420, 15, 'new'],
        ['Meinl Headliner Cajon', 'Percussion', true, true, 110, 4, 'new'],
        ['Gawharet El Fan Darbuka', 'Percussion', true, true, 140, 5, 'new'],
        ['Handmade Damascene Oud', 'Oud', true, true, 950, 30, 'new'],
        ['Turkish Oud (Used)', 'Oud', true, false, 380, null, 'used'],
        ['Professional Qanun', 'Qanun', true, true, 1400, 40, 'new'],
        ['Ney Flute Set (7 pieces)', 'Ney', true, false, 90, null, 'new'],
        ['Stentor Student II Violin 4/4', 'Violin', true, true, 210, 7, 'new'],
        ['Yamaha YAS-280 Alto Saxophone', 'Saxophone', true, true, 1150, 35, 'new'],
        ['Bach TR300 Trumpet', 'Trumpet', true, true, 520, 16, 'used'],
        ['Kala KA-15S Ukulele', 'Ukulele', true, false, 65, null, 'new'],
        ['Cello 4/4 with Case', 'Cello', false, true, null, 28, 'used'],
        ['Yamaha YFL-222 Flute', 'Flute', true, true, 430, 12, 'new'],
        ['Marshall DSL40 Amplifier', 'Amplifier', true, true, 690, 20, 'used'],
        ['Hohner Special 20 Harmonica', 'Harmonica', true, false, 45, null, 'new'],
        ['Buzuq Traditional', 'Buzuq', true, true, 260, 9, 'new'],
        ['Pending: Gibson Les Paul Studio', 'Guitar', true, false, 1600, null, 'used'],
        ['Pending: Riq Tambourine', 'Percussion', true, true, 75, 3, 'new'],
        ['Pending: Nord Stage 3 Piano', 'Piano', true, true, 3200, 90, 'used'],
        ['Pending: Mandolin Vintage', 'Mandolin', true, false, 240, null, 'used'],
    ];

    private const USERS = [
        'Omar Haddad', 'Lina Khoury', 'Rami Saleh', 'Nour Alhasan', 'Yara Mansour', 'Karim Darwish',
        'Sara Nasser', 'Hadi Ibrahim', 'Maya Azar', 'Fadi Jaber', 'Rana Halabi', 'Tarek Shami',
        'Dana Yousef', 'Bassel Hamdan', 'Hiba Kassem', 'Ziad Rahal', 'Leen Sabbagh', 'Majd Aloush',
        'Rasha Barakat', 'Samer Kanaan', 'Joud Atassi', 'Wael Haidar', 'Reem Nahhas', 'Adnan Fares',
    ];

    public function run(): void
    {
        if (!config('demo.enabled')) {
            throw new RuntimeException('DemoSeeder wipes the database. Set DEMO_MODE=true in .env first.');
        }

        mt_srand(2026);
        $this->wipe();

        $admins = $this->createAdmins();
        $users = $this->createUsers();
        $products = $this->createProducts($users, $admins);
        $this->createOrders($users, $products);
        $this->createRentals($users, $products);
        $this->createNotices($admins);
        $this->createRatingsAndOffers($users, $products);
        $this->createNotifications($admins, $users, $products);
    }

    // مسح البيانات القديمة مع الإبقاء على الحساب التجريبي وتوكناته (حتى لا يخرج الزوار عند التحديث اليومي)
    private function wipe(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'platform_earnings', 'rental_items', 'rentals', 'order_items', 'orders', 'carts',
            'favorites', 'price_offers', 'ratings', 'product_items', 'products', 'notices', 'lessons',
            'notifications',
        ] as $table) {
            DB::table($table)->truncate();
        }
        $demo = User::where('email', config('demo.email'))->first();
        User::where('email', '!=', config('demo.email'))->delete();
        DB::table('personal_access_tokens')
            ->where(fn($q) => $q->where('tokenable_id', '!=', $demo?->id ?? 0)->orWhere('tokenable_type', '!=', User::class))
            ->delete();
        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->deleteDirectory('demo');
    }

    private function createAdmins(): array
    {
        $demo = User::updateOrCreate(['email' => config('demo.email')], [
            'name'        => 'Demo Admin',
            'password'    => Hash::make(Str::random(32)),
            'role'        => 'admin',
            'active'      => '1',
            'is_verified' => true,
        ]);
        $demo->forceFill(['created_at' => now()->subMonths(4)])->save();

        $admins = [$demo];
        foreach (['Ahmad Mohamad', 'Hamzah Khadour'] as $name) {
            $admins[] = $this->makeUser($name, 'admin', '1', true, now()->subMonths(4));
        }
        // أدمن ينتظر الموافقة ليظهر في قائمة الطلبات
        $this->makeUser('Khaled Mourad', 'admin', '0', false, now()->subDays(1));

        return $admins;
    }

    private function createUsers(): array
    {
        $users = [];
        foreach (self::USERS as $i => $name) {
            // معظم المستخدمين مفعلين، والبعض أرسل طلب توثيق، والبعض جديد
            [$active, $verified] = match (true) {
                $i >= 21 => ['0', true],
                $i >= 19 => ['0', false],
                default  => ['1', true],
            };
            $user = $this->makeUser($name, 'user', $active, $verified, now()->subDays(mt_rand(1, 110)));
            $user->update(['balance' => mt_rand(0, 400)]);
            $users[] = $user;
        }
        return array_values(array_filter($users, fn($u) => $u->active === '1'));
    }

    private function makeUser(string $name, string $role, string $active, bool $verified, Carbon $createdAt): User
    {
        $user = User::create([
            'name'        => $name,
            'email'       => Str::slug($name, '.') . '@gmail.com',
            'password'    => Hash::make(Str::random(32)),
            'role'        => $role,
            'active'      => $active,
            'is_verified' => $verified,
        ]);
        $user->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        return $user;
    }

    private function createProducts(array $users, array $admins): array
    {
        $products = [];
        foreach (self::PRODUCTS as $i => [$title, $category, $sale, $rent, $salePrice, $rentPrice, $condition]) {
            $pending = str_starts_with($title, 'Pending: ');
            $title = str_replace('Pending: ', '', $title);
            // أول 6 منتجات يملكها الأدمن لتظهر في صفحة "منتجاتنا"
            $owner = $i < 6 ? $admins[$i % count($admins)] : $users[$i % count($users)];
            $gov = self::GOVERNORATES[$i % count(self::GOVERNORATES)];
            $stock = mt_rand(2, 5);
            $createdAt = $pending ? now()->subDays(mt_rand(0, 3)) : now()->subDays(mt_rand(4, 100));

            $id = DB::table('products')->insertGetId([
                'owner_id'         => $owner->id,
                'is_active'        => !$pending,
                'title'            => $title,
                'description'      => "{$title} in {$condition} condition. Well maintained and ready to play.",
                'governorate'      => $gov,
                'office'           => self::OFFICES[$gov][0],
                'image1'           => $this->makeImage($title, $category, $i),
                'condition'        => $condition,
                'is_for_sale'      => $sale,
                'sale_price'       => $salePrice,
                'is_for_rent'      => $rent,
                'rent_price_daily' => $rentPrice,
                'announcement'     => $i % 7 === 0,
                'stock'            => $stock,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $itemIds = [];
            for ($n = 1; $n <= $stock; $n++) {
                $itemIds[] = DB::table('product_items')->insertGetId([
                    'product_id'    => $id,
                    'serial_number' => sprintf('SN-%03d-%02d-%s', $id, $n, strtoupper(Str::random(4))),
                    'condition'     => $condition,
                    'status'        => $pending ? 'pending' : 'active',
                    'created_at'    => $createdAt,
                    'updated_at'    => $createdAt,
                ]);
            }

            if (!$pending) {
                $products[] = compact('id', 'title', 'owner', 'sale', 'rent', 'salePrice', 'rentPrice', 'itemIds');
            }
        }
        return $products;
    }

    // صورة SVG بسيطة لكل منتج حتى لا تظهر الصور فارغة
    private function makeImage(string $title, string $category, int $i): string
    {
        $palettes = [['#2563eb', '#7c3aed'], ['#0f766e', '#22c55e'], ['#b45309', '#ef4444'], ['#be185d', '#8b5cf6'], ['#0369a1', '#06b6d4']];
        [$c1, $c2] = $palettes[$i % count($palettes)];
        $title = htmlspecialchars($title, ENT_XML1);
        $category = htmlspecialchars(strtoupper($category), ENT_XML1);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$c1}"/><stop offset="1" stop-color="{$c2}"/></linearGradient></defs>
  <rect width="800" height="600" fill="url(#g)"/>
  <circle cx="400" cy="250" r="110" fill="#ffffff" fill-opacity="0.15"/>
  <path d="M370 190v105a32 32 0 1 1-18-29v-61l90-20v85a32 32 0 1 1-18-29v-56z" fill="#ffffff"/>
  <text x="400" y="430" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" letter-spacing="6" fill="#ffffff" fill-opacity="0.8">{$category}</text>
  <text x="400" y="480" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="bold" fill="#ffffff">{$title}</text>
</svg>
SVG;
        $path = 'demo/products/product-' . ($i + 1) . '.svg';
        Storage::disk('public')->put($path, $svg);
        return $path;
    }

    // عدد العمليات في اليوم: الداشبورد يقارن الأسبوع الحالي (غير المكتمل) بالأسبوع الماضي كاملاً،
    // لذلك نوزع على أيام الأسبوع الحالي ما يعادل ~120% من الأسبوع الماضي حتى تظهر نسب النمو موجبة أي يوم يُفتح فيه الموقع
    private function dailyCount(int $daysAgo): int
    {
        $elapsedDays = now()->startOfWeek(Carbon::MONDAY)->diffInDays(now()->startOfDay()) + 1;
        if ($daysAgo < $elapsedDays) {
            return max(1, (int) round(2 * 7 * 1.2 / $elapsedDays) + mt_rand(-1, 1));
        }
        return $daysAgo <= 30 ? mt_rand(1, 3) : mt_rand(0, 2);
    }

    private function randomTime(int $daysAgo): Carbon
    {
        $time = now()->subDays($daysAgo)->setTime(mt_rand(9, 22), mt_rand(0, 59));
        return $time->isFuture() ? now()->subMinutes(mt_rand(5, 120)) : $time;
    }

    private function createOrders(array $users, array $products): void
    {
        $forSale = array_values(array_filter($products, fn($p) => $p['sale']));

        for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
            for ($k = 0; $k < $this->dailyCount($daysAgo); $k++) {
                $at = $this->randomTime($daysAgo);
                $buyer = $users[array_rand($users)];
                $picked = array_rand($forSale, mt_rand(1, 2));
                $status = $daysAgo > 2 ? ($this->chance(90) ? 'completed' : 'cancelled') : ($this->chance(60) ? 'completed' : 'pending');
                $gov = self::GOVERNORATES[array_rand(self::GOVERNORATES)];

                $orderId = DB::table('orders')->insertGetId([
                    'buyer_id'            => $buyer->id,
                    'transaction_id'      => $this->transactionId(),
                    'total_price'         => 0,
                    'status'              => $status,
                    'transfer_status'     => $this->transferStatus($daysAgo, $status),
                    'expected_arrival_at' => $daysAgo <= 1 ? now()->addHours(mt_rand(2, 30)) : null,
                    'receive_governorate' => $gov,
                    'receive_office'      => self::OFFICES[$gov][0],
                    'created_at'          => $at,
                    'updated_at'          => $at,
                ]);

                $total = 0;
                foreach ((array) $picked as $idx) {
                    $p = $forSale[$idx];
                    $qty = $this->chance(80) ? 1 : 2;
                    $total += $p['salePrice'] * $qty;
                    DB::table('order_items')->insert([
                        'order_id'   => $orderId,
                        'seller_id'  => $p['owner']->id,
                        'product_id' => $p['id'],
                        'quantity'   => $qty,
                        'unit_price' => $p['salePrice'],
                        'created_at' => $at,
                        'updated_at' => $at,
                    ]);
                }
                DB::table('orders')->where('id', $orderId)->update(['total_price' => $total]);

                if ($status === 'completed') {
                    $this->earning(['order_id' => $orderId], $total, 'sale', $at);
                }
            }
        }
    }

    private function createRentals(array $users, array $products): void
    {
        $forRent = array_values(array_filter($products, fn($p) => $p['rent']));
        $busyItems = [];

        for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
            for ($k = 0; $k < $this->dailyCount($daysAgo); $k++) {
                $at = $this->randomTime($daysAgo);
                $p = $forRent[array_rand($forRent)];
                $freeItems = array_values(array_diff($p['itemIds'], $busyItems));
                if (!$freeItems) {
                    continue;
                }
                $itemId = $freeItems[array_rand($freeItems)];
                $days = mt_rand(2, 14);
                $start = $at->copy()->startOfDay();
                $end = $start->copy()->addDays($days);
                $stillOut = $end->isFuture();
                $status = $stillOut ? 'active' : 'returned';
                $total = $p['rentPrice'] * $days;
                $gov = self::GOVERNORATES[array_rand(self::GOVERNORATES)];

                $rentalId = DB::table('rentals')->insertGetId([
                    'renter_id'           => $users[array_rand($users)]->id,
                    'transaction_id'      => $this->transactionId(),
                    'total_price'         => $total,
                    'status'              => $status,
                    'transfer_status'     => $this->transferStatus($daysAgo, 'completed'),
                    'expected_arrival_at' => $daysAgo <= 1 ? now()->addHours(mt_rand(2, 30)) : null,
                    'receive_governorate' => $gov,
                    'receive_office'      => self::OFFICES[$gov][0],
                    'created_at'          => $at,
                    'updated_at'          => $at,
                ]);
                DB::table('rental_items')->insert([
                    'rental_id'       => $rentalId,
                    'lessor_id'       => $p['owner']->id,
                    'product_item_id' => $itemId,
                    'start_date'      => $start->toDateString(),
                    'end_date'        => $end->toDateString(),
                    'rent_days'       => $days,
                    'unit_price'      => $p['rentPrice'],
                    'status'          => $stillOut ? 'rented' : 'completed',
                    'created_at'      => $at,
                    'updated_at'      => $at,
                ]);
                if ($stillOut) {
                    $busyItems[] = $itemId;
                    DB::table('product_items')->where('id', $itemId)->update(['status' => 'rented']);
                }
                $this->earning(['rental_id' => $rentalId], $total, 'rental', $at);
            }
        }
    }

    private function earning(array $ref, float $amount, string $type, Carbon $at): void
    {
        DB::table('platform_earnings')->insert($ref + [
            'transaction_amount' => $amount,
            'commission_amount'  => round($amount * 0.02, 2),
            'type'               => $type,
            'created_at'         => $at,
            'updated_at'         => $at,
        ]);
    }

    private function transferStatus(int $daysAgo, string $status): string
    {
        if ($status !== 'completed') {
            return 'pending';
        }
        return match (true) {
            $daysAgo === 0 => 'pending',
            $daysAgo <= 1  => 'onWay',
            $daysAgo <= 3  => 'reached',
            default        => 'finished',
        };
    }

    private function createNotices(array $admins): void
    {
        $notices = [
            ['Review pending instruments', 'Four new instruments are waiting for approval.', 'urgent', 1],
            ['Weekly shipping report', 'Prepare the shipping summary for the Damascus office.', 'project', 3],
            ['Call the Aleppo office', 'Confirm the new delivery schedule.', 'personal', 2],
            ['Commission policy update', 'Platform commission stays at 2% for this quarter.', 'project', 10],
            ['Verify new sellers', 'Check ID documents for this week\'s verification requests.', 'urgent', 0],
            ['Plan the summer campaign', 'Discounts on rentals for music students.', 'project', 20],
            ['Old Q2 report', 'Archived after the review meeting.', 'archive', -30],
            ['Order new packaging', 'Protective cases for oud and violin shipping.', 'personal', 6],
        ];
        foreach ($notices as $i => [$title, $description, $type, $dueInDays]) {
            $at = now()->subDays(mt_rand(1, 20));
            DB::table('notices')->insert([
                'user_id'     => $admins[$i % count($admins)]->id,
                'title'       => $title,
                'description' => $description,
                'due_date'    => now()->addDays($dueInDays)->toDateString(),
                'type'        => $type,
                'created_at'  => $at,
                'updated_at'  => $at,
            ]);
        }
    }

    private function createRatingsAndOffers(array $users, array $products): void
    {
        $pairs = [];
        foreach ($products as $p) {
            $rater = $users[array_rand($users)];
            if ($rater->id === $p['owner']->id || isset($pairs["{$rater->id}-{$p['owner']->id}"])) {
                continue;
            }
            $pairs["{$rater->id}-{$p['owner']->id}"] = true;
            DB::table('ratings')->insert([
                'rater_id'   => $rater->id,
                'rated_id'   => $p['owner']->id,
                'score'      => mt_rand(3, 5),
                'comment'    => ['Great seller!', 'Instrument as described.', 'Fast delivery, thanks.', 'Very good condition.'][mt_rand(0, 3)],
                'created_at' => now()->subDays(mt_rand(1, 40)),
                'updated_at' => now(),
            ]);
        }

        foreach (array_slice($products, 0, 10) as $i => $p) {
            $type = $p['sale'] ? 'sale' : 'rent';
            $price = $type === 'sale' ? $p['salePrice'] : $p['rentPrice'];
            DB::table('price_offers')->insert([
                'user_id'        => $users[array_rand($users)]->id,
                'product_id'     => $p['id'],
                'type'           => $type,
                'proposed_price' => round($price * 0.85, 2),
                'status'         => ['pending', 'accepted', 'rejected'][$i % 3],
                'created_at'     => now()->subDays(mt_rand(0, 10)),
                'updated_at'     => now(),
            ]);
        }
    }

    private function createNotifications(array $admins, array $users, array $products): void
    {
        $demo = $admins[0];
        $recentTxn = DB::table('orders')->latest('id')->limit(3)->pluck('transaction_id');
        $items = [
            [new NewCheckoutAdminNotification($recentTxn[0] ?? 'TXN', $users[0]->name), 20, false],
            [new NewProductCreatedAdminNotification('Gibson Les Paul Studio', $users[3]->name), 55, false],
            [new VerificationRequestAdminNotification('Joud Atassi'), 90, false],
            [new NewUserRegisteredAdminNotification('Reem Nahhas'), 150, false],
            [new NewAdminRegisteredNotification('Khaled Mourad'), 240, false],
            [new NewImportantNoticeNotification('Verify new sellers', 'urgent', $admins[1]->name), 400, true],
            [new NewCheckoutAdminNotification($recentTxn[1] ?? 'TXN', $users[5]->name), 600, true],
            [new NewProductCreatedAdminNotification('Nord Stage 3 Piano', $users[7]->name), 900, true],
            [new NewImportantNoticeNotification('Weekly shipping report', 'project', $admins[2]->name), 1500, true],
            [new NewCheckoutAdminNotification($recentTxn[2] ?? 'TXN', $users[9]->name), 2600, true],
        ];
        foreach ($items as [$notification, $minutesAgo, $read]) {
            $at = now()->subMinutes($minutesAgo);
            DB::table('notifications')->insert([
                'id'              => Str::uuid()->toString(),
                'type'            => get_class($notification),
                'notifiable_type' => User::class,
                'notifiable_id'   => $demo->id,
                'data'            => json_encode($notification->toDatabase($demo)),
                'read_at'         => $read ? $at : null,
                'created_at'      => $at,
                'updated_at'      => $at,
            ]);
        }
    }

    private function transactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(10));
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }
}
