<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->nullable()->after('email');
            $table->string('branch')->nullable()->after('company');
            $table->boolean('is_active')->default(true)->after('branch');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('erpnext_name')->unique();
            $table->string('abbr', 10)->nullable();
            $table->string('default_currency', 10)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('erpnext_name')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dashboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('dashboard_type', 50);
            $table->string('company')->nullable();
            $table->string('branch')->nullable();
            $table->date('snapshot_date');
            $table->json('kpi_data');
            $table->json('chart_data')->nullable();
            $table->json('table_data')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['dashboard_type', 'snapshot_date', 'company']);
        });

        Schema::create('daily_closing', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date');
            $table->string('company');
            $table->string('branch')->nullable();
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('receipts', 18, 2)->default(0);
            $table->decimal('payments', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->decimal('bank_balance', 18, 2)->default(0);
            $table->decimal('cash_in_hand', 18, 2)->default(0);
            $table->decimal('pending_deposits', 18, 2)->default(0);
            $table->decimal('outstanding_cheques', 18, 2)->default(0);
            $table->decimal('daily_profit_loss', 18, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['closing_date', 'company', 'branch']);
        });

        Schema::create('executive_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('severity', 20)->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_read']);
        });

        Schema::create('dashboard_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->string('dashboard_type', 50);
            $table->json('payload');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['dashboard_type', 'expires_at']);
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 50);
            $table->string('format', 20)->default('pdf');
            $table->string('frequency', 20)->default('daily');
            $table->time('delivery_time')->default('08:00:00');
            $table->json('recipients');
            $table->json('filters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('default_company')->nullable();
            $table->string('default_branch')->nullable();
            $table->string('theme', 20)->default('dark');
            $table->string('currency', 10)->nullable();
            $table->json('widget_layout')->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('dashboard_preferences');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('dashboard_cache');
        Schema::dropIfExists('executive_notifications');
        Schema::dropIfExists('daily_closing');
        Schema::dropIfExists('dashboard_snapshots');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company', 'branch', 'is_active', 'last_login_at']);
        });
    }
};
