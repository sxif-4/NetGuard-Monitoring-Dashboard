<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->boolean('reachable');
            $table->decimal('latency_ms', 8, 2)->nullable();
            $table->decimal('packet_loss_pct', 5, 2)->nullable();
            $table->decimal('cpu_pct', 5, 2)->nullable();
            $table->decimal('memory_pct', 5, 2)->nullable();
            $table->decimal('bandwidth_pct', 5, 2)->nullable();
            $table->unsignedBigInteger('bandwidth_in_bps')->nullable();
            $table->unsignedBigInteger('bandwidth_out_bps')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('polled_at');
            $table->timestamps();

            $table->index(['device_id', 'polled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_metrics');
    }
};
