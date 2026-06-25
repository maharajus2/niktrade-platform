@extends('layouts.public')

@section('title', 'Оформление заказа')

@push('styles')
    <style>
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
        }

        .title {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
            align-items: start;
        }

        .form,
        .summary {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .form {
            display: grid;
            gap: 22px;
        }

        .section {
            display: grid;
            gap: 14px;
        }

        .section-title {
            margin: 0;
            font-size: 1.2rem;
        }

        .fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .label {
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .input,
        .textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            padding: 10px 12px;
        }

        .textarea {
            min-height: 110px;
            resize: vertical;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
        }

        .hint {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .checkbox {
            display: flex;
            gap: 8px;
            align-items: center;
            color: #374151;
            font-weight: 700;
        }

        .fulfillment-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .fulfillment-option {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 800;
            padding: 12px;
        }

        .fulfillment-panel[hidden] {
            display: none;
        }

        .warehouse-info {
            display: grid;
            gap: 8px;
            border: 1px solid #d1fae5;
            border-radius: 8px;
            background: #f0fdf4;
            color: #166534;
            padding: 12px;
        }

        .warehouse-info[hidden] {
            display: none;
        }

        .button,
        .link-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 10px 16px;
            text-decoration: none;
        }

        .button {
            width: fit-content;
            background: #166534;
            color: #ffffff;
            cursor: pointer;
        }

        .link-button {
            background: #ecfdf5;
            color: #166534;
        }

        .summary {
            display: grid;
            gap: 14px;
        }

        .summary-title {
            margin: 0;
            font-size: 1.2rem;
        }

        .item {
            display: grid;
            gap: 4px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }

        .item:first-of-type {
            border-top: 0;
            padding-top: 0;
        }

        .item-name {
            font-weight: 800;
        }

        .item-meta {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: #374151;
        }

        .total-row.final {
            border-top: 1px solid #e5e7eb;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 900;
            padding-top: 14px;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page {
                width: min(100% - 24px, 1180px);
                padding-top: 24px;
            }

            .header {
                display: grid;
            }

            .fields {
                grid-template-columns: 1fr;
            }

            .fulfillment-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $fulfillmentMethod = old('fulfillment_method', \App\Models\Order::FULFILLMENT_DELIVERY);
        $selectedAddressId = old('customer_address_id', $defaultAddress?->id);
        $selectedWarehouseId = old('warehouse_id');
        $deliverySource = $addresses->firstWhere('id', (int) $selectedAddressId) ?? $defaultAddress;
        $addressPayload = $addresses
            ->map(fn ($address): array => [
                'id' => $address->id,
                'postal_code' => $address->postal_code,
                'region' => $address->region,
                'city' => $address->city,
                'street' => $address->street,
                'house' => $address->house,
                'building' => $address->building,
                'apartment' => $address->apartment,
                'entrance' => $address->entrance,
                'floor' => $address->floor,
                'delivery_comment' => $address->comment,
            ])
            ->values();
        $warehousePayload = $warehouses
            ->map(fn ($warehouse): array => [
                'id' => $warehouse->id,
                'address' => $warehouse->full_address,
                'phone' => $warehouse->phone,
                'working_hours' => $warehouse->working_schedule_label,
            ])
            ->values();
    @endphp

    <main class="page">
        <header class="header">
            <h1 class="title">Оформление заказа</h1>
            <a class="link-button" href="{{ route('cart.index') }}">Вернуться в корзину</a>
        </header>

        <div class="layout">
            <form class="form" method="POST" action="{{ route('checkout.store') }}">
                @csrf

                <section class="section">
                    <h2 class="section-title">Покупатель</h2>

                    <div class="fields">
                        <label class="field">
                            <span class="label">Имя</span>
                            <input class="input" type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" required>
                            @error('first_name')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Фамилия</span>
                            <input class="input" type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}">
                            @error('last_name')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Email</span>
                            <input class="input" type="email" name="email" value="{{ old('email', $customer?->email) }}" required>
                            @error('email')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Телефон</span>
                            <input class="input" type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" required>
                            @error('phone')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="section">
                    <h2 class="section-title">Получение</h2>

                    <div class="fulfillment-options">
                        <label class="fulfillment-option">
                            <input type="radio" name="fulfillment_method" value="{{ \App\Models\Order::FULFILLMENT_DELIVERY }}" @checked($fulfillmentMethod === \App\Models\Order::FULFILLMENT_DELIVERY)>
                            <span>Доставка</span>
                        </label>

                        <label class="fulfillment-option">
                            <input type="radio" name="fulfillment_method" value="{{ \App\Models\Order::FULFILLMENT_PICKUP }}" @checked($fulfillmentMethod === \App\Models\Order::FULFILLMENT_PICKUP)>
                            <span>Самовывоз</span>
                        </label>
                    </div>

                    @error('fulfillment_method')
                        <span class="error">{{ $message }}</span>
                    @enderror

                    <div class="fulfillment-panel" data-fulfillment-panel="delivery">
                        @if ($customer && $addresses->isNotEmpty())
                            <label class="field full">
                                <span class="label">Адрес доставки</span>
                                <select class="input" name="customer_address_id" id="customer-address-select">
                                    <option value="">Новый адрес</option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected((int) $selectedAddressId === $address->id)>
                                            {{ $address->title ?: trim($address->city . ', ' . $address->street . ', ' . $address->house) }}
                                            @if ($address->is_default)
                                                — по умолчанию
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <span class="hint">Можно выбрать сохранённый адрес или изменить поля ниже для этого заказа.</span>
                                @error('customer_address_id')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>
                        @endif

                        <div class="fields">
                            <label class="field">
                                <span class="label">Индекс</span>
                                <input class="input" type="text" name="postal_code" value="{{ old('postal_code', $deliverySource?->postal_code) }}" data-address-field="postal_code">
                                @error('postal_code')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Регион</span>
                                <input class="input" type="text" name="region" value="{{ old('region', $deliverySource?->region) }}" data-address-field="region">
                                @error('region')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Город</span>
                                <input class="input" type="text" name="city" value="{{ old('city', $deliverySource?->city) }}" data-address-field="city" data-delivery-required>
                                @error('city')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Улица</span>
                                <input class="input" type="text" name="street" value="{{ old('street', $deliverySource?->street) }}" data-address-field="street" data-delivery-required>
                                @error('street')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Дом</span>
                                <input class="input" type="text" name="house" value="{{ old('house', $deliverySource?->house) }}" data-address-field="house" data-delivery-required>
                                @error('house')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Корпус</span>
                                <input class="input" type="text" name="building" value="{{ old('building', $deliverySource?->building) }}" data-address-field="building">
                                @error('building')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Квартира</span>
                                <input class="input" type="text" name="apartment" value="{{ old('apartment', $deliverySource?->apartment) }}" data-address-field="apartment">
                                @error('apartment')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Подъезд</span>
                                <input class="input" type="text" name="entrance" value="{{ old('entrance', $deliverySource?->entrance) }}" data-address-field="entrance">
                                @error('entrance')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field">
                                <span class="label">Этаж</span>
                                <input class="input" type="text" name="floor" value="{{ old('floor', $deliverySource?->floor) }}" data-address-field="floor">
                                @error('floor')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="field full">
                                <span class="label">Комментарий к доставке</span>
                                <textarea class="textarea" name="delivery_comment" data-address-field="delivery_comment">{{ old('delivery_comment', $deliverySource?->comment) }}</textarea>
                                @error('delivery_comment')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        @if ($customer && $addresses->isEmpty())
                            <label class="checkbox" data-save-address-block>
                                <input type="checkbox" name="save_address" value="1" @checked(old('save_address'))>
                                <span>Сохранить адрес в личном кабинете</span>
                            </label>
                        @endif
                    </div>

                    <div class="fulfillment-panel" data-fulfillment-panel="pickup">
                        <label class="field full">
                            <span class="label">Пункт самовывоза</span>
                            <select class="input" name="warehouse_id" id="warehouse-select">
                                <option value="">Выберите пункт самовывоза</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((int) $selectedWarehouseId === $warehouse->id)>
                                        {{ $warehouse->name }} — {{ $warehouse->full_address ?: $warehouse->city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <div class="warehouse-info" id="warehouse-info" hidden></div>
                    </div>
                </section>

                <section class="section">
                    <h2 class="section-title">Комментарий</h2>

                    <label class="field full">
                        <span class="label">Комментарий к заказу</span>
                        <textarea class="textarea" name="comment">{{ old('comment') }}</textarea>
                        @error('comment')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </label>
                </section>

                <button class="button" type="submit">Оформить заказ</button>
            </form>

            <aside class="summary" aria-label="Состав заказа">
                <h2 class="summary-title">Ваш заказ</h2>

                @foreach ($items as $item)
                    <div class="item">
                        <div class="item-name">{{ $item->product?->name ?? 'Товар удален' }}</div>
                        <div class="item-meta">
                            {{ $item->quantity }} × {{ number_format((float) $item->price_snapshot, 2, ',', ' ') }} ₽
                        </div>
                        <div class="item-meta">
                            Итого: {{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                @endforeach

                <div class="total-row">
                    <span>Сумма товаров</span>
                    <strong>{{ number_format((float) $cart->subtotal, 2, ',', ' ') }} ₽</strong>
                </div>

                <div class="total-row">
                    <span>Скидка</span>
                    <strong>{{ number_format((float) $cart->discount_total, 2, ',', ' ') }} ₽</strong>
                </div>

                <div class="total-row final">
                    <span>Итого</span>
                    <strong>{{ number_format((float) $cart->total, 2, ',', ' ') }} ₽</strong>
                </div>
            </aside>
        </div>
    </main>

    <script>
        (() => {
            const addresses = @json($addressPayload);
            const warehouses = @json($warehousePayload);
            const addressSelect = document.getElementById('customer-address-select');
            const warehouseSelect = document.getElementById('warehouse-select');
            const warehouseInfo = document.getElementById('warehouse-info');
            const fulfillmentInputs = [...document.querySelectorAll('input[name="fulfillment_method"]')];
            const panels = [...document.querySelectorAll('[data-fulfillment-panel]')];
            const deliveryRequiredFields = [...document.querySelectorAll('[data-delivery-required]')];

            const selectedFulfillmentMethod = () => {
                return fulfillmentInputs.find((input) => input.checked)?.value ?? @json(\App\Models\Order::FULFILLMENT_DELIVERY);
            };

            const renderWarehouseInfo = () => {
                if (!warehouseSelect || !warehouseInfo) {
                    return;
                }

                const warehouse = warehouses.find((item) => String(item.id) === warehouseSelect.value);

                if (!warehouse) {
                    warehouseInfo.hidden = true;
                    warehouseInfo.innerHTML = '';
                    return;
                }

                warehouseInfo.replaceChildren();

                [
                    ['Адрес:', warehouse.address],
                    ['Телефон:', warehouse.phone],
                    ['Часы работы:', warehouse.working_hours],
                ].forEach(([label, value]) => {
                    if (!value) {
                        return;
                    }

                    const row = document.createElement('div');
                    const labelElement = document.createElement('strong');

                    labelElement.textContent = label;
                    row.append(labelElement, ` ${value}`);
                    warehouseInfo.append(row);
                });

                warehouseInfo.hidden = warehouseInfo.children.length === 0;
            };

            const syncFulfillmentPanels = () => {
                const method = selectedFulfillmentMethod();
                const isDelivery = method === @json(\App\Models\Order::FULFILLMENT_DELIVERY);

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.fulfillmentPanel !== method;
                });

                deliveryRequiredFields.forEach((field) => {
                    field.required = isDelivery;
                });

                if (warehouseSelect) {
                    warehouseSelect.required = !isDelivery;
                }

                renderWarehouseInfo();
            };

            if (addressSelect) {
                addressSelect.addEventListener('change', () => {
                    const address = addresses.find((item) => String(item.id) === addressSelect.value);

                    if (!address) {
                        return;
                    }

                    Object.entries(address).forEach(([field, value]) => {
                        const input = document.querySelector(`[data-address-field="${field}"]`);

                        if (input) {
                            input.value = value ?? '';
                        }
                    });
                });
            }

            if (warehouseSelect) {
                warehouseSelect.addEventListener('change', renderWarehouseInfo);
            }

            fulfillmentInputs.forEach((input) => {
                input.addEventListener('change', syncFulfillmentPanels);
            });

            syncFulfillmentPanels();
        })();
    </script>
@endsection
