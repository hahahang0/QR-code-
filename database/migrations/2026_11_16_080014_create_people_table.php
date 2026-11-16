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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 120);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 40)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 254)->nullable()->index();
            $table->string('address', 500)->nullable();
            $table->string('organization', 160)->nullable()->index();
            $table->string('reference_number', 80)->nullable()->unique();
            $table->string('emergency_contact', 80)->nullable();
            $table->text('notes')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
