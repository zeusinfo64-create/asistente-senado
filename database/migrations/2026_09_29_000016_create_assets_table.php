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
        Schema::create('assets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('asset_code', 50);
            $table->string('serial_number', 100)->nullable();
            $table->unsignedBigInteger('asset_type_id');
            $table->unsignedBigInteger('asset_state_id');
            $table->string('brand', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('organizational_unit_id')->nullable();
            $table->string('location', 150)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_until')->nullable();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('decommissioned_at')->nullable();
            $table->timestamps();

            $table->unique('asset_code');
            $table->index('serial_number');
            $table->index('mac_address');
            $table->index('warranty_until');
            $table->index('asset_type_id');
            $table->index('asset_state_id');
            $table->index('assigned_user_id');
            $table->index('organizational_unit_id');

            $table->foreign('asset_type_id')->references('id')->on('asset_types')->restrictOnDelete();
            $table->foreign('asset_state_id')->references('id')->on('asset_states')->restrictOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('organizational_unit_id')->references('id')->on('organizational_units')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
