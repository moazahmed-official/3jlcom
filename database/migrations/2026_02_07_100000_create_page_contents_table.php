<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // about_us, privacy_policy, terms_conditions
            $table->string('title_en');
            $table->string('title_ar');
            $table->longText('body_en');
            $table->longText('body_ar');
            $table->timestamps();
        });

        // Seed default page content
        DB::table('page_contents')->insert([
            [
                'page_key' => 'about_us',
                'title_en' => 'About Us',
                'title_ar' => 'من نحن',
                'body_en' => 'Welcome to our platform. We are dedicated to providing the best service.',
                'body_ar' => 'مرحباً بكم في منصتنا. نحن ملتزمون بتقديم أفضل خدمة.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'page_key' => 'privacy_policy',
                'title_en' => 'Privacy Policy',
                'title_ar' => 'سياسة الخصوصية',
                'body_en' => 'Your privacy is important to us. This policy describes how we collect, use, and protect your data.',
                'body_ar' => 'خصوصيتك مهمة بالنسبة لنا. توضح هذه السياسة كيف نجمع بياناتك ونستخدمها ونحميها.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'page_key' => 'terms_conditions',
                'title_en' => 'Terms and Conditions',
                'title_ar' => 'الشروط والأحكام',
                'body_en' => 'By using our platform, you agree to the following terms and conditions.',
                'body_ar' => 'باستخدامك لمنصتنا، فإنك توافق على الشروط والأحكام التالية.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
