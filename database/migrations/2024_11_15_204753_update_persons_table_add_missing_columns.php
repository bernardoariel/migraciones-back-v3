<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePersonsTableAddMissingColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->foreignId('authorizing_relatives_id')->nullable()->after('updated_at');
            $table->foreignId('accreditation_links_id')->nullable()->after('authorizing_relatives_id');
            $table->foreignId('nationality_id')->nullable()->after('accreditation_links_id');
            $table->foreignId('issuer_document_id')->nullable()->after('nationality_id');
            $table->date('fecha_de_nacimiento')->nullable()->after('issuer_document_id');
            $table->foreignId('sex_id')->nullable()->after('fecha_de_nacimiento');
            $table->string('domicilio', 100)->nullable()->after('sex_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn([
                'authorizing_relatives_id',
                'accreditation_links_id',
                'nationality_id',
                'issuer_document_id',
                'fecha_de_nacimiento',
                'sex_id',
                'domicilio',
            ]);
        });
    }
}
