<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('acts', function (Blueprint $table) {
            $table->id();


            $table->string('type');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade')->onUpdate('cascade');


            $table->string('description');

            $table->index(['type', 'user_id', 'workspace_id', 'created_at']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('acts');
    }
};
