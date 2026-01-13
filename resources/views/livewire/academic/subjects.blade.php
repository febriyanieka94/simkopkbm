<?php

declare(strict_types=1);

use App\Models\Subject;
use App\Models\Level;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public string $code = '';
    public ?int $level_id = null;

    public ?Subject $editing = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code,' . ($this->editing->id ?? 'NULL')],
            'level_id' => ['nullable', 'exists:levels,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Subject::create($validated);
        }

        $this->reset(['name', 'code', 'level_id', 'editing']);
        $this->dispatch('close-modal', 'subject-modal');
    }

    public function edit(Subject $subject): void
    {
        $this->editing = $subject;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->level_id = $subject->level_id;

        $this->dispatch('open-modal', 'subject-modal');
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }

    public function with(): array
    {
        return [
            'subjects' => Subject::with('level')->latest()->paginate(15),
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Mata Pelajaran</flux:heading>
            <flux:subheading>Daftar mata pelajaran yang tersedia di semua jenjang.</flux:subheading>
        </div>

        <flux:modal.trigger name="subject-modal">
            <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Mapel</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Kode</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Jenjang</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($subjects as $subject)
                    <tr wire:key="{{ $subject->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">
                            {{ $subject->code }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $subject->name }}
                        </td>
                        <td class="px-4 py-3">
                            @if($subject->level)
                                <flux:badge size="sm" variant="neutral">{{ $subject->level->name }}</flux:badge>
                            @else
                                <flux:text size="sm" color="zinc">Umum</flux:text>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $subject->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus mapel ini?" wire:click="delete({{ $subject->id }})" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subjects->links() }}
    </div>

    <flux:modal name="subject-modal" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</flux:heading>
                <flux:subheading>Lengkapi detail mata pelajaran di bawah ini.</flux:subheading>
            </div>

            <flux:input wire:model="code" label="Kode Mapel (e.g. MAT-A, INDO-P1)" required />
            <flux:input wire:model="name" label="Nama Mata Pelajaran" required />

            <flux:select wire:model="level_id" label="Jenjang (Opsional)">
                <option value="">Wajib Semua Jenjang / Umum</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
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
