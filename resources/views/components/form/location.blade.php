{{--
    Division, district (and optionally upazila) dropdowns. Each list narrows to the choice above it (see app.js).

    by="name" (default): values are names from config/bangladesh.php. Used by the browse filters,
                          which still search sample data by name until push B3.
    by="id":              values are database ids, field names division_id / district_id / upazila_id,
                          and the upazila list comes from the upazilas table. Use this for forms that save.

    <x-form.location :division="$filters['division']" :district="$filters['district']" />
    <x-form.location by="id" required with-upazila :division="$user->division_id" :district="$user->district_id" :upazila="$user->upazila_id" />
--}}
@props([
    'by' => 'name',
    'division' => null,
    'district' => null,
    'upazila' => null,
    'required' => false,
    'withUpazila' => false,
    'labels' => true,
    'idPrefix' => '',
])

@php
    $byId = $by === 'id';
    $names = $byId ? ['division_id', 'district_id', 'upazila_id'] : ['division', 'district', 'upazila'];
    [$divisionField, $districtField, $upazilaField] = $names;

    $currentDivision = (string) old($divisionField, $division);
    $currentDistrict = (string) old($districtField, $district);
    $currentUpazila = (string) old($upazilaField, $upazila);

    if ($byId) {
        // One query for the whole tree, reused if the component appears twice on a page.
        $tree = once(fn () => \App\Models\Division::with(['districts' => fn ($q) => $q->orderBy('name'), 'districts.upazilas' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get());
        $divisionOptions = $tree->pluck('name', 'id')->all();
        $districtOptions = $tree->flatMap(fn ($d) => $d->districts->map(fn ($t) => ['value' => $t->id, 'label' => $t->name, 'parent' => $d->id]));
        $upazilaOptions = $tree->flatMap(fn ($d) => $d->districts->flatMap(fn ($t) => $t->upazilas->map(fn ($u) => ['value' => $u->id, 'label' => $u->name, 'parent' => $t->id])));
    } else {
        $config = config('bangladesh.divisions');
        $divisionOptions = array_combine(array_keys($config), array_keys($config));
        $districtOptions = collect($config)->flatMap(fn ($districts, $divisionName) => collect($districts)->map(fn ($name) => ['value' => $name, 'label' => $name, 'parent' => $divisionName]));
        $upazilaOptions = collect();
    }

    $divisionId = $idPrefix.$divisionField;
    $districtId = $idPrefix.$districtField;
    $upazilaId = $idPrefix.$upazilaField;
@endphp

<x-form.select :name="$divisionField" :id="$divisionId" :label="$labels ? 'Division' : null" :aria-label="$labels ? null : 'Division'"
    :selected="$currentDivision" :placeholder="$required ? 'Select division' : 'All divisions'" :required="$required"
    :options="$divisionOptions" />

<x-form.select :name="$districtField" :id="$districtId" :label="$labels ? 'District' : null" :aria-label="$labels ? null : 'District'"
    :placeholder="$required ? 'Select district' : 'All districts'" :required="$required" data-depends-on="{{ $divisionId }}">
    @foreach ($districtOptions as $option)
        <option value="{{ $option['value'] }}" data-parent="{{ $option['parent'] }}" @selected($currentDistrict === (string) $option['value'])>{{ $option['label'] }}</option>
    @endforeach
</x-form.select>

@if ($withUpazila)
    @if ($byId)
        <x-form.select :name="$upazilaField" :id="$upazilaId" label="Upazila / Thana" :placeholder="'Select upazila or thana'" :required="$required" data-needs-parent data-depends-on="{{ $districtId }}">
            @foreach ($upazilaOptions as $option)
                <option value="{{ $option['value'] }}" data-parent="{{ $option['parent'] }}" @selected($currentUpazila === (string) $option['value'])>{{ $option['label'] }}</option>
            @endforeach
        </x-form.select>
    @else
        {{-- Name mode has no upazila list yet: plain text (forms that still use it are not connected). --}}
        <x-form.input :name="$upazilaField" :id="$upazilaId" label="Upazila / Thana" :value="$currentUpazila" :required="$required"
            maxlength="80" autocomplete="address-level3" placeholder="e.g. Dhanmondi" />
    @endif
@endif
