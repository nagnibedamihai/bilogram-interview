<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('record_id')->unique()->index();
            $table->dateTime('time')->index();
            $table->string('source_id')->index();
            $table->string('destination_id')->index();
            $table->enum('type', ['positive', 'negative']);
            $table->decimal('value', 15, 2);
            $table->string('unit');
            $table->string('reference')->index();
            $table->timestamps();

            // Composite index for common query patterns
            $table->index(['destination_id', 'reference']);
            $table->index(['destination_id', 'type']);
            $table->index(['time', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
