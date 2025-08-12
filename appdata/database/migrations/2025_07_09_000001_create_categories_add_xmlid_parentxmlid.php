<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // унікальний id з XML
            if (! Schema::hasColumn('categories', 'xml_id')) {
                $table->unsignedBigInteger('xml_id')->unique()->after('id');
            }
            // посилання на xml_id батьківської категорії
            if (! Schema::hasColumn('categories', 'parent_xml_id')) {
                $table->unsignedBigInteger('parent_xml_id')->nullable()->after('xml_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['xml_id', 'parent_xml_id']);
        });
    }
};
