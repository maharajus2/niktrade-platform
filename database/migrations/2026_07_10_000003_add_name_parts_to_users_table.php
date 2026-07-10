<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('patronymic')->nullable();
            $table->boolean('no_patronymic')->default(false);
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function (object $user): void {
                $parts = $this->splitFullName((string) $user->name);
                $fullName = collect([
                    $parts['last_name'],
                    $parts['first_name'],
                    $parts['patronymic'],
                ])->filter(fn (?string $part): bool => filled($part))->implode(' ');

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'name' => $fullName ?: $user->name,
                        'last_name' => $parts['last_name'],
                        'first_name' => $parts['first_name'],
                        'patronymic' => $parts['patronymic'],
                        'no_patronymic' => blank($parts['patronymic']),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'last_name',
                'first_name',
                'patronymic',
                'no_patronymic',
            ]);
        });
    }

    /**
     * @return array{last_name: ?string, first_name: ?string, patronymic: ?string}
     */
    private function splitFullName(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalized = mb_strtolower(implode(' ', $words));

        if (in_array($normalized, ['елена алёшина', 'елена алешина'], true)) {
            return [
                'last_name' => $words[1] ?? null,
                'first_name' => $words[0] ?? null,
                'patronymic' => null,
            ];
        }

        if (count($words) === 1) {
            return [
                'last_name' => null,
                'first_name' => $words[0],
                'patronymic' => null,
            ];
        }

        return [
            'last_name' => $words[0] ?? null,
            'first_name' => $words[1] ?? null,
            'patronymic' => count($words) >= 3 ? implode(' ', array_slice($words, 2)) : null,
        ];
    }
};
