# Component & Controller Structure Guidelines

This file defines conventions for structuring both Livewire component classes (controllers) and Blade views in the CMIS project. Read this file before generating or editing any component to ensure consistency.

---

## Layout & Attributes
- Use `#[Layout('components.layouts.app')]` and `#[Title('...')]` attributes for all Livewire components.
- Blade views should start with `<x-slot:headerTitle>` and use `<x-page-header>` for page headers.

## Main Container
- Use `<div class="space-y-6">` as the main wrapper for page content.

## Page Header
- Always use `<x-page-header>` with `title`, `subtitle`, and action slots.
- Back navigation should use `:backRoute` and `backLabel` props.

## Form Container
- Forms should be wrapped in `<div class="bg-white rounded-lg border border-gray-200">`.
- Use `class="p-6 space-y-4"` for form padding and spacing.

## Form Fields
- Each field should have:
  - `<label>` with `block text-sm font-medium text-gray-700 mb-1.5`.
  - `<input>` or `<select>` with `w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:border-gray-900 focus:ring-1 focus:ring-gray-900 outline-none`.
  - `@error` block with `mt-1 text-sm text-red-500`.
- Use placeholders and required indicators (`<span class="text-red-500">*</span>`) as needed.

## Actions
- Actions section should be:
  - `<div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-200">` for update/create forms.
  - Buttons: consistent classes, e.g. `bg-gray-900` for create, `bg-yellow-600` for update, `bg-white border border-gray-300` for cancel.

## Section Headings
- Use `<h3 class="text-sm font-semibold text-gray-900 mb-4">` for section titles inside forms.

## Uncodixify Principles
- No Codex UI patterns: avoid gradients, floating panels, excessive rounding, dramatic shadows, etc.
- Use clean, functional, human-inspired design as described in the uncodixfy skill.

---

# Livewire Component Controller Structure

## Class Attributes
- Always use `#[Layout('components.layouts.app')]` and `#[Title('...')]` attributes for page components.

## Properties
- Declare all public properties with explicit types (e.g., `public string $name = '';`).
- Use default values for properties where possible.
- For model binding, use typed properties (e.g., `public User $user;`).

## Lifecycle Methods
- Use `mount(Model $model): void` for initializing properties from the model.
- Use `render()` to return the Blade view, passing all required data.

## Validation
- Define validation rules as either:
  - `protected $rules = [...]` for simple cases.
  - `public function rules(): array` for dynamic rules.
- Use `$this->validate()` for validation.

## Actions
- Use clear, descriptive method names for actions (e.g., `save()`, `submit()`, `deleteUser()`).
- Redirect after successful actions using `$this->redirect(route('...'), navigate: true);`.
- Flash success/error messages with `session()->flash('success', '...');`.

## Consistency
- Match property and method naming conventions across similar components (e.g., `submit()` for create/update, `save()` for user update).
- Use typed properties and methods for clarity and reliability.

---

## Example (Blade View)
```
<x-slot:headerTitle>Title</x-slot:headerTitle>
<div class="space-y-6">
    <x-page-header title="..." subtitle="..." :backRoute="route('...')" backLabel="Back" />
    <div class="bg-white rounded-lg border border-gray-200">
        <form wire:submit.prevent="..." class="p-6 space-y-4">
            <div>
                <label ...>Field</label>
                <input ...>
                @error('field') <p ...>{{ $message }}</p> @enderror
            </div>
            ...
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-200">
                <a ...>Cancel</a>
                <button ...>Submit</button>
            </div>
        </form>
    </div>
</div>
```

## Example (Livewire Controller)
```
#[Layout('components.layouts.app')]
#[Title('Edit Entity')]
class EditEntity extends Component
{
    public Entity $entity;
    public string $name = '';
    public string $status = '';

    protected $rules = [
        'name' => 'required|string',
        'status' => 'required|string',
    ];

    public function mount(Entity $entity): void
    {
        $this->entity = $entity;
        $this->name = $entity->name;
        $this->status = $entity->status;
    }

    public function submit(): void
    {
        $this->validate();
        $this->entity->update([
            'name' => $this->name,
            'status' => $this->status,
        ]);
        session()->flash('success', 'Entity updated successfully!');
        $this->redirect(route('entities.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.entity.edit-entity');
    }
}
```

---
Always read this file before generating or editing any component or controller to ensure structure and style consistency.
