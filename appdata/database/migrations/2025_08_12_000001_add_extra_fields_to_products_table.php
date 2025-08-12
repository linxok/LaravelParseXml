<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('available')->default(true);
            $table->unsignedBigInteger('category_xml_id')->nullable()->index();
            $table->string('currency', 8)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->text('description_format')->nullable();
            $table->string('vendor')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('barcode')->nullable();
            $table->json('pictures')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'available',
                'category_xml_id',
                'currency',
                'stock_quantity',
                'description_format',
                'vendor',
                'vendor_code',
                'barcode',
                'pictures',
            ]);
        });
    }
};
