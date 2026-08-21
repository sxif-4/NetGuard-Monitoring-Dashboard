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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('snmp_community')->nullable()->default('public')->after('monitoring_port');
            $table->string('snmp_version')->nullable()->default('2c')->after('snmp_community');
            $table->unsignedInteger('snmp_port')->nullable()->default(161)->after('snmp_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['snmp_community', 'snmp_version', 'snmp_port']);
        });
    }
};
