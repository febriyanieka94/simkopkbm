<?php

declare(strict_types=1);

use App\Models\FeeCategory;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public float $default_amount = 0;

    public ?FeeCategory $editing = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:fee_categories,code,' . ($this->editing?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editing) {
            $this->editing->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
            ]);
            \Flux::toast('Kategori biaya berhasil diperbarui.');
        } else {
            FeeCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
            ]);
            \Flux::toast('Kategori biaya berhasil ditambahkan.');
        }

        $this->reset(['name', 'code', 'description', 'default_amount', 'editing']);
        $this->dispatch('close-modal');
    }

    public function edit(FeeCategory $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->default_amount = (float) $category->default_amount;
    }

    public function delete(FeeCategory $category): void
    {
        if ($category->billings()->exists()) {
            \Flux::toast('Kategori tidak bisa dihapus karena sudah digunakan dalam penagihan.', variant: 'danger');
            return;
        }

        $category->delete();
        \Flux::toast('Kategori biaya berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'categories' => FeeCategory::latest()->paginate(10),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Kategori Biaya</flux:heading>
            <flux:subheading>Daftar jenis biaya sekolah (SPP, Gedung, dll).</flux:subheading>
        </div>
        <flux:modal.trigger name="add-category">
            <flux:button variant="primary" icon="plus">Tambah Kategori</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kode</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Kategori</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nominal Default</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($categories as $category)
                    <tr wire:key="{{ $category->id }}">
                        <td class="px-4 py-3 text-zinc-900 dark:text-white font-mono text-xs">
                            {{ $category->code }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $category->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $category->description }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-white">
                            Rp {{ number_format($category->default_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:modal.trigger name="add-category">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="edit({{ $category->id }})" />
                                </flux:modal.trigger>
                                <flux:button variant="ghost" icon="trash" size="sm" wire:click="delete({{ $category->id }})" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t">
            {{ $categories->links() }}
        </div>
    </div>

    <flux:modal name="add-category" class="md:w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Kategori' : 'Tambah Kategori' }}</flux:heading>
                <flux:subheading>Deskripsikan kategori biaya baru.</flux:subheading>
            </div>

            <flux:input wire:model="code" label="Kode Kategori" placeholder="Contoh: SPP-10" />
            <flux:input wire:model="name" label="Nama Kategori" placeholder="Contoh: SPP Kelas 10" />
            <flux:input wire:model="default_amount" type="number" label="Nominal Default" icon="banknotes" />
            <flux:textarea wire:model="description" label="Deskripsi" rows="2" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="save">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
