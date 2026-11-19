<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Medicine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The verified medicine catalogue. Changes are audited.
 */
class CatalogueController extends Controller
{
    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 80) : '';
        $like = '%'.addcslashes($q, '%_\\').'%';

        return view('admin.medicines', [
            'medicines' => Medicine::with('aliases')->withCount('listings')
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                    ->where('generic_name', 'like', $like)
                    ->orWhereHas('aliases', fn ($a) => $a->where('brand_name', 'like', $like))))
                ->orderBy('eligibility')->orderBy('generic_name')->orderBy('strength')
                ->get(),
            'q' => $q,
            'editing' => $request->query('edit') ? Medicine::with('aliases')->find($request->query('edit')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $medicine = DB::transaction(fn () => $this->save(new Medicine, $this->validated($request)));
        AuditLog::record('medicine.created', $medicine, "Added {$medicine->label()} to the catalogue");

        return redirect()->route('admin.medicines.index')->with('success', "{$medicine->label()} was added.");
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        DB::transaction(fn () => $this->save($medicine, $this->validated($request, $medicine)));
        AuditLog::record('medicine.updated', $medicine, "Updated {$medicine->label()} in the catalogue");

        return redirect()->route('admin.medicines.index')->with('success', "{$medicine->label()} was updated.");
    }

    private function validated(Request $request, ?Medicine $medicine = null): array
    {
        return $request->validate([
            'generic_name' => ['required', 'string', 'max:150'],
            'strength' => ['required', 'string', 'max:50',
                Rule::unique('medicines')->where(fn ($q) => $q->where('generic_name', $request->input('generic_name'))->where('dosage_form', $request->input('dosage_form')))->ignore($medicine?->id)],
            'dosage_form' => ['required', 'string', 'max:30'],
            'requires_prescription' => ['nullable', 'boolean'],
            'eligibility' => ['required', Rule::in(['allowed', 'not_allowed'])],
            'restriction_reason' => ['nullable', 'required_if:eligibility,not_allowed', 'string', 'max:200'],
            'brand_names' => ['nullable', 'string', 'max:500'],
        ], ['strength.unique' => 'This medicine (generic name, strength and form) is already in the catalogue.']);
    }

    private function save(Medicine $medicine, array $data): Medicine
    {
        $medicine->fill([
            'generic_name' => trim($data['generic_name']),
            'strength' => trim($data['strength']),
            'dosage_form' => trim($data['dosage_form']),
            'requires_prescription' => (bool) ($data['requires_prescription'] ?? false),
            'eligibility' => $data['eligibility'],
            'restriction_reason' => $data['eligibility'] === 'not_allowed' ? $data['restriction_reason'] : null,
        ])->save();

        // Brand names: comma separated. Brands no longer listed are removed.
        $brands = collect(explode(',', $data['brand_names'] ?? ''))->map(fn ($b) => trim($b))->filter()->unique(fn ($b) => mb_strtolower($b));
        $medicine->aliases()->whereNotIn('brand_name', $brands->all())->delete();
        $brands->each(fn ($brand) => $medicine->aliases()->firstOrCreate(['brand_name' => mb_substr($brand, 0, 100)]));

        return $medicine;
    }
}
