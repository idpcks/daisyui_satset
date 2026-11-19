<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email dan username yang dipakai user ter-soft-delete tidak boleh
     * mengunci nilai tersebut selamanya. Unique constraint diganti dengan
     * partial unique index yang hanya berlaku untuk baris aktif.
     */
    public function up(): void
    {
        $this->assertNoDuplicateActiveIdentifiers();

        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'pgsql' => $this->forPostgres(),
            'sqlite' => $this->forSqlite(),
            default => throw new RuntimeException(
                "Migration ini belum mendukung driver {$driver}."
            ),
        };
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'pgsql' => $this->undoPostgres(),
            'sqlite' => $this->undoSqlite(),
            default => throw new RuntimeException(
                "Migration ini belum mendukung driver {$driver}."
            ),
        };
    }

    /**
     * Partial index akan menolak duplikat di antara baris aktif. Lebih baik
     * gagal dengan pesan yang jelas daripada error SQL saat migrasi.
     */
    private function assertNoDuplicateActiveIdentifiers(): void
    {
        foreach (['email', 'username'] as $column) {
            $duplicate = DB::table('users')
                ->whereNull('deleted_at')
                ->select($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->value($column);

            throw_if(
                $duplicate !== null,
                RuntimeException::class,
                "Tidak bisa membuat partial index: {$column} '{$duplicate}' digunakan lebih dari satu user aktif.",
            );
        }
    }

    private function forPostgres(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_email_unique');
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_username_unique');

        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_username_active_unique ON users (username) WHERE deleted_at IS NULL');
    }

    private function undoPostgres(): void
    {
        DB::statement('DROP INDEX users_email_active_unique');
        DB::statement('DROP INDEX users_username_active_unique');

        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_username_unique UNIQUE (username)');
    }

    private function forSqlite(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_username_unique');
        });

        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_username_active_unique ON users (username) WHERE deleted_at IS NULL');
    }

    private function undoSqlite(): void
    {
        DB::statement('DROP INDEX users_email_active_unique');
        DB::statement('DROP INDEX users_username_active_unique');

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email', 'users_email_unique');
            $table->unique('username', 'users_username_unique');
        });
    }
};
