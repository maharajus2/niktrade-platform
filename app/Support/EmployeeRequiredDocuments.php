<?php

namespace App\Support;

use App\Models\User;

class EmployeeRequiredDocuments
{
    private const CATEGORIES = [
        'passport' => 'Паспорт',
        'snils' => 'СНИЛС',
        'inn' => 'ИНН',
        'personal_data_consent' => 'Согласие на обработку персональных данных',
        'employment_record' => 'Трудовая книжка / выписка',
        'medical_book' => 'Медицинская книжка',
        'education_document' => 'Документ об образовании',
        'employment_contract' => 'Трудовой договор',
        'guardian_consent' => 'Согласие законного представителя',
        'driver_license' => 'Водительское удостоверение',
        'foreign_passport' => 'Иностранный паспорт',
        'passport_translation' => 'Нотариальный перевод паспорта',
        'migration_card' => 'Миграционная карта',
        'migration_registration' => 'Миграционный учёт / регистрация',
        'patent' => 'Патент на работу',
        'work_permit' => 'Разрешение на работу',
        'visa' => 'Виза',
        'voluntary_medical_insurance' => 'Полис ДМС',
        'residence_permit' => 'ВНЖ',
        'temporary_residence_permit' => 'РВП',
        'tax_payment_receipt' => 'Чек оплаты патента',
        'foreign_employment_notice' => 'Уведомление МВД о заключении договора',
        'foreign_dismissal_notice' => 'Уведомление МВД о расторжении договора',
        'other' => 'Другое',
    ];

    private const EXPIRATION_CONTROLLED = [
        'medical_book',
        'patent',
        'work_permit',
        'visa',
        'voluntary_medical_insurance',
        'residence_permit',
        'temporary_residence_permit',
        'migration_registration',
        'driver_license',
    ];

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public static function label(string $category): string
    {
        return self::CATEGORIES[$category] ?? $category;
    }

    public static function expirationControlledCategories(): array
    {
        return self::EXPIRATION_CONTROLLED;
    }

    public static function requiresExpirationControl(string $category): bool
    {
        return in_array($category, self::EXPIRATION_CONTROLLED, true);
    }

    public static function requiredFor(User $employee): array
    {
        $required = match ($employee->employment_type) {
            User::EMPLOYMENT_TYPE_STAFF => [
                'passport',
                'snils',
                'inn',
                'personal_data_consent',
                'employment_record',
            ],
            User::EMPLOYMENT_TYPE_CIVIL_CONTRACT,
            User::EMPLOYMENT_TYPE_SELF_EMPLOYED,
            User::EMPLOYMENT_TYPE_PART_TIME => [
                'passport',
                'snils',
                'inn',
                'personal_data_consent',
            ],
            User::EMPLOYMENT_TYPE_MINOR => [
                'passport',
                'snils',
                'personal_data_consent',
                'guardian_consent',
            ],
            User::EMPLOYMENT_TYPE_NOT_EMPLOYED => [],
            default => [
                'passport',
                'snils',
                'inn',
                'personal_data_consent',
            ],
        };

        if (self::isForeignOrStateless($employee)) {
            $required = [
                ...$required,
                'foreign_passport',
                'passport_translation',
                'personal_data_consent',
                'migration_registration',
                ...match ($employee->foreign_legal_status) {
                    User::FOREIGN_STATUS_PATENT => [
                        'patent',
                        'migration_card',
                        'voluntary_medical_insurance',
                        'tax_payment_receipt',
                    ],
                    User::FOREIGN_STATUS_WORK_PERMIT => [
                        'work_permit',
                        'migration_card',
                        'voluntary_medical_insurance',
                    ],
                    User::FOREIGN_STATUS_VISA => [
                        'visa',
                        'migration_card',
                        'voluntary_medical_insurance',
                    ],
                    User::FOREIGN_STATUS_TEMPORARY_RESIDENCE => [
                        'temporary_residence_permit',
                    ],
                    User::FOREIGN_STATUS_RESIDENCE_PERMIT => [
                        'residence_permit',
                    ],
                    default => [],
                },
            ];
        }

        return array_values(array_unique($required));
    }

    public static function optionalFor(User $employee): array
    {
        $optional = match ($employee->employment_type) {
            User::EMPLOYMENT_TYPE_STAFF => [
                'medical_book',
                'education_document',
                'employment_contract',
                'driver_license',
            ],
            User::EMPLOYMENT_TYPE_MINOR => [
                'inn',
                'medical_book',
                'education_document',
            ],
            default => [
                'medical_book',
                'education_document',
                'employment_contract',
                'driver_license',
            ],
        };

        if (self::isForeignOrStateless($employee) && $employee->foreign_legal_status === User::FOREIGN_STATUS_EAEU) {
            $optional = [
                ...$optional,
                'migration_card',
                'voluntary_medical_insurance',
            ];
        }

        return array_values(array_diff(array_unique($optional), self::requiredFor($employee)));
    }

    public static function missingFor(User $employee): array
    {
        $present = $employee->activeDocuments()
            ->pluck('category')
            ->unique()
            ->all();

        return array_values(array_diff(self::requiredFor($employee), $present));
    }

    public static function isComplete(User $employee): bool
    {
        return self::missingFor($employee) === [];
    }

    private static function isForeignOrStateless(User $employee): bool
    {
        if (in_array($employee->citizenship_type, [User::CITIZENSHIP_FOREIGN, User::CITIZENSHIP_STATELESS], true)) {
            return true;
        }

        $country = mb_strtolower(trim((string) $employee->citizenship_country));

        return $country !== '' && ! in_array($country, ['россия', 'рф', 'russia', 'russian federation'], true);
    }
}
