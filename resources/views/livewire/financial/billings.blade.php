<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\FeeCategory;
use App\Models\StudentBilling;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $fee_category_id = null;
    public string $month = '';
    public ?float $amount = null;

    public string $search = '';

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
        $this->month = now()->format('Y-m');
    }

    public function updatedFeeCategoryId($value): void
    {
        if ($value) {
            $category = FeeCategory::find($value);
            $this->amount = (float) $category->default_amount;
        }
    }

    public function generateBillings(): void
    {
        $this->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'nullable|string',
        ]);

        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        $count = 0;
        foreach ($students as $student) {
            // Check if billing already exists to avoid duplicates
            $exists = StudentBilling::where([
                'student_id' => $student->id,
                'fee_category_id' => $this->fee_category_id,
                'academic_year_id' => $this->academic_year_id,
                'month' => $this->month,
            ])->exists();

            if (!$exists) {
                StudentBilling::create([
                    'student_id' => $student->id,
                    'fee_category_id' => $this->fee_category_id,
                    'academic_year_id' => $this->academic_year_id,
                    'month' => $this->month,
                    'amount' => $this->amount,
                    'due_date' => now()->addDays(14),
                    'status' => 'unpaid',
                ]);
                $count++;
            }
        }

        \Flux::toast("$count Tagihan berhasil di-generate.");
        $this->dispatch('close-modal');
    }

    public function with(): array
    {
        $billings = StudentBilling::with(['student', 'feeCategory'])
            ->when($this->classroom_id, function($q) {
                $q->whereHas('student.profiles.profileable', function($sq) {
                    $sq->where('classroom_id', $this->classroom_id);
                });
            })
            ->when($this->search, function($q) {
                $q->whereHas('student', function($sq) {
                    $sq->where('name', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return [
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::all(),
            'categories' => FeeCategory::all(),
            'billings' => $billings,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Tagihan Siswa</flux:heading>
            <flux:subheading>Manajemen penagihan biaya pendidikan siswa.</flux:subheading>
        </div>
        <flux:modal.trigger name="generate-billing">
            <flux:button variant="primary" icon="document-plus">Generate Tagihan Kelas</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari siswa..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="classroom_id" class="w-64">
            <option value="">Semua Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Siswa</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kategori</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Bulan</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Nominal</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($billings as $billing)
                    <tr wire:key="{{ $billing->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $billing->student->name }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $billing->feeCategory->name }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $billing->month ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-900 dark:text-white">
                            Rp {{ number_format($billing->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" :variant="$billing->status === 'paid' ? 'success' : ($billing->status === 'partial' ? 'warning' : 'danger')">
                                {{ strtoupper($billing->status) }}
                            </flux:badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t">
            {{ $billings->links() }}
        </div>
    </div>

    <flux:modal name="generate-billing" class="md:w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Generate Tagihan</flux:heading>
                <flux:subheading>Buat tagihan untuk satu kelas sekaligus.</flux:subheading>
            </div>

            <flux:select wire:model="classroom_id" label="Kelas">
                <option value="">Pilih Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="fee_category_id" label="Jenis Biaya">
                <option value="">Pilih Biaya</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="month" type="month" label="Bulan (Khusus SPP)" />
            <flux:input wire:model="amount" type="number" label="Nominal" icon="banknotes" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="generateBillings">Generate</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
