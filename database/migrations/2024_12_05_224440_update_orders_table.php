<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Agregar las columnas que faltan
            $table->string('tipo_acompaniante', 1)->nullable()->after('paises_desc');
            $table->string('descripcion_acompaniante', 500)->nullable()->after('tipo_acompaniante');

            // Asegurar que las columnas requeridas existan
            if (!Schema::hasColumn('orders', 'serie_foja')) {
                $table->string('serie_foja', 25)->after('cualquier_pais');
            }
            if (!Schema::hasColumn('orders', 'tipo_foja')) {
                $table->string('tipo_foja', 25)->after('serie_foja');
            }
            if (!Schema::hasColumn('orders', 'nro_foja')) {
                $table->string('nro_foja', 50)->after('tipo_foja');
            }

            // Asegurar que columnas opcionales permitan valores nulos
            $table->text('aprobacion')->nullable()->change();
            $table->string('paises_desc', 191)->nullable()->change();
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
            // Eliminar las columnas agregadas
            $table->dropColumn('tipo_acompaniante');
            $table->dropColumn('descripcion_acompaniante');

            if (Schema::hasColumn('orders', 'serie_foja')) {
                $table->dropColumn('serie_foja');
            }
            if (Schema::hasColumn('orders', 'tipo_foja')) {
                $table->dropColumn('tipo_foja');
            }
            if (Schema::hasColumn('orders', 'nro_foja')) {
                $table->dropColumn('nro_foja');
            }

            // Revertir columnas opcionales a no permitir nulos
            $table->text('aprobacion')->nullable(false)->change();
            $table->string('paises_desc', 191)->nullable(false)->change();
        });
    }
}
