<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->dateTime('date');
            $table->float('qty')->nullable();
            $table->string('units')->nullable();
            $table->string('source')->nullable();
            $table->text('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['metric_name', 'date', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
};
