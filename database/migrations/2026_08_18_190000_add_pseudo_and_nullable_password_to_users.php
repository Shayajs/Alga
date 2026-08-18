<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pseudo')->nullable()->after('name');
            $table->string('password')->nullable()->change();
            $table->string('pin')->nullable()->change();
        });

        User::query()->orderBy('id')->each(function (User $user): void {
            if (blank($user->pseudo)) {
                $user->forceFill([
                    'pseudo' => Str::lower($user->name),
                ])->save();
            }
        });

        DB::table('users')->update(['password' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('pseudo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pseudo']);
            $table->dropColumn('pseudo');
            $table->string('password')->nullable(false)->change();
            $table->string('pin')->nullable(false)->change();
        });
    }
};
