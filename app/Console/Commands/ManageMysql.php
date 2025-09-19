<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbManage extends Command
{
    protected $signature = 'db:manage
        {action : add-user|change-pass|delete-user|add-db|change-db-pass|delete-db}
        {name : nazwa usera albo bazy (np. user, user_baza1)}
        {password? : hasło (opcjonalnie)}
        {driver? : mysql|pgsql (opcjonalne, jeśli dotyczy bazy)}';

    protected $description = 'Zarządzanie użytkownikami i bazami w MySQL i PostgreSQL';

    public function handle()
    {
        $action   = $this->argument('action');
        $name     = $this->argument('name');
        $password = $this->argument('password');
        $driver   = $this->argument('driver'); // mysql lub pgsql

        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql');

        switch ($action) {
            case 'add-user':
                $this->addUser($mysql, $pgsql, $name, $password, $driver);
                break;
            case 'change-pass':
                $this->changePass($mysql, $pgsql, $name, $password, $driver);
                break;
            case 'delete-user':
                $this->deleteUser($mysql, $pgsql, $name, $driver);
                break;
            case 'add-db':
                $this->addDatabase($mysql, $pgsql, $name, $password, $driver);
                break;
            case 'change-db-pass':
                $this->changeDbPass($mysql, $pgsql, $name, $password, $driver);
                break;
            case 'delete-db':
                $this->deleteDatabase($mysql, $pgsql, $name, $driver);
                break;
            default:
                $this->error("Nieznana akcja: $action");
        }
    }

    private function addUser($mysql, $pgsql, $user, $password, $driver = null)
    {
        $dbName   = $user;
        $mainUser = explode('_', $user)[0]; // prefix przed "_" to główne konto

        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("
                CREATE DATABASE IF NOT EXISTS `$dbName`;
                CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED BY '$password';
                GRANT ALL PRIVILEGES ON `$dbName`.* TO '$user'@'%';
                -- Dodatkowy dostęp dla głównego użytkownika
                GRANT ALL PRIVILEGES ON `$dbName`.* TO '$mainUser'@'%';
                FLUSH PRIVILEGES;
            ");
        }

        if ($driver === 'pgsql' || $driver === null) {
            // 1. Tworzenie roli
            $pgsql->unprepared("
                DO \$\$
                BEGIN
                    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$user') THEN
                        CREATE ROLE \"$user\" LOGIN PASSWORD '$password';
                    END IF;
                END
                \$\$;
            ");

            // 2. Tworzenie bazy
            $pgsql->unprepared("CREATE DATABASE \"$dbName\" OWNER \"$user\";");

            // 3. Przywileje dla głównego użytkownika
            $pgsql->unprepared("GRANT ALL PRIVILEGES ON DATABASE \"$dbName\" TO \"$mainUser\";");
        }

        $this->info("Dodano usera $user i bazę $dbName z dostępem dla $mainUser w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }

    private function changePass($mysql, $pgsql, $user, $password, $driver = null)
    {
        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("ALTER USER '$user'@'%' IDENTIFIED BY '$password'; FLUSH PRIVILEGES;");
        }

        if ($driver === 'pgsql' || $driver === null) {
            $pgsql->unprepared("ALTER ROLE \"$user\" WITH PASSWORD '$password';");
        }

        $this->info("Zmieniono hasło dla $user w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }

    private function deleteUser($mysql, $pgsql, $user, $driver = null)
    {
        $dbName = $user;

        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("
                DROP DATABASE IF EXISTS `$dbName`;
                DROP USER IF EXISTS '$user'@'%';
            ");
        }

        if ($driver === 'pgsql' || $driver === null) {
            $pgsql->unprepared("REVOKE CONNECT ON DATABASE \"$dbName\" FROM PUBLIC;");
            $pgsql->unprepared("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$dbName';");
            $pgsql->unprepared("DROP DATABASE IF EXISTS \"$dbName\";");
            $pgsql->unprepared("DROP ROLE IF EXISTS \"$user\";");
        }

        $this->info("Usunięto usera $user i jego bazę w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }

    private function addDatabase($mysql, $pgsql, $db, $password, $driver = null)
    {
        // Tworzymy podkonto bazujące na głównym userze + nazwa bazy
        $user     = $db;
        $mainUser = explode('_', $db)[0]; // prefix = główny user

        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("
                CREATE DATABASE IF NOT EXISTS `$db`;
                CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED BY '$password';
                GRANT ALL PRIVILEGES ON `$db`.* TO '$user'@'%';
                GRANT ALL PRIVILEGES ON `$db`.* TO '$mainUser'@'%';
                FLUSH PRIVILEGES;
            ");
        }

        if ($driver === 'pgsql' || $driver === null) {
            $pgsql->unprepared("
                DO \$\$
                BEGIN
                    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$user') THEN
                        CREATE ROLE \"$user\" LOGIN PASSWORD '$password';
                    END IF;
                END
                \$\$;
            ");
            $pgsql->unprepared("CREATE DATABASE \"$db\" OWNER \"$user\";");
            $pgsql->unprepared("GRANT ALL PRIVILEGES ON DATABASE \"$db\" TO \"$mainUser\";");
        }

        $this->info("Dodano bazę $db i usera $user z dostępem dla $mainUser w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }

    private function changeDbPass($mysql, $pgsql, $db, $password, $driver = null)
    {
        $user = $db;

        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("ALTER USER '$user'@'%' IDENTIFIED BY '$password'; FLUSH PRIVILEGES;");
        }

        if ($driver === 'pgsql' || $driver === null) {
            $pgsql->unprepared("ALTER ROLE \"$user\" WITH PASSWORD '$password';");
        }

        $this->info("Zmieniono hasło usera $user dla bazy $db w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }

    private function deleteDatabase($mysql, $pgsql, $db, $driver = null)
    {
        $user = $db;

        if ($driver === 'mysql' || $driver === null) {
            $mysql->unprepared("
                DROP DATABASE IF EXISTS `$db`;
                DROP USER IF EXISTS '$user'@'%';
            ");
        }

        if ($driver === 'pgsql' || $driver === null) {
            $pgsql->unprepared("REVOKE CONNECT ON DATABASE \"$db\" FROM PUBLIC;");
            $pgsql->unprepared("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$db';");
            $pgsql->unprepared("DROP DATABASE IF EXISTS \"$db\";");
            $pgsql->unprepared("DROP ROLE IF EXISTS \"$user\";");
        }

        $this->info("Usunięto bazę $db i usera $user w " . ($driver ?? 'MySQL + PostgreSQL') . ".");
    }
}
