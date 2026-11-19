<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CustomFormField;
use App\Models\FormBuilderRevision;
use App\Models\FormBuilderState;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormBuilderController extends Controller
{
    public function index(Request $request)
    {
        $formTypes = CustomFormField::formTypes();
        $formType = $request->get('form_type', 'accreditation');
        abort_unless(array_key_exists($formType, $formTypes), 404);

        $state = FormBuilderState::where('form_type', $formType)->firstOrFail();
        $fields = collect($state->draft_schema ?? [])
            ->map(fn (array $field) => (object) ($field + ['id' => $field['field_key'] ?? '']))
            ->sortBy(fn ($field) => sprintf('%03d-%06d', $field->step_number, $field->sort_order))
            ->values();
        $revisions = FormBuilderRevision::where('form_type', $formType)
            ->with('publisher:id,name')
            ->orderByDesc('version')
            ->limit(12)
            ->get();
        $fieldTypes = CustomFormField::fieldTypes();
        $hasUnpublishedChanges = $this->schemaHash($state->draft_schema ?? []) !== $this->schemaHash($state->published_schema ?? []);
        $stepLabels = CustomFormField::stepLabels()[$formType] ?? [];
        $previewStep = min(max($request->integer('step', 1), 1), max(1, count($stepLabels)));

        return view('staff.it.form-builder.index', compact(
            'fields', 'formType', 'formTypes', 'fieldTypes', 'state', 'revisions', 'hasUnpublishedChanges', 'stepLabels', 'previewStep'
        ));
    }

    public function preview(Request $request)
    {
        $formType = (string) $request->query('form_type', 'accreditation');
        $stepLabels = CustomFormField::stepLabels();
        abort_unless(array_key_exists($formType, $stepLabels), 404);

        $previewStep = min(max($request->integer('step', 1), 1), count($stepLabels[$formType]));
        $state = FormBuilderState::where('form_type', $formType)->firstOrFail();
        $customFieldsOverride = collect($state->draft_schema ?? [])
            ->where('is_active', true)
            ->map(fn (array $field) => (object) ($field + ['id' => $field['field_key'] ?? $field['field_name']]))
            ->values();

        $view = match ($formType) {
            'accreditation' => 'portal.accreditation.new',
            'ap1' => 'portal.mediahouse.newregistration',
            'ap2' => 'portal.mediahouse.ap2_form',
            'ap4' => 'portal.mediahouse.ap4_form',
        };
        $draft = null;
        $returnReasons = collect();
        $formBuilderPreview = true;
        $customFormPreview = true;

        return view($view, compact(
            'formType', 'previewStep', 'customFieldsOverride', 'draft', 'returnReasons', 'formBuilderPreview', 'customFormPreview'
        ));
    }

    public function store(Request $request)
    {
        $maxSteps = CustomFormField::stepCounts()[$request->input('form_type')] ?? 1;
        $data = $request->validate([
            'form_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::formTypes()))],
            'field_label' => ['required', 'string', 'max:200'],
            'field_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::fieldTypes()))],
            'step_number' => ['required', 'integer', 'min:1', 'max:' . $maxSteps],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_required' => ['sometimes', 'boolean'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'options' => ['nullable', 'string', 'max:10000'],
        ]);

        $options = $this->optionsFor($data['field_type'], $data['options'] ?? null);
        if (in_array($data['field_type'], ['select', 'radio'], true) && count($options) < 2) {
            return back()->withErrors(['options' => 'Add at least two non-empty options for this field type.'])->withInput();
        }

        $state = FormBuilderState::where('form_type', $data['form_type'])->firstOrFail();
        $draft = $state->draft_schema ?? [];
        $fieldName = $this->uniqueFieldName($data['form_type'], $data['field_label'], $draft);
        $maxSort = collect($draft)
            ->where('step_number', (int) $data['step_number'])
            ->max('sort_order') ?? 0;

        $draft[] = [
            'field_key' => 'draft-' . Str::uuid(),
            'field_name' => $fieldName,
            'field_label' => $data['field_label'],
            'field_type' => $data['field_type'],
            'options' => $options,
            'step_number' => (int) $data['step_number'],
            'sort_order' => (int) ($data['sort_order'] ?? ((int) $maxSort + 1)),
            'is_required' => $request->boolean('is_required'),
            'placeholder' => $data['placeholder'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'is_active' => true,
            'validation_rules' => null,
        ];

        $this->saveDraft($state, $draft);

        return $this->backToBuilder($data['form_type'], (int) $data['step_number'])->with('success', 'Field added to the selected step in the draft. Publish this form to make it live.');
    }

    public function update(Request $request, string $field)
    {
        $state = $this->stateForFieldKey($field);
        $maxSteps = CustomFormField::stepCounts()[$state->form_type] ?? 1;
        $data = $request->validate([
            'field_label' => ['required', 'string', 'max:200'],
            'field_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::fieldTypes()))],
            'step_number' => ['required', 'integer', 'min:1', 'max:' . $maxSteps],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_required' => ['sometimes', 'boolean'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'options' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $draft = $state->draft_schema ?? [];
        $index = $this->fieldIndex($draft, $field);
        $options = $this->optionsFor($data['field_type'], $data['options'] ?? null);
        if (in_array($data['field_type'], ['select', 'radio'], true) && count($options) < 2) {
            return back()->withErrors(['options' => 'Add at least two non-empty options for this field type.'])->withInput();
        }

        $draft[$index] = array_merge($draft[$index], [
            'field_label' => $data['field_label'],
            'field_type' => $data['field_type'],
            'options' => $options,
            'step_number' => (int) $data['step_number'],
            'sort_order' => (int) ($data['sort_order'] ?? $draft[$index]['sort_order']),
            'is_required' => $request->boolean('is_required'),
            'placeholder' => $data['placeholder'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->saveDraft($state, $draft);

        return $this->backToBuilder($state->form_type, (int) $data['step_number'])->with('success', 'Draft field saved. Existing submissions retain the original field key and data.');
    }

    public function destroy(string $field)
    {
        $state = $this->stateForFieldKey($field);
        $draft = $state->draft_schema ?? [];
        $index = $this->fieldIndex($draft, $field);
        $stepNumber = (int) ($draft[$index]['step_number'] ?? 1);
        $draft[$index]['is_active'] = false;
        $this->saveDraft($state, $draft);

        return $this->backToBuilder($state->form_type, $stepNumber)->with('success', 'Field removed from the draft. Past submissions are retained; publish to apply this change.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'form_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::formTypes()))],
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'string', 'max:80'],
            'order.*.position' => ['required', 'integer', 'min:0'],
        ]);

        $state = FormBuilderState::where('form_type', $data['form_type'])->firstOrFail();
        $draft = $state->draft_schema ?? [];
        $positions = collect($data['order'])->keyBy('id');
        foreach ($draft as &$field) {
            if ($positions->has($field['field_key'])) {
                $field['sort_order'] = (int) $positions[$field['field_key']]['position'];
            }
        }
        unset($field);
        $this->saveDraft($state, $draft);

        return response()->json(['success' => true, 'published' => false]);
    }

    public function toggleActive(string $field)
    {
        $state = $this->stateForFieldKey($field);
        $draft = $state->draft_schema ?? [];
        $index = $this->fieldIndex($draft, $field);
        $stepNumber = (int) ($draft[$index]['step_number'] ?? 1);
        $draft[$index]['is_active'] = !($draft[$index]['is_active'] ?? false);
        $this->saveDraft($state, $draft);

        return $this->backToBuilder($state->form_type, $stepNumber)->with('success', 'Draft field ' . ($draft[$index]['is_active'] ? 'enabled' : 'disabled') . '. Publish to apply this change.');
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'form_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::formTypes()))],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);
        $state = FormBuilderState::where('form_type', $data['form_type'])->firstOrFail();
        $draft = $this->normalizeSchema($state->draft_schema ?? []);
        $this->validateSchema($draft);

        DB::transaction(function () use ($state, $draft, $data) {
            $fieldNames = [];
            $publishedSchema = [];

            foreach ($draft as $position => $definition) {
                $fieldNames[] = $definition['field_name'];
                $attributes = Arr::only($definition, [
                    'field_name', 'field_label', 'field_type', 'options', 'step_number',
                    'sort_order', 'is_required', 'placeholder', 'help_text', 'validation_rules', 'is_active',
                ]);
                $field = CustomFormField::query()
                    ->where('form_type', $state->form_type)
                    ->where('field_name', $definition['field_name'])
                    ->first();

                if (!$field) {
                    $field = CustomFormField::create($attributes + [
                        'form_type' => $state->form_type,
                        'created_by' => auth()->id(),
                    ]);
                } else {
                    $field->update($attributes);
                }

                $definition['field_key'] = (string) $field->id;
                $publishedSchema[] = $definition;
            }

            // Never delete the definitions. Mark removed fields inactive so
            // staff can still interpret values saved on earlier applications.
            CustomFormField::where('form_type', $state->form_type)
                ->whereNotIn('field_name', $fieldNames ?: ['__none__'])
                ->update(['is_active' => false]);

            $version = (int) $state->version + 1;
            $publishedAt = now();
            $state->update([
                'draft_schema' => $publishedSchema,
                'published_schema' => $publishedSchema,
                'version' => $version,
                'draft_updated_by' => auth()->id(),
                'published_by' => auth()->id(),
                'published_at' => $publishedAt,
            ]);
            FormBuilderRevision::create([
                'form_type' => $state->form_type,
                'version' => $version,
                'schema' => $publishedSchema,
                'change_summary' => $data['change_summary'] ?? null,
                'published_by' => auth()->id(),
                'published_at' => $publishedAt,
            ]);
        });

        \App\Support\AuditTrail::log('it_admin.publish_form_configuration', null, [
            'form_type' => $state->form_type,
            'version' => (int) $state->version,
        ]);

        return $this->backToBuilder($state->form_type)->with('success', 'Form published as version ' . $state->version . '. New applications will use this version; existing applications remain unchanged.');
    }

    public function restoreRevision(Request $request, FormBuilderRevision $revision)
    {
        $data = $request->validate([
            'form_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomFormField::formTypes()))],
        ]);
        abort_unless($revision->form_type === $data['form_type'], 404);
        $state = FormBuilderState::where('form_type', $revision->form_type)->firstOrFail();
        $this->saveDraft($state, $revision->schema ?? []);

        return $this->backToBuilder($state->form_type)->with('success', 'Revision ' . $revision->version . ' restored to the draft. Review the preview and publish when ready.');
    }

    private function saveDraft(FormBuilderState $state, array $draft): void
    {
        $state->update([
            'draft_schema' => $this->normalizeSchema($draft),
            'draft_updated_by' => auth()->id(),
        ]);
    }

    private function stateForFieldKey(string $fieldKey): FormBuilderState
    {
        $state = FormBuilderState::query()->get()->first(function (FormBuilderState $candidate) use ($fieldKey) {
            return collect($candidate->draft_schema ?? [])->contains(fn ($field) => ($field['field_key'] ?? null) === $fieldKey);
        });
        abort_if(!$state, 404);
        return $state;
    }

    private function fieldIndex(array $draft, string $fieldKey): int
    {
        foreach ($draft as $index => $field) {
            if (($field['field_key'] ?? null) === $fieldKey) {
                return $index;
            }
        }
        abort(404);
    }

    private function uniqueFieldName(string $formType, string $label, array $draft): string
    {
        $base = 'custom_' . Str::slug($label, '_');
        $base = substr(trim($base, '_'), 0, 85) ?: 'custom_field';
        $existing = collect($draft)->pluck('field_name')->all();
        $name = $base;
        while (in_array($name, $existing, true) || CustomFormField::where('form_type', $formType)->where('field_name', $name)->exists()) {
            $name = substr($base, 0, 85) . '_' . Str::lower(Str::random(6));
        }
        return $name;
    }

    private function optionsFor(string $fieldType, ?string $rawOptions): ?array
    {
        if (!in_array($fieldType, ['select', 'radio'], true)) {
            return null;
        }
        return collect(preg_split('/\r\n|\r|\n/', (string) $rawOptions))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeSchema(array $schema): array
    {
        return collect($schema)
            ->map(fn ($field) => Arr::only((array) $field, [
                'field_key', 'field_name', 'field_label', 'field_type', 'options', 'step_number',
                'sort_order', 'is_required', 'placeholder', 'help_text', 'is_active', 'validation_rules',
            ]))
            ->values()
            ->all();
    }

    private function validateSchema(array $schema): void
    {
        $allowedTypes = array_keys(CustomFormField::fieldTypes());
        $names = [];
        foreach ($schema as $field) {
            abort_unless(isset($field['field_name']) && preg_match('/^[a-z][a-z0-9_]{0,99}$/', $field['field_name']), 422);
            abort_unless(isset($field['field_label']) && trim($field['field_label']) !== '', 422);
            abort_unless(in_array($field['field_type'] ?? null, $allowedTypes, true), 422);
            abort_unless(!in_array($field['field_name'], $names, true), 422);
            $names[] = $field['field_name'];
            if (in_array($field['field_type'], ['select', 'radio'], true)) {
                abort_unless(count($field['options'] ?? []) >= 2, 422);
            }
        }
    }

    private function schemaHash(array $schema): string
    {
        return hash('sha256', json_encode($this->normalizeSchema($schema)) ?: '[]');
    }

    private function backToBuilder(string $formType, ?int $step = null)
    {
        return redirect()->route('staff.it.form-builder', array_filter([
            'form_type' => $formType,
            'step' => $step,
        ], fn ($value) => $value !== null));
    }
}
