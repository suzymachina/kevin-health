<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_workouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->float('duration')->nullable();
            $table->float('calories')->nullable();
            $table->float('distance')->nullable();
            $table->text('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['name', 'start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_workouts');
    }
};
