<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class Bazy extends Model
{
    use HasFactory;

    protected $table = 'bazies';

    protected $fillable = [
        'user_id',
        'type',
        'username',
        'password',
        'db',
        'host',
        'data_wygasniacia',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($baza) {
            $user = auth()->user();

            // zmienne lokalne na początku
            $baseUsername = $user->name . '_' . $baza->username;
            $baseDb       = $user->name . '_' . $baza->db;
        
            // zapewnienie unikalności username
            $counter = 1;
            while (self::where('username', $baseUsername)->exists()) {
                $baseUsername = $user->name . '_' . $baza->username . $counter++;
            }
        
            // przypisanie do modelu
            $baza->username = $baseUsername;
            $baza->db       = $baseDb;
        
            $baza->user_id = $user->id;
            $baza->host = '%';
            $baza->password = bin2hex(random_bytes(8));
            $baza->data_wygasniacia = now()->addDays(14);
        });
        static::created(function ($baza) {
            // Tworzymy użytkownika + bazę (MySQL/Postgres) tylko dla dodatkowych baz
            Artisan::call('db:manage', [
                'action'   => 'add-user-with-db',   // nowa akcja
                'name'     => $baza->username,      // nazwa usera z prefixem
                'password' => $baza->password,
                'dbName'   => $baza->db,            // nazwa bazy z prefixem
                'driver'   => $baza->type,
            ]);
        });
    }
}
