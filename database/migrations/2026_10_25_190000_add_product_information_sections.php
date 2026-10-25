<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The information tabs of the product page. how_to_use and ingredients already exist; these are the others:
     * the key ingredients ("Name : benefit / benefit", one per line), the precautions of use and the storage conditions.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('key_ingredients')->nullable()->after('ingredients');
            $table->text('precautions')->nullable()->after('key_ingredients');
            $table->text('storage_conditions')->nullable()->after('precautions');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['key_ingredients', 'precautions', 'storage_conditions']);
        });
    }
};
