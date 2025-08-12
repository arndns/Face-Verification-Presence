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
        Schema::create('presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('locations_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->timestamp('masuk');
            $table->decimal('masuk_latitude', 10, 8)->nullable();
            $table->decimal('masuk_longitude', 11, 8)->nullable();
            $table->timestamp('pulang');
            $table->date('tanggal');
            $table->decimal('pulang_latitude', 10, 8)->nullable();
            $table->decimal('pulang_longitude', 11, 8)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            
            
            

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence');
    }
};
