<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('log_type')->default('model'); // 'model' o 'manual'
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
