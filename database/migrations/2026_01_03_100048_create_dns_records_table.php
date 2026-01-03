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
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained()->onDelete('cascade');
            $table->string('type'); // A, AAAA, CNAME, MX, TXT, NS
            $table->string('name'); // @, www, mail
            $table->string('value');
            $table->integer('priority')->nullable();
            $table->integer('ttl')->default(3600);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
