<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table receptions
        if (Schema::hasTable('receptions')) {
            Schema::table('receptions', function (Blueprint $table) {
                // Si une colonne user_id avait été créée, on la renomme en created_by_id
                if (Schema::hasColumn('receptions', 'user_id') && !Schema::hasColumn('receptions', 'created_by_id')) {
                    $table->renameColumn('user_id', 'created_by_id');
                } elseif (Schema::hasColumn('receptions', 'gardien_id') && !Schema::hasColumn('receptions', 'created_by_id')) {
                    $table->renameColumn('gardien_id', 'created_by_id');
                } elseif (!Schema::hasColumn('receptions', 'created_by_id')) {
                    $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                }

                // validated_by_id (remplace secretaire_id / reception_id)
                if (Schema::hasColumn('receptions', 'secretaire_id') && !Schema::hasColumn('receptions', 'validated_by_id')) {
                    $table->renameColumn('secretaire_id', 'validated_by_id');
                } elseif (!Schema::hasColumn('receptions', 'validated_by_id')) {
                    $table->foreignId('validated_by_id')->nullable()->constrained('users')->nullOnDelete();
                }

                // repaired_by_id (remplace chef_atelier_id)
                if (Schema::hasColumn('receptions', 'chef_atelier_id') && !Schema::hasColumn('receptions', 'repaired_by_id')) {
                    $table->renameColumn('chef_atelier_id', 'repaired_by_id');
                } elseif (!Schema::hasColumn('receptions', 'repaired_by_id')) {
                    $table->foreignId('repaired_by_id')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        // 2. Table reparations
        if (Schema::hasTable('reparations')) {
            Schema::table('reparations', function (Blueprint $table) {
                if (Schema::hasColumn('reparations', 'chef_atelier_id') && !Schema::hasColumn('reparations', 'user_id')) {
                    $table->renameColumn('chef_atelier_id', 'user_id');
                }
            });
        }

        // 3. Table billets_sortie
        if (Schema::hasTable('billets_sortie')) {
            Schema::table('billets_sortie', function (Blueprint $table) {
                if (Schema::hasColumn('billets_sortie', 'chef_atelier_id') && !Schema::hasColumn('billets_sortie', 'user_id')) {
                    $table->renameColumn('chef_atelier_id', 'user_id');
                }
            });
        }

        // 4. Table factures
        if (Schema::hasTable('factures')) {
            Schema::table('factures', function (Blueprint $table) {
                if (Schema::hasColumn('factures', 'caissier_id') && !Schema::hasColumn('factures', 'user_id')) {
                    $table->renameColumn('caissier_id', 'user_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('factures')) {
            Schema::table('factures', function (Blueprint $table) {
                if (Schema::hasColumn('factures', 'user_id') && !Schema::hasColumn('factures', 'caissier_id')) {
                    $table->renameColumn('user_id', 'caissier_id');
                }
            });
        }

        if (Schema::hasTable('billets_sortie')) {
            Schema::table('billets_sortie', function (Blueprint $table) {
                if (Schema::hasColumn('billets_sortie', 'user_id') && !Schema::hasColumn('billets_sortie', 'chef_atelier_id')) {
                    $table->renameColumn('user_id', 'chef_atelier_id');
                }
            });
        }

        if (Schema::hasTable('reparations')) {
            Schema::table('reparations', function (Blueprint $table) {
                if (Schema::hasColumn('reparations', 'user_id') && !Schema::hasColumn('reparations', 'chef_atelier_id')) {
                    $table->renameColumn('user_id', 'chef_atelier_id');
                }
            });
        }

        if (Schema::hasTable('receptions')) {
            Schema::table('receptions', function (Blueprint $table) {
                if (Schema::hasColumn('receptions', 'created_by_id') && !Schema::hasColumn('receptions', 'gardien_id')) {
                    $table->renameColumn('created_by_id', 'gardien_id');
                }
                if (Schema::hasColumn('receptions', 'validated_by_id') && !Schema::hasColumn('receptions', 'secretaire_id')) {
                    $table->renameColumn('validated_by_id', 'secretaire_id');
                }
                if (Schema::hasColumn('receptions', 'repaired_by_id') && !Schema::hasColumn('receptions', 'chef_atelier_id')) {
                    $table->renameColumn('repaired_by_id', 'chef_atelier_id');
                }
            });
        }
    }
};