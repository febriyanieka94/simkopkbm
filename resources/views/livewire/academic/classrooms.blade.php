<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public ?int $academic_year_id = null;
    public ?int $level_id = null;
    public ?int $homeroom_teacher_id = null;

    public ?Classroom $editing = null;

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Classroom::create($validated);
        }

        $this->reset(['name', 'homeroom_teacher_id', 'editing']);
        $this->dispatch('close-modal', 'classroom-modal');
    }

    public function edit(Classroom $classroom): void
    {
        $this->editing = $classroom;
        $this->name = $classroom->name;
        $this->academic_year_id = $classroom->academic_year_id;
        $this->level_id = $classroom->level_id;
        $this->homeroom_teacher_id = $classroom->homeroom_teacher_id;

        $this->dispatch('open-modal', 'classroom-modal');
    }

    public function delete(Classroom $classroom): void
    {
        $classroom->delete();
    }

    public function with(): array
    {
        return [
            'classrooms' => Classroom::with(['academicYear', 'level', 'homeroomTeacher'])
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->paginate(15),
            'years' => AcademicYear::all(),
            'levels' => Level::all(),
            'teachers' => User::where('role', 'guru')->get(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Manajemen Kelas</flux:heading>
            <flux:subheading>Kelola rombongan belajar dan wali kelas.</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:select wire:model.live="academic_year_id" class="w-48">
                <option value="">Semua Tahun</option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                @endforeach
            </flux:select>

            <flux:modal.trigger name="classroom-modal">
                <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Kelas</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Nama Kelas</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Jenjang</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Tahun Ajaran</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Wali Kelas</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($classrooms as $classroom)
                    <tr wire:key="{{ $classroom->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $classroom->name }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" variant="outline">{{ $classroom->level->name }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $classroom->academicYear->name }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $classroom->homeroomTeacher?->name ?? 'Belum Ditentukan' }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $classroom->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus kelas ini?" wire:click="delete({{ $classroom->id }})" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $classrooms->links() }}
    </div>

    <flux:modal name="classroom-modal" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Kelas' : 'Tambah Kelas Baru' }}</flux:heading>
                <flux:subheading>Lengkapi detail rombongan belajar di bawah ini.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama Kelas (Contoh: Kelas 1 A, Paket B Smt 1)" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="level_id" label="Jenjang" required>
                    <option value="">Pilih Jenjang</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="homeroom_teacher_id" label="Wali Kelas (Opsional)">
                <option value="">Pilih Wali Kelas</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
