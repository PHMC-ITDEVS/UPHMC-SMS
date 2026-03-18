<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('')->nullable()->index();
            $table->string('table_id')->default('')->nullable()->index();
            $table->string('table')->default('')->nullable()->index();
            $table->string('original_name')->default('')->nullable();
            $table->string('size')->default('')->nullable();
            $table->string('mime_type')->default('')->nullable();
            $table->string('extension')->default('')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_uploads');
    }
}
