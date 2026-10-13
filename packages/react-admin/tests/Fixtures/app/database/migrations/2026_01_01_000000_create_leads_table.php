<?php

declare(strict_types=1);

use Slendie\Database\Migrations\Migration;
use Slendie\Database\Schema\Blueprint;
use Slendie\Support\Facades\Schema;

// Igual à do skeleton (sem a coluna `notes`, que é acrescentada pela migração do package).
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
