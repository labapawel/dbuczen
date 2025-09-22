<?php

namespace App\Console\Commands;


use app\Commands\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbManage extends Command
{
    protected $signature = 'db:manage
        {action : add-user|change-pass|delete-user|add-db|change-db-pass|delete-db}
        {name : nazwa usera albo bazy (np. user, user_baza1)}
        {password? : hasło (opcjonalnie)}
        {dbName? : nazwa bazy (opcjonalnie, potrzebna dla add-user-with-db)}
        {driver? : mysql|pgsql (opcjonalne, jeśli dotyczy bazy)}';

    protected $description = 'Zarządzanie użytkownikami i bazami w MySQL i PostgreSQL';

    public function handle()
    {
        $action   = $this->argument('action');
        $name     = $this->argument('name');
        $password = $this->argument('password');
        $dbName = $this->argument('dbName');
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
            case 'add-user-with-db': // <-- nowa akcja
                $dbName = $this->argument('dbName');
                $this->addUserWithDb($mysql, $pgsql, $name, $dbName, $password, $driver);
                break;
            case 'change-db-pass':
                $this->changeDbPass($mysql, $pgsql, $name, $password, $driver);
                break;
            case 'delete-db':
                $user   = $name;
                $dbName = $this->argument('dbName'); // jeśli nie podano dbName, użyj name jako nazwy bazy
                $driver = $this->argument('driver');
                $this->deleteDatabase($mysql, $pgsql, $dbName, $user, $driver);
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
            \Log::info("create mysql user $user $password with db $dbName ");
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

    private function addUserWithDb($mysql, $pgsql, $user, $dbName, $password, $driver = null)
{
    $mainUser = explode('_', $user)[0]; // prefix = główny user

    if ($driver === 'mysql' || $driver === null) {
        \Log::info("create mysql user $user $password with db $dbName ");
        $mysql->unprepared("
            CREATE DATABASE IF NOT EXISTS `$dbName`;
            CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED BY '$password';
            GRANT ALL PRIVILEGES ON `$dbName`.* TO '$user'@'%';
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
    private function deleteUser($user, $dbName, $driver = null)
    {
        
    try {
        if ($driver === 'mysql' || $driver === null) {
            DB::connection('mysql')->unprepared("
                DROP DATABASE IF EXISTS `$dbName`;
                DROP USER IF EXISTS '$user'@'%';
            ");
            \Log::info("MySQL: Usunięto bazę $dbName i użytkownika $user.");
        }

        if ($driver === 'pgsql' || $driver === null) {
            DB::connection('pgsql')->unprepared("REVOKE CONNECT ON DATABASE \"$dbName\" FROM PUBLIC;");
            DB::connection('pgsql')->unprepared("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$dbName';");
            DB::connection('pgsql')->unprepared("DROP DATABASE IF EXISTS \"$dbName\";");
            DB::connection('pgsql')->unprepared("DROP ROLE IF EXISTS \"$user\";");
            \Log::info("PostgreSQL: Usunięto bazę $dbName i użytkownika $user.");
        }
    } catch (\Exception $e) {
        // Logowanie błędów
        \Log::error("Błąd przy usuwaniu usera $user i bazy $dbName: " . $e->getMessage());
    }
    
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

    private function deleteDatabase($mysql, $pgsql, $dbName, $user, $driver = null)
{
    // Blokada dla systemowych baz danych MySQL
    if (in_array($dbName, ['mysql', 'information_schema', 'performance_schema', 'sys'])) {
        $this->error("Nie można usunąć systemowej bazy danych: $dbName");
        return;
    }

    // MySQL
    if ($driver === 'mysql' || $driver === null) {
        try {
            $mysql->unprepared("DROP USER IF EXISTS '$user'@'%';");
            $this->info("MySQL: Usunięto użytkownika $user");
        } catch (\Exception $e) {
            $this->error("MySQL: Błąd przy usuwaniu użytkownika $user: " . $e->getMessage());
        }

        try {
            $mysql->unprepared("DROP DATABASE IF EXISTS `$dbName`;");
            $this->info("MySQL: Usunięto bazę $dbName");
        } catch (\Exception $e) {
            $this->error("MySQL: Błąd przy usuwaniu bazy $dbName: " . $e->getMessage());
        }
    }

    // PostgreSQL
    if ($driver === 'pgsql' || $driver === null) {
        try {
            $pgsql->unprepared("REVOKE CONNECT ON DATABASE \"$dbName\" FROM PUBLIC;");
            $pgsql->unprepared("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$dbName';");
            $pgsql->unprepared("DROP DATABASE IF EXISTS \"$dbName\";");
            $pgsql->unprepared("DROP ROLE IF EXISTS \"$user\";");
            $this->info("PostgreSQL: Usunięto bazę $dbName i użytkownika $user");
        } catch (\Exception $e) {
            $this->error("PostgreSQL: Błąd przy usuwaniu bazy $dbName i użytkownika $user: " . $e->getMessage());
        }
    }
}

}
