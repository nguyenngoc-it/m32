<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCollectedCodColumnToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('collected_cod')->nullable()->comment('Cod thực thu');
            $table->double('goods_value')->nullable()->comment('Giá trị đơn hàng');
            $table->boolean('paid_by_customer')->default(true)->comment("Đánh đấu khách trả phí");
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
            $table->dropColumn('collected_cod');
            $table->dropColumn('goods_value');
            $table->dropColumn('paid_by_customer');
        });
    }
}
