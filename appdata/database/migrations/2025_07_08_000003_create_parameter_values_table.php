<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parameter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
            $table->unique(['parameter_id','value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_values');
    }
};
