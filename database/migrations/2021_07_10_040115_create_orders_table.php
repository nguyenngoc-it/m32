<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('application_id');
            $table->integer('shipping_partner_id')->index()->comment('ID đối tác vận chuyển');
            $table->string('shipping_partner_code')->comment('Mã đối tác tích hợp vận chuyển');
            $table->string('shipping_carrier_code')->comment('Mã đơn vị vận chuyển');
            $table->string('ref')->index()->comment('Reference code')->nullable();
            $table->string('code')->nullable()->index()->comment('Mã đơn từ đối tác');
            $table->string('tracking_no')->nullable()->index()->comment('Mã vận đơn từ đối tác');
            $table->double('fee', 16, 3)->nullable()->comment('Phí vận chuyển từ đối tác');
            $table->double('cod', 16, 3)->nullable()->comment('COD amount');
            $table->double('weight', 8, 3)->nullable()->comment('Cân nặng (kg)');
            $table->double('length', 8, 3)->nullable()->comment('Chiều dài (m)');
            $table->double('width', 8, 3)->nullable()->comment('Chiều dài (m)');
            $table->double('height', 8, 3)->nullable()->comment('Chiều dài (m)');
            $table->double('volume', 16, 9)->nullable()->comment('Thể tích (m3)');
            $table->string('sender_name')->nullable()->comment('Tên người gửi');
            $table->string('sender_phone')->nullable()->index()->comment('Số điện thoại gửi');
            $table->string('sender_province_code')->nullable()->index()->comment('Mã tỉnh thành gửi');
            $table->string('sender_district_code')->nullable()->index()->comment('Mã quận huyện gửi');
            $table->string('sender_ward_code')->nullable()->index()->comment('Mã phường xã gửi');
            $table->string('sender_address')->nullable()->comment('Địa chỉ nhận chi tiết');
            $table->string('receiver_name')->nullable()->comment('Tên người nhận');
            $table->string('receiver_phone')->nullable()->index()->comment('Số điện thoại nhận');
            $table->string('receiver_province_code')->nullable()->index()->comment('Mã tỉnh thành nhận');
            $table->string('receiver_district_code')->nullable()->index()->comment('Mã quận huyện nhận');
            $table->string('receiver_ward_code')->nullable()->index()->comment('Mã phường xã nhận');
            $table->string('receiver_address')->nullable()->comment('Địa chỉ nhận chi tiết');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->string('status')->comment('Trạng thái đơn');
            $table->string('original_status')->nullable()->comment('Trạng thái đơn vận chuyển bên đối tác');
            $table->timestamps();

            $table->unique(['application_id', 'ref']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
