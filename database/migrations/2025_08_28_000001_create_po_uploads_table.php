<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('po_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 80)->nullable()->index();
            $table->string('file_path');   // path di storage
            $table->string('file_url');    // URL publik
            $table->enum('status', ['OK','NOT_OK']);
            $table->json('n8n_response')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('po_uploads');
    }
};
