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
        Schema::create('oauth_mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('microsoft');
            $table->string('email');
            $table->text('access_token')->nullable();
            $table->text('refresh_token');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['provider', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_mail_accounts');
    }
};
