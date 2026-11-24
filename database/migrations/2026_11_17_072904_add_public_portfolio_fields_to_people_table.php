<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('public_slug', 180)->nullable()->unique();
            $table->text('professional_bio')->nullable();
            $table->string('location', 160)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('github_url', 2048)->nullable();
            $table->json('other_social_links')->nullable();
        });

        DB::table('people')->select(['id', 'full_name'])->orderBy('id')->chunkById(100, function ($people): void {
            foreach ($people as $person) {
                $base = Str::slug($person->full_name) ?: 'person';
                $slug = $base.'-'.Str::lower(Str::random(8));

                while (DB::table('people')->where('public_slug', $slug)->exists()) {
                    $slug = $base.'-'.Str::lower(Str::random(8));
                }

                DB::table('people')->where('id', $person->id)->update(['public_slug' => $slug]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique(['public_slug']);
            $table->dropColumn(['public_slug', 'professional_bio', 'location', 'website_url', 'linkedin_url', 'github_url', 'other_social_links']);
        });
    }
};
