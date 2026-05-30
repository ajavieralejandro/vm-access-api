<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_passes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('access_zone_id')->constrained()->cascadeOnDelete();

            $table->string('code')->unique();

            $table->unsignedBigInteger('vmserver_user_id')->nullable()->index();
            $table->string('dni')->nullable()->index();
            $table->string('holder_name')->nullable();

            $table->string('source_service')->nullable()->index();
            $table->string('source_type')->nullable()->index();
            $table->string('source_reference')->nullable()->index();

            $table->string('status')->default('active')->index();

            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['access_zone_id', 'status']);
            $table->index(['source_service', 'source_type', 'source_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_passes');
    }
};
