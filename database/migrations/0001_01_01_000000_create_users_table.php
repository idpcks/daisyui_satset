<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('username', 50);
            $table->string('email', 255);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->string('deleted_by', 50)->nullable();
            $table->string('avatar', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Partial unique index agar email/username user soft-deleted dapat
        // dipakai ulang oleh user aktif. Driver yang tidak didukung sengaja
        // gagal dengan pesan jelas, sama seperti migration asalnya.
        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'pgsql' => (function (): void {
                DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
                DB::statement('CREATE UNIQUE INDEX users_username_active_unique ON users (username) WHERE deleted_at IS NULL');
            })(),
            'sqlite' => (function (): void {
                DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
                DB::statement('CREATE UNIQUE INDEX users_username_active_unique ON users (username) WHERE deleted_at IS NULL');
            })(),
            default => throw new RuntimeException("Migration ini belum mendukung driver {$driver}."),
        };

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
