<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEloquentyTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('eloquenty_entries', function (Blueprint $table) {
            $table->string('id');
            $table->string('site');
            $table->string('origin_id')->nullable();
            $table->boolean('published')->default(true);
            $table->string('status');
            $table->string('slug');
            $table->string('uri')->nullable();
            $table->string('date')->nullable();
            $table->string('collection');
            $table->json('data');
            $table->timestamps();
        });

        Schema::table('eloquenty_entries', function (Blueprint $table) {
            $table->primary('id');
            $table->unique(['slug', 'site']);
            // These indexes improve performance
            $table->index('status');
            $table->index('uri');
            $table->index('date');
            $table->index('collection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('eloquenty_entries');
    }
}
