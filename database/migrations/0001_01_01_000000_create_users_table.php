<?php

use App\Enums\UserPlan;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('avatar_path')->nullable();
            $table->timestamp('date_of_birth')->nullable();

            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('company_name')->nullable();

            $table->string('locale', 5)->default('ru');
            $table->string('timezone')->default('UTC');

            $table->string('role', 20)->default('player');
            $table->string('plan', 20)->default('starter');
            $table->timestamp('subscribed_until')->nullable();
            $table->string('robokassa_sub_id')->nullable();

            $table->boolean('is_onboarded')->default(false);

            $table->boolean('notifications_enabled')->default(false);

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

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'nickname' => 'Admin',
                'password' => Hash::make('Kk1cFfUWnTSuxHh'),
                'role' => UserRole::ADMIN,
                'plan' => UserPlan::STARTER,
                'is_onboarded' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
