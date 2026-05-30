<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('access_pass_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_zone_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('vmserver_user_id')->nullable()->index();
            $table->string('dni')->nullable()->index();

            $table->string('direction')->default('in');

            $table->boolean('allowed')->default(false);

            $table->string('reason')->nullable()->index();

            $table->string('scanner_device_id')->nullable();
            $table->string('scanned_by')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('decision_payload')->nullable();

            $table->timestamp('scanned_at')->useCurrent();

            $table->timestamps();

            $table->index(['access_zone_id', 'scanned_at']);
            $table->index(['vmserver_user_id', 'scanned_at']);
            $table->index(['allowed', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
