<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('author');
            $table->foreign('author')
                ->references('nickname')->on('profiles')
                ->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->text('tags')->nullable();
            $table->string('thumbnail')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
