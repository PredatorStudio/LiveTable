<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('live-table.persist_state_table', 'live_table_states');

        Schema::create($table, function (Blueprint $table): void {
            $table->id();
            $table->string('table_id', 191);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('client_id', 36)->nullable()->index();
            $table->json('state');
            $table->timestamps();

            $table->index(['table_id', 'user_id']);
            $table->index(['table_id', 'client_id']);
        });
    }

    public function down(): void
    {
        $table = config('live-table.persist_state_table', 'live_table_states');

        Schema::dropIfExists($table);
    }
};
