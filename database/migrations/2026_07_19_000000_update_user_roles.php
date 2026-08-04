<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Temporarily expand enum values to contain both old and new roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'gardien', 'secretaire', 'chef_atelier', 'caisse', 'admin', 'reception', 'caisse_outils') NOT NULL");

        // 2. Update existing users to new roles
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'chef_atelier')->update(['role' => 'caisse_outils']);
        DB::table('users')->where('role', 'secretaire')->update(['role' => 'reception']);

        // 3. Set the final enum structure restricting to new roles only
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'gardien', 'reception', 'caisse_outils', 'caisse') NOT NULL DEFAULT 'gardien'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. Expand enum back
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'gardien', 'secretaire', 'chef_atelier', 'caisse', 'admin', 'reception', 'caisse_outils') NOT NULL DEFAULT 'gardien'");

        // 2. Rollback users
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'caisse_outils')->update(['role' => 'chef_atelier']);
        DB::table('users')->where('role', 'reception')->update(['role' => 'secretaire']);

        // 3. Restore original enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'gardien', 'secretaire', 'chef_atelier', 'caisse') NOT NULL");
    }
};
