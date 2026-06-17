@php
    $fieldValue = fn (string $field) => old($field, $address?->{$field});
@endphp

<div class="fields">
    <label class="field">
        <span class="label">Название</span>
        <input class="input" type="text" name="title" value="{{ $fieldValue('title') }}">
    </label>

    <label class="field">
        <span class="label">Индекс</span>
        <input class="input" type="text" name="postal_code" value="{{ $fieldValue('postal_code') }}">
    </label>

    <label class="field">
        <span class="label">Регион</span>
        <input class="input" type="text" name="region" value="{{ $fieldValue('region') }}">
    </label>

    <label class="field">
        <span class="label">Город</span>
        <input class="input" type="text" name="city" value="{{ $fieldValue('city') }}" required>
        @error('city')
            <span class="error">{{ $message }}</span>
        @enderror
    </label>

    <label class="field">
        <span class="label">Улица</span>
        <input class="input" type="text" name="street" value="{{ $fieldValue('street') }}" required>
        @error('street')
            <span class="error">{{ $message }}</span>
        @enderror
    </label>

    <label class="field">
        <span class="label">Дом</span>
        <input class="input" type="text" name="house" value="{{ $fieldValue('house') }}" required>
        @error('house')
            <span class="error">{{ $message }}</span>
        @enderror
    </label>

    <label class="field">
        <span class="label">Корпус</span>
        <input class="input" type="text" name="building" value="{{ $fieldValue('building') }}">
    </label>

    <label class="field">
        <span class="label">Квартира</span>
        <input class="input" type="text" name="apartment" value="{{ $fieldValue('apartment') }}">
    </label>

    <label class="field">
        <span class="label">Подъезд</span>
        <input class="input" type="text" name="entrance" value="{{ $fieldValue('entrance') }}">
    </label>

    <label class="field">
        <span class="label">Этаж</span>
        <input class="input" type="text" name="floor" value="{{ $fieldValue('floor') }}">
    </label>

    <label class="field full">
        <span class="label">Комментарий</span>
        <textarea class="textarea" name="comment">{{ $fieldValue('comment') }}</textarea>
    </label>
</div>

<label class="checkbox">
    <input type="checkbox" name="is_default" value="1" @checked((bool) old('is_default', $address?->is_default ?? false))>
    <span>Адрес по умолчанию</span>
</label>
