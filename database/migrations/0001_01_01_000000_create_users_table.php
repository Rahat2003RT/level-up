<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('role')->nullable();
            $table->json('name');
            $table->json('description')->nullable();
            $table->decimal('price', 10);
            $table->string('period');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('account_id', 10)->unique()->nullable();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('avatar_path')->nullable();
            $table->string('date_of_birth')->nullable();

            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('company_name')->nullable();

            $table->string('locale', 5)->default('ru');
            $table->string('timezone')->default('UTC');

            $table->string('role', 20)->nullable();

            $table->boolean('notifications_enabled')->default(false);

            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_recurrent_id')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->string('block_reason')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code');
            $table->timestamp('created_at')->nullable();
        });

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'nickname' => 'Admin',
                'password' => Hash::make('Kk1cFfUWnTSuxHh'),
                'role'     => UserRole::ADMIN,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_codes');
    }
};
