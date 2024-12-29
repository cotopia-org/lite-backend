<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->string('availability_type');
            $table->string('days');

            $table->foreignId('user_id')->constrained('users');
            $table->time('start_time')->default('08:00:00');
            $table->time('end_time')->default('18:00:00');


            $table->boolean('is_recurrence')->default(FALSE);

            $table->timestamp('recurrence_start_at')->default(now());
            $table->timestamp('recurrence_end_at')->nullable();
            $table->string('timezone')->default('Asia/Tehran');


            $table->foreignId('workspace_id')->constrained('workspaces');

            $table->foreignId('contract_id')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('schedules');
    }
};
