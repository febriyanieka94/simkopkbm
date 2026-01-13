<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = false;
    public string $status = 'open';

    public ?AcademicYear $editing = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            AcademicYear::create($validated);
        }

        if ($this->is_active) {
            $this->setActive($this->editing ?? AcademicYear::latest()->first());
        }

        $this->reset(['name', 'start_date', 'end_date', 'is_active', 'status', 'editing']);
        $this->dispatch('close-modal', 'academic-year-modal');
    }

    public function edit(AcademicYear $year): void
    {
        $this->editing = $year;
        $this->name = $year->name;
        $this->start_date = $year->start_date->format('Y-m-d');
        $this->end_date = $year->end_date->format('Y-m-d');
        $this->is_active = $year->is_active;
        $this->status = $year->status;

        $this->dispatch('open-modal', 'academic-year-modal');
    }

    public function setActive(AcademicYear $year): void
    {
        AcademicYear::where('id', '!=', $year->id)->update(['is_active' => false]);
        $year->update(['is_active' => true]);
    }

    public function delete(AcademicYear $year): void
    {
        $year->delete();
    }

    public function with(): array
    {
        return [
            'years' => AcademicYear::latest()->paginate(10),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Tahun Ajaran</flux:heading>
            <flux:subheading>Kelola tahun akademik sekolah Anda di sini.</flux:subheading>
        </div>

        <flux:modal.trigger name="academic-year-modal">
            <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Tahun Ajaran</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Rentang Waktu</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Status</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($years as $year)
                    <tr wire:key="{{ $year->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $year->name }}</span>
                                @if($year->is_active)
                                    <flux:badge variant="success" size="sm">Aktif</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $year->start_date->format('d M Y') }} - {{ $year->end_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge variant="{{ $year->status === 'open' ? 'neutral' : 'warning' }}" size="sm">
                                {{ $year->status === 'open' ? 'Terbuka' : 'Ditutup' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if(!$year->is_active)
                                <flux:button size="sm" variant="ghost" wire:click="setActive({{ $year->id }})">Set Aktif</flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $year->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus ini?" wire:click="delete({{ $year->id }})" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $years->links() }}
    </div>

    <flux:modal name="academic-year-modal" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran Baru' }}</flux:heading>
                <flux:subheading>Masukkan detail tahun ajaran di bawah ini.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama (Contoh: 2024/2025)" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="start_date" type="date" label="Tanggal Mulai" required />
                <flux:input wire:model="end_date" type="date" label="Tanggal Selesai" required />
            </div>

            <flux:select wire:model="status" label="Status Opsional">
                <option value="open">Terbuka</option>
                <option value="closed">Ditutup</option>
            </flux:select>

            <flux:checkbox wire:model="is_active" label="Jadikan Tahun Aktif" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
