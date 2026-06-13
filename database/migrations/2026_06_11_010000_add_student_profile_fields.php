<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('father_name')->nullable()->after('gender');
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('father_whatsapp', 25)->nullable()->after('mother_name');
            $table->string('mother_whatsapp', 25)->nullable()->after('father_whatsapp');
            $table->string('province')->nullable()->after('mother_whatsapp');
            $table->string('city')->nullable()->after('province');
            $table->string('district')->nullable()->after('city');
            $table->string('village')->nullable()->after('district');
            $table->text('address')->nullable()->after('village');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'birth_place', 'birth_date', 'father_name', 'mother_name',
                'father_whatsapp', 'mother_whatsapp', 'province', 'city',
                'district', 'village', 'address',
            ]);
        });
    }
};
