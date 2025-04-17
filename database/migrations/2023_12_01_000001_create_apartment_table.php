<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('square_feet');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code', 20);
            $table->enum('status', ['available', 'rented', 'sold'])->default('available');
            $table->enum('type', ['apartment', 'condo', 'studio']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_featured')->default(false);
            $table->json('amenities')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('year_built')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better search performance
            $table->index('status');
            $table->index('type');
            $table->index('is_featured');
            $table->index('is_published');
            $table->index(['city', 'state']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
