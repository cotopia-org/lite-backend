<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', \App\Models\Job::STATUSES)->default('in_progress');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            $table->integer('duration')->default(0);

            $table->integer('estimate');
            $table->foreignId('workspace_id');


            $table->integer('job_id')->nullable();
            // TODO - Foreign key constraint is incorrectly formed
//            $table->foreignId('workspace_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
//            $table->foreignId('job_id')->constrained()->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
