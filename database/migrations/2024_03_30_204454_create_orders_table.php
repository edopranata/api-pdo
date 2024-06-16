<?php

use App\Models\Customer;
use App\Models\Factory;
use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->index();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Factory::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Customer::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->date('trade_date');

            $table->double('net_weight')->default(0)->comment('Berat bersih timbangan pabrik (kg)');
            $table->double('net_price')->default(0)->comment('Harga beli pabrik (Rp)');
            $table->double('margin')->default(0)->comment('Margin / pendapatan DO (Rp)');
            $table->double('ppn_tax')->default(0)->comment('PPN Percent');
            $table->double('pph22_tax')->default(0)->comment('PPh 22 Percent');
            $table->double('ppn_total')->default(0)->comment('Rp. PPN');
            $table->double('pph22_total')->default(0)->comment('Rp. PPh 22');
            $table->double('gross_total')->default(0)->comment('Pendapatan Kotor  (Rp. Berat * Harga Pabrik)');
            $table->double('net_total')->default(0)->comment('Pendapatan Bersih (Rp. Berat * Margin)');
            $table->double('customer_price')->default(0)->comment('Harga jual customer (Net Price * Margin)');
            $table->double('customer_total')->default(0)->comment('Total terima customer (Customer Price * Net Weight)');
            $table->dateTime('invoice_status')->nullable()->comment('Tanggal invoice');
            $table->dateTime('income_status')->nullable()->comment('Tanggal uang masuk dari pabrik');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
