<?php

declare(strict_types=1);

use Slendie\Database\Migrations\Migration;
use Slendie\Database\Schema\Blueprint;
use Slendie\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'notes')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'notes')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropColumn('notes');
            });
        }
    }
};
