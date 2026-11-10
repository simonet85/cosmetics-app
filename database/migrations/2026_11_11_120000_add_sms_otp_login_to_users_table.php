<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Canonical E.164 number: the only value the SMS login looks up. It stays NULL until the
            // customer proves the number with a one-time code, so the unique index is safe to add now.
            $table->string('phone_normalized', 20)->nullable()->unique()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_normalized']);
            $table->dropColumn(['phone_normalized', 'phone_verified_at']);
        });
    }
};
