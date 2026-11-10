<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->mediumText('body');
            $table->string('status', 20)->default('draft');
            // Snapshot of the audience when the send starts, so subscribers who arrive later are never
            // included in a campaign that was already launched.
            $table->unsignedBigInteger('max_subscriber_id')->nullable();
            $table->unsignedBigInteger('last_subscriber_id')->nullable();
            $table->unsignedInteger('recipients_count')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
    }
};
