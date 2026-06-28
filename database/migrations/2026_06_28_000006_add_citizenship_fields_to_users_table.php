<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'citizenship_type')) {
                $table->string('citizenship_type')->default(User::CITIZENSHIP_RUSSIAN)->after('schedule_type');
            }

            if (! Schema::hasColumn('users', 'citizenship_country')) {
                $table->string('citizenship_country')->nullable()->after('citizenship_type');
            }

            if (! Schema::hasColumn('users', 'arrival_country')) {
                $table->string('arrival_country')->nullable()->after('citizenship_country');
            }

            if (! Schema::hasColumn('users', 'arrived_at')) {
                $table->date('arrived_at')->nullable()->after('arrival_country');
            }

            if (! Schema::hasColumn('users', 'foreign_legal_status')) {
                $table->string('foreign_legal_status')->nullable()->after('arrived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'citizenship_type',
                'citizenship_country',
                'arrival_country',
                'arrived_at',
                'foreign_legal_status',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
