<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->string('type')->default('per_hour');

            $table->float('amount');
            $table->string('currency')->default('USDT');

            $table->timestamp('start_at');
            $table->timestamp('end_at');

            $table->boolean('auto_renewal')->default(TRUE);
            $table->integer('renewal_count')->default(0);
            $table->string('renew_time_period_type')->default('month');
            $table->integer('renew_time_period')->default(1);
            $table->integer('renew_notice')->default(10);


            $table->string('user_status')->default('will_renew');
            $table->string('contractor_status')->default('will_renew');

            $table->integer('min_hours')->default(40);
            $table->integer('max_hours')->default(250);

            $table->string('payment_method')->default('trc20');
            $table->string('payment_address')->nullable();

            $table->string('payment_period')->default('monthly');
            $table->string('role')->nullable();
            $table->boolean('user_sign_status')->default(FALSE);
            $table->boolean('contractor_sign_status')->default(FALSE);

            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade')->onUpdate('cascade');

            $table->boolean('in_schedule')->default(FALSE);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('contracts');
    }
};
