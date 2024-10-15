<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('active')->default(TRUE);
            $table->string('status')->nullable();
            $table->string('bio')->nullable();
            $table->string('avatar', 4096)->nullable();

            $table->integer('workspace_id')->nullable();
            $table->integer('room_id')->nullable();
            $table->string('voice_status')->nullable();
            $table->string('video_status')->nullable();
            $table->string('coordinates')->nullable();
            $table->string('screenshare_coordinates')->nullable();
            $table->string('screenshare_size')->nullable();
            $table->string('video_coordinates')->nullable();
            $table->string('video_size')->nullable();
            $table->boolean('is_megaphone')->default(FALSE);


            $table->string('socket_id')->nullable();
            $table->boolean('verified')->default(FALSE);
            $table->boolean('is_bot')->default(FALSE);
            $table->boolean('livekit_connected')->default(FALSE);


            $table->integer('active_job_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
