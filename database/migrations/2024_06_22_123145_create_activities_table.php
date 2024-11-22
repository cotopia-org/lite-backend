<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->timestamp('join_at')->default(now());
            $table->timestamp('left_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('no action')->onUpdate('no action');
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('no action')->onUpdate('no action');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('no action')->onUpdate('no action');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('no action')->onUpdate('no action');


            $table->longText('data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('activities');
    }
};
