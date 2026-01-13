<?php

declare(strict_types=1);

use App\Models\Level;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $type = 'class_teacher';

    public ?Level $editing = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:class_teacher,subject_teacher'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Level::create($validated);
        }

        $this->reset(['name', 'type', 'editing']);
        $this->dispatch('close-modal', 'level-modal');
    }

    public function edit(Level $level): void
    {
        $this->editing = $level;
        $this->name = $level->name;
        $this->type = $level->type;

        $this->dispatch('open-modal', 'level-modal');
    }

    public function delete(Level $level): void
    {
        $level->delete();
    }

    public function with(): array
    {
        return [
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Jenjang Pendidikan</flux:heading>
            <flux:subheading>Atur jenjang SPP dan skema pengajaran (Guru Kelas vs Mata Pelajaran).</flux:subheading>
        </div>

        <flux:modal.trigger name="level-modal">
            <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Jenjang</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($levels as $level)
            <div wire:key="{{ $level->id }}" class="p-6 border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <flux:heading size="lg">{{ $level->name }}</flux:heading>
                        <flux:badge variant="{{ $level->type === 'class_teacher' ? 'success' : 'info' }}" size="sm">
                            {{ $level->type === 'class_teacher' ? 'Guru Kelas' : 'Guru Mapel' }}
                        </flux:badge>
                    </div>
                    <flux:text class="mt-2 text-sm">
                        {{ $level->type === 'class_teacher' ? 'Satu guru mengampu semua mata pelajaran.' : 'Satu mata pelajaran diampu oleh satu guru spesialis.' }}
                    </flux:text>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $level->id }})" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus jenjang ini?" wire:click="delete({{ $level->id }})" />
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="level-modal" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Jenjang' : 'Tambah Jenjang Baru' }}</flux:heading>
                <flux:subheading>Pilih skema pengajaran yang sesuai untuk jenjang ini.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama Jenjang (Contoh: PAUD, Paket B)" required />

            <flux:radio.group wire:model="type" label="Skema Pengajaran" class="flex flex-col gap-2">
                <flux:radio value="class_teacher" label="Sistem Guru Kelas" />
                <flux:radio value="subject_teacher" label="Sistem Guru Mata Pelajaran" />
            </flux:radio.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
