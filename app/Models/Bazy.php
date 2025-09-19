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

            // prefix tylko do username i db osobno
            $baza->username = $user->name . '_' . $baza->username;
            $baza->db       = $user->name . '_' . $baza->db;

            // przypisanie user_id
            $baza->user_id = $user->id;

            // host zawsze %
            $baza->host = '%';

            // generowanie losowego hasła
            $baza->password = bin2hex(random_bytes(8));

            // data wygaśnięcia
            $baza->data_wygasniacia = now()->addDays(30);
        });

        static::created(function ($baza) {
            // Tworzymy użytkownika + bazę (MySQL/Postgres)
            Artisan::call('db:manage', [
                'action'   => 'add-user',       // add-user = tworzy usera + bazę
                'name'     => $baza->username,  // nazwa użytkownika
                'password' => $baza->password,
                'driver'   => $baza->type,      // mysql lub postgre
            ]);
        });
    }
}
