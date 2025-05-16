<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->string('causer');
            $table->foreign('causer')
                ->references('nickname')
                ->on('profiles')
                ->onDelete('cascade');
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
