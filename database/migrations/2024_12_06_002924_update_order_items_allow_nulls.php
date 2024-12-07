<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrderItemsAllowNulls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('authorizing_relatives_id')->nullable()->change();
            $table->integer('accreditation_links_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('authorizing_relatives_id')->nullable(false)->change();
            $table->integer('accreditation_links_id')->nullable(false)->change();
        });
    }
}
