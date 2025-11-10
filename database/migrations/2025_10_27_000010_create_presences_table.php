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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->dateTime('waktu_masuk')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('longitude_masuk')->nullable();
            $table->string('latitude_masuk')->nullable();
            
            $table->dateTime('waktu_pulang')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->string('longitude_pulang')->nullable();
            $table->string('latitude_pulang')->nullable();

            $table->enum('status', ['Tepat Waktu', 'Terlambat'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
