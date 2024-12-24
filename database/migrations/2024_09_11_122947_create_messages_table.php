<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->text('translated_text')->nullable();
            $table->boolean('is_edited')->nullable();
            $table->boolean('is_pinned')->default(FALSE);

            $table->integer('reply_to')->nullable();

            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('chat_id')->nullable()->constrained()->onDelete('cascade')->onUpdate('cascade');


            $table->string('nonce_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('messages');
    }
};
