<?php

use App\Models\Factory;
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
        Schema::create('factory_prices', function (Blueprint $table) {
            $table->uuid('id')->primary()->index();
            $table->foreignIdFor(Factory::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->date('date');
            $table->float('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factory_prices');
    }
};
