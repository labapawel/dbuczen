<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Bazy;
use Carbon\Carbon;

class CleanupExpiredDatabases extends Command
{
    protected $signature = 'bazy:cleanup';
    protected $description = 'Usuń bazy, jeśli data_wygasniacia jest starsza niż dzisiejsza data';

    public function handle()
    {
        $today = Carbon::today();

        // znajdź wszystkie bazy, które wygasły
        $bazki = Bazy::whereDate('data_wygasniacia', '<', $today)->get();

        foreach ($bazki as $baza) {

            // Usuń usera + bazę (korzystając z Twojej komendy db:manage)
            Artisan::call('db:manage', [
                'action' => 'delete-db',
                'name'   => $baza->username,
                'dbName' => $baza->db,
                'driver' => $baza->type, // mysql / pgsql
            ]);

            // Usuń rekord z tabeli
            $baza->delete();
        }

        return 0;
    }
}
