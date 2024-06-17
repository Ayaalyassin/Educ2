<?php

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
        Schema::create('profile_student_ads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('profile_student_id')->unsigned();
            $table->foreign('profile_student_id')->references('id')->on('profile_students')->onDelete('cascade');

            $table->bigInteger('ads_id')->unsigned();
            $table->foreign('ads_id')->references('id')->on('ads')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_student_ads');
    }
};
