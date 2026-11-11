{{--
    Division and district dropdowns. Choosing a division narrows the district list (see app.js).
    <x-form.location :division="$filters['division']" :district="$filters['district']" />
    <x-form.location required with-upazila />

    Upazila is a plain text box until the locations table is added in the backend part.
--}}
@props([
    'division' => null,
    'district' => null,
    'upazila' => null,
    'required' => false,
    'withUpazila' => false,
    'labels' => true,
    'idPrefix' => '',
])

@php
    $divisions = config('bangladesh.divisions');
    $currentDivision = old('division', $division);
    $currentDistrict = old('district', $district);
@endphp

<x-form.select name="division" :id="$idPrefix.'division'" :label="$labels ? 'Division' : null" :aria-label="$labels ? null : 'Division'"
    :selected="$currentDivision" :placeholder="$required ? 'Select division' : 'All divisions'" :required="$required"
    :options="array_combine(array_keys($divisions), array_keys($divisions))" />

<x-form.select name="district" :id="$idPrefix.'district'" :label="$labels ? 'District' : null" :aria-label="$labels ? null : 'District'"
    :placeholder="$required ? 'Select district' : 'All districts'" :required="$required" data-district-of="{{ $idPrefix }}division">
    @foreach ($divisions as $divisionName => $districts)
        @foreach ($districts as $districtName)
            <option value="{{ $districtName }}" data-division="{{ $divisionName }}" @selected($currentDistrict === $districtName)>{{ $districtName }}</option>
        @endforeach
    @endforeach
</x-form.select>

@if ($withUpazila)
    <x-form.input name="upazila" :id="$idPrefix.'upazila'" label="Upazila / Thana" :value="$upazila" :required="$required"
        maxlength="80" autocomplete="address-level3" placeholder="e.g. Dhanmondi" />
@endif
