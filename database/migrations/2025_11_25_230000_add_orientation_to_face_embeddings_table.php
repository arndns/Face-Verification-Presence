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
        Schema::table('face__embeddings', function (Blueprint $table) {
            $table->string('orientation', 20)->default('front')->after('descriptor');
            $table->unique(['employee_id', 'orientation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('face__embeddings', function (Blueprint $table) {
            $table->dropUnique('face__embeddings_employee_id_orientation_unique');
            $table->dropColumn('orientation');
        });
    }
};
