<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cria / atualiza dois usuários MySQL:
 *
 *  1. app_user   — runtime da aplicação (SELECT, INSERT, UPDATE, DELETE apenas)
 *  2. migrator   — somente para migrations (todos os DDL necessários)
 *
 * Deve ser executado conectado com um superusuário (root) via DB_USERNAME_SUPER.
 *
 * Uso:
 *   php artisan setup:db-permissions
 *
 * Variáveis de ambiente necessárias (Forge → Environment):
 *   DB_SUPER_USERNAME  — usuário MySQL com GRANT OPTION (geralmente 'forge' ou 'root')
 *   DB_SUPER_PASSWORD  — senha do superusuário
 *   DB_USERNAME        — usuário runtime da app (sem DDL)
 *   DB_PASSWORD        — senha do usuário runtime
 *   DB_MIGRATE_USERNAME — usuário para migrations
 *   DB_MIGRATE_PASSWORD — senha do usuário de migration
 *   DB_DATABASE        — nome do banco
 *   DB_HOST            — host do banco
 */
class SetupDatabasePermissions extends Command
{
    protected $signature   = 'setup:db-permissions';
    protected $description = 'Cria usuários MySQL com privilégios mínimos (app_user sem DDL, migrator com DDL)';

    public function handle(): int
    {
        $database        = config('database.connections.mysql.database');
        $host            = config('database.connections.mysql.host', '127.0.0.1');

        $appUser         = env('DB_USERNAME');
        $appPass         = env('DB_PASSWORD');
        $migratorUser    = env('DB_MIGRATE_USERNAME');
        $migratorPass    = env('DB_MIGRATE_PASSWORD');
        $superUser       = env('DB_SUPER_USERNAME');
        $superPass       = env('DB_SUPER_PASSWORD');

        // Validações
        foreach (['DB_USERNAME', 'DB_PASSWORD', 'DB_MIGRATE_USERNAME', 'DB_MIGRATE_PASSWORD', 'DB_SUPER_USERNAME', 'DB_SUPER_PASSWORD'] as $var) {
            if (empty(env($var))) {
                $this->error("Variável de ambiente {$var} não definida.");
                return self::FAILURE;
            }
        }

        $this->info("Conectando com superusuário '{$superUser}'...");

        // Conecta com o superusuário dinamicamente
        config(['database.connections.mysql_super' => array_merge(
            config('database.connections.mysql'),
            ['username' => $superUser, 'password' => $superPass],
        )]);

        try {
            DB::connection('mysql_super')->statement('SELECT 1');
        } catch (\Exception $e) {
            $this->error('Falha ao conectar com superusuário: ' . $e->getMessage());
            return self::FAILURE;
        }

        $conn = DB::connection('mysql_super');

        // ─────────────────────────────────────────────────────────
        // 1. Usuário de RUNTIME (sem nenhum DDL)
        // ─────────────────────────────────────────────────────────
        $this->info("Criando/atualizando usuário runtime: {$appUser}@{$host}");

        $conn->statement("CREATE USER IF NOT EXISTS '{$appUser}'@'{$host}' IDENTIFIED BY '{$appPass}'");
        $conn->statement("ALTER  USER '{$appUser}'@'{$host}' IDENTIFIED BY '{$appPass}'");

        // Revoga absolutamente tudo primeiro (idempotente)
        $conn->statement("REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{$appUser}'@'{$host}'");

        // Concede apenas DML — sem CREATE, DROP, ALTER, INDEX, REFERENCES
        $conn->statement("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$database}`.* TO '{$appUser}'@'{$host}'");

        $this->line("  ✔  SELECT, INSERT, UPDATE, DELETE → {$appUser}");

        // ─────────────────────────────────────────────────────────
        // 2. Usuário de MIGRATION (DDL completo, sem SUPER/GRANT)
        // ─────────────────────────────────────────────────────────
        $this->info("Criando/atualizando usuário migrator: {$migratorUser}@{$host}");

        $conn->statement("CREATE USER IF NOT EXISTS '{$migratorUser}'@'{$host}' IDENTIFIED BY '{$migratorPass}'");
        $conn->statement("ALTER  USER '{$migratorUser}'@'{$host}' IDENTIFIED BY '{$migratorPass}'");

        $conn->statement("REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{$migratorUser}'@'{$host}'");

        // DDL necessário para migrations Laravel + runtime DML
        $conn->statement("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, CREATE TEMPORARY TABLES ON `{$database}`.* TO '{$migratorUser}'@'{$host}'");

        $this->line("  ✔  SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER → {$migratorUser}");

        // ─────────────────────────────────────────────────────────
        // Aplica imediatamente
        // ─────────────────────────────────────────────────────────
        $conn->statement('FLUSH PRIVILEGES');

        $this->newLine();
        $this->info('✅  Permissões aplicadas com sucesso.');
        $this->newLine();
        $this->table(
            ['Usuário', 'Permissões', 'Uso'],
            [
                [$appUser,      'SELECT INSERT UPDATE DELETE',             'Aplicação em runtime (DB_USERNAME)'],
                [$migratorUser, 'SELECT INSERT UPDATE DELETE + DDL completo', 'Migrations apenas (DB_MIGRATE_USERNAME)'],
            ]
        );

        $this->newLine();
        $this->comment('Lembre-se: no Forge, use DB_MIGRATE_USERNAME apenas no Deploy Script:');
        $this->comment('  DB_USERNAME={$migratorUser} php artisan migrate --force');
        $this->comment('  # Após o migrate, o .env da app usa DB_USERNAME={$appUser}');

        return self::SUCCESS;
    }
}
