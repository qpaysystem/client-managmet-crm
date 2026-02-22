<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    private const TYPES = [
        'text' => 'Текст',
        'number' => 'Число',
        'date' => 'Дата',
        'select' => 'Выпадающий список',
        'checkbox' => 'Чекбокс',
        'textarea' => 'Текстовая область',
        'file' => 'Файл',
    ];

    public function index(): View
    {
        $fields = CustomField::ordered()->get();
        return view('admin.custom-fields.index', compact('fields'));
    }

    public function create(): View
    {
        return view('admin.custom-fields.create', ['types' => self::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:64|regex:/^[a-z0-9_]+$/|unique:custom_fields,name',
            'label' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'options' => 'nullable|string', // JSON или построчно для select
            'required' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['name'] = strtolower($validated['name']);
        $validated['required'] = $request->boolean('required');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($validated['type'] === 'select' && !empty($validated['options'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['options'])));
            $validated['options'] = array_values($lines);
        } else {
            $validated['options'] = null;
        }

        CustomField::create($validated);
        return redirect()->route('admin.custom-fields.index')->with('success', 'Поле добавлено.');
    }

    public function edit(CustomField $customField): View
    {
        return view('admin.custom-fields.edit', [
            'field' => $customField,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'options' => 'nullable|string',
            'required' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['required'] = $request->boolean('required');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($validated['type'] === 'select' && isset($validated['options'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['options'])));
            $validated['options'] = array_values($lines);
        }

        $customField->update($validated);
        return redirect()->route('admin.custom-fields.index')->with('success', 'Поле обновлено.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();
        return redirect()->route('admin.custom-fields.index')->with('success', 'Поле удалено.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:custom_fields,id']);
        foreach ($request->order as $position => $id) {
            CustomField::where('id', $id)->update(['sort_order' => $position]);
        }
        return back()->with('success', 'Порядок полей сохранён.');
    }
}
