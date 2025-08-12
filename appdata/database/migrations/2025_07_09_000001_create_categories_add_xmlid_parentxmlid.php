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
        // Якщо таблиці ще немає — створюємо
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();

                // Унікальний ID категорії з XML
                $table->unsignedBigInteger('xml_id')->unique();

                // XML ID батьківської категорії (nullable)
                $table->unsignedBigInteger('parent_xml_id')->nullable();

                // Назва категорії
                $table->string('title');

                $table->timestamps();

                // Self-reference FK: parent_xml_id → xml_id тієї ж таблиці
                $table->foreign('parent_xml_id')
                    ->references('xml_id')
                    ->on('categories')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Спочатку прибираємо FK, потім дропаємо таблицю
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'parent_xml_id')) {
                $table->dropForeign(['parent_xml_id']);
            }
        });

        Schema::dropIfExists('categories');
    }
};
