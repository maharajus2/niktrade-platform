<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Certificate;
use App\Rules\NoExpiredCertificates;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('article')
                            ->label('Артикул')
                            ->maxLength(255),

                        TextInput::make('barcode')
                            ->label('Штрихкод')
                            ->maxLength(255)
                            ->helperText('Введите цифры штрихкода. Изображение штрихкода будет генерироваться автоматически.'),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Классификация')
                    ->schema([
                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('product_type_id')
                            ->label('Тип продукта')
                            ->relationship('productType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('product_line_id')
                            ->label('Линейка продуктов')
                            ->relationship('productLine', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('direction')
                            ->label('Направление')
                            ->options([
                                'home' => 'Home',
                                'professional' => 'Professional',
                            ])
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Цена и продажи')
                    ->schema([
                        TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if (! is_numeric($state)) {
                                    return;
                                }

                                $discountPercent = $get('discount_percent');

                                if (! is_numeric($discountPercent)) {
                                    return;
                                }

                                $discountedPrice = round($state - ($state * $discountPercent / 100), 2);

                                if ($get('discounted_price') !== $discountedPrice) {
                                    $set('discounted_price', $discountedPrice);
                                }
                            }),

                        TextInput::make('discount_percent')
                            ->label('Скидка (%)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(100)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if (! is_numeric($state)) {
                                    return;
                                }

                                $price = $get('price');

                                if (! is_numeric($price)) {
                                    return;
                                }

                                $discountedPrice = round($price - ($price * $state / 100), 2);

                                if ($get('discounted_price') !== $discountedPrice) {
                                    $set('discounted_price', $discountedPrice);
                                }
                            }),

                        TextInput::make('discounted_price')
                            ->label('Цена со скидкой')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if (! is_numeric($state)) {
                                    return;
                                }

                                $price = $get('price');

                                if (! is_numeric($price) || $price <= 0) {
                                    return;
                                }

                                $discountPercent = round((($price - $state) / $price) * 100, 1);

                                if ($get('discount_percent') !== $discountPercent) {
                                    $set('discount_percent', $discountPercent);
                                }
                            })
                            ->rules(function (callable $get) {
                                return [
                                    'numeric',
                                    'min:0',
                                    function ($attribute, $value, $fail) use ($get) {
                                        $price = $get('price');

                                        if (! is_numeric($price) || ! is_numeric($value)) {
                                            return;
                                        }

                                        if ($value > $price) {
                                            $fail('Цена со скидкой не может быть больше обычной цены.');
                                        }
                                    },
                                ];
                            })
                            ->helperText('Оставьте пустым, чтобы рассчитать автоматически.'),

                        Toggle::make('is_featured')
                            ->label('Выгодно'),

                        Toggle::make('is_best_seller')
                            ->label('Хит продаж'),

                        Toggle::make('is_new')
                            ->label('Новинка'),

                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Section::make('Характеристики')
                    ->schema([
                        TextInput::make('volume_value')
                            ->label('Объем (число)')
                            ->numeric()
                            ->minValue(0),

                        Select::make('volume_unit')
                            ->label('Единица объема')
                            ->options([
                                'ml' => 'мл',
                                'l' => 'л',
                                'g' => 'г',
                                'kg' => 'кг',
                            ])
                            ->searchable(),

                        TextInput::make('shelf_life_value')
                            ->label('Срок годности (число)')
                            ->numeric()
                            ->minValue(0),

                        Select::make('shelf_life_unit')
                            ->label('Единица срока годности')
                            ->options([
                                'days' => 'Дней',
                                'months' => 'Месяцев',
                                'years' => 'Лет',
                            ])
                            ->searchable(),

                        Select::make('availability_status')
                            ->label('Статус наличия')
                            ->options([
                                'in_stock' => 'В наличии',
                                'out_of_stock' => 'Временно отсутствует',
                                'discontinued' => 'Снят с производства',
                            ])
                            ->default('in_stock')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')
                            ->label('Краткое описание')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('composition')
                            ->label('Состав')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('usage_method')
                            ->label('Способ применения')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('storage_conditions')
                            ->label('Условия хранения')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('precautions')
                            ->label('Меры предосторожности')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('disposal_method')
                            ->label('Утилизация')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Документация')
                    ->schema([
                        Select::make('certificates')
                            ->label('Документация')
                            ->multiple()
                            ->relationship(
                                name: 'certificates',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Certificate $record): string => self::formatCertificateOptionLabel($record))
                            ->rules([new NoExpiredCertificates()])
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        FileUpload::make('instruction_file_path')
                            ->label('Инструкция (PDF)')
                            ->disk('public')
                            ->directory('product-instructions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),

                        ViewField::make('instruction_file_preview')
                            ->label('Предпросмотр инструкции')
                            ->view('filament.forms.components.product-instruction-pdf-preview')
                            ->visible(fn ($get) => filled($get('instruction_file_path')) && str_starts_with($get('instruction_file_path'), 'product-instructions/'))
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO Title')
                            ->maxLength(255)
                            ->helperText('Заголовок для поисковых систем'),

                        Textarea::make('seo_description')
                            ->label('SEO Description')
                            ->rows(3)
                            ->helperText('Описание для поисковых систем')
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    private static function formatCertificateOptionLabel(Certificate $certificate): string
    {
        $number = $certificate->number ? " №{$certificate->number}" : '';

        return sprintf(
            '%s %s%s',
            self::getCertificateStatusLabel($certificate),
            $certificate->name,
            $number,
        );
    }

    private static function getCertificateStatusLabel(Certificate $certificate): string
    {
        if ($certificate->is_permanent) {
            return '🔵 бессрочно';
        }

        if ($certificate->expires_at?->isPast()) {
            return '🔴 просрочен';
        }

        if ($certificate->expires_at?->lte(now()->addDays(30))) {
            return '🟡 скоро истекает';
        }

        return '🟢 действует';
    }
}
