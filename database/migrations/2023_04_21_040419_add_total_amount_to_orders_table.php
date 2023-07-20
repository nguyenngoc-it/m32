<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalAmountToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('total_amount', 16, 3)->nullable()->comment('Tổng tiền đơn phải thanh toán');
            $table->double('order_amount', 16, 3)->nullable()->comment('Tổng tiền hàng');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'order_amount']);
        });
    }
}
