<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_books_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('realm_id');
            $table->string('environment')->default('sandbox');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamps();
            $table->unique(['realm_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_books_connections');
    }
};
