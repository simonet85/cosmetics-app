<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Translations of catalogue text (products, categories, banners, tags). The French text stays on the model itself.
     * source_hash is the md5 of the French text a machine translation was made from: when the French changes, the
     * translation is stale and the page shows French again until `translations:sync` renews it. A null hash marks a
     * translation typed by hand, which is never replaced.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('field', 50);
            $table->string('locale', 10);
            $table->text('value');
            $table->char('source_hash', 32)->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'field', 'locale'], 'translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
