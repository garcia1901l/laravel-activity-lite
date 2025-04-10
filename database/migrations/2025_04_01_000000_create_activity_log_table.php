<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            // Cambiado a bigIncrements para máxima compatibilidad
            $table->bigIncrements('id');
            
            $table->string('action', 50);
            $table->string('log_type', 20)->default('model');
            $table->string('model_type', 150)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('causer_type', 150)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            // Índices optimizados
            $table->index(['model_type', 'model_id'], 'activity_logs_model_index');
            $table->index(['causer_type', 'causer_id'], 'activity_logs_causer_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}