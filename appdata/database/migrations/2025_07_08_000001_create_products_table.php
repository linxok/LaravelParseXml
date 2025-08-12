<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 return new class extends Migration {
     public function up(): void
     {
         Schema::create('products', function (Blueprint $table) {
             $table->id();
             $table->string('xml_id')->unique();  // унікальний id з XML
             $table->string('name');
             $table->decimal('price', 12, 2);
             $table->text('description')->nullable();
             $table->timestamps();
         });
     }

     public function down(): void
     {
         Schema::dropIfExists('products');
     }
 };
