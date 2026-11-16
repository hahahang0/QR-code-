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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->longText('payload');
            $table->json('included_fields');
            $table->char('error_correction', 1)->default('Q');
            $table->string('signature_algorithm', 32)->nullable();
            $table->text('signature')->nullable();
            $table->string('public_key_id', 80)->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->timestamps();

            $table->index(['person_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
