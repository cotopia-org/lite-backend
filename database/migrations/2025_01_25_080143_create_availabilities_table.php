<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('workspace_id')->constrained('workspaces');
            $table->foreignId('contract_id')->nullable()->constrained('users');

            $table->string('type')->nullable();


            $table->timestamp('start_at');
            $table->timestamp('end_at');


            $table->string('timezone')->default('Asia/Tehran');


            $table->string('title')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('availabilities');
    }
};
