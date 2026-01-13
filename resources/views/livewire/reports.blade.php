<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\FeeCategory;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\AcademicYear;
use Livewire\Volt\Component;

new class extends Component {
    public string $tab = 'financial';
    
    // Financial Filters
    public ?int $fee_category_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    
    // Academic Filters
    public ?int $classroom_id = null;
    public ?int $academic_year_id = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function with(): array
    {
        $financialData = [];
        if ($this->tab === 'financial') {
            $financialData = Transaction::with(['billing.student', 'billing.feeCategory'])
                ->when($this->fee_category_id, function($q) {
                    $q->whereHas('billing', fn($bq) => $bq->where('fee_category_id', $this->fee_category_id));
                })
                ->when($this->start_date, fn($q) => $q->whereDate('payment_date', '>=', $this->start_date))
                ->when($this->end_date, fn($q) => $q->whereDate('payment_date', '<=', $this->end_date))
                ->latest()
                ->get();
        }

        $attendanceData = [];
        if ($this->tab === 'attendance') {
            $attendanceData = Attendance::with(['classroom', 'subject'])
                ->when($this->classroom_id, fn($q) => $q->where('classroom_id', $this->classroom_id))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->get();
        }

        return [
            'financialData' => $financialData,
            'attendanceData' => $attendanceData,
            'categories' => FeeCategory::all(),
            'classrooms' => Classroom::all(),
            'years' => AcademicYear::all(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" level="1">Laporan & Analitik</flux:heading>
        <flux:subheading>Pantau performa akademik dan keuangan PKBM.</flux:subheading>
    </div>

    <!-- Tabs -->
    <div class="flex gap-4 border-b dark:border-zinc-800 mb-6">
        <button 
            wire:click="$set('tab', 'financial')" 
            class="pb-2 px-1 text-sm font-medium transition cursor-pointer {{ $tab === 'financial' ? 'text-primary border-b-2 border-primary' : 'text-zinc-500 hover:text-zinc-700' }}"
        >
            Laporan Keuangan
        </button>
        <button 
            wire:click="$set('tab', 'attendance')" 
            class="pb-2 px-1 text-sm font-medium transition cursor-pointer {{ $tab === 'attendance' ? 'text-primary border-b-2 border-primary' : 'text-zinc-500 hover:text-zinc-700' }}"
        >
            Laporan Presensi
        </button>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed">
        @if($tab === 'financial')
            <flux:select wire:model.live="fee_category_id" label="Kategori Biaya">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="start_date" type="date" label="Dari Tanggal" />
            <flux:input wire:model.live="end_date" type="date" label="Sampai Tanggal" />
        @endif

        @if($tab === 'attendance')
            <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="classroom_id" label="Kelas">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </flux:select>
        @endif
        
        <div class="flex items-end">
            <flux:button icon="printer" class="w-full">Cetak / Export</flux:button>
        </div>
    </div>

    <!-- Results -->
    <div class="border rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        @if($tab === 'financial')
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Tanggal</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kategori</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Metode</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php $totalIncome = 0; @endphp
                    @foreach($financialData as $tx)
                        @php $totalIncome += $tx->amount; @endphp
                        <tr>
                            <td class="px-4 py-3 text-zinc-500">{{ $tx->payment_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-medium dark:text-white">{{ $tx->billing->student->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $tx->billing->feeCategory->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 uppercase text-xs">{{ $tx->payment_method }}</td>
                            <td class="px-4 py-3 text-right font-mono dark:text-white">
                                Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-zinc-50 dark:bg-zinc-800 font-bold">
                        <td colspan="4" class="px-4 py-4 text-right uppercase tracking-wider text-xs">Total Pendapatan</td>
                        <td class="px-4 py-4 text-right font-mono text-lg text-primary">
                            Rp {{ number_format($totalIncome, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if($tab === 'attendance')
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Tanggal</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kelas</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Mata Pelajaran</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-center">Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($attendanceData as $att)
                        <tr>
                            <td class="px-4 py-3 text-zinc-500">{{ $att->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-medium dark:text-white">{{ $att->classroom->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $att->subject?->name ?? 'Harian' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php 
                                    $items = $att->items;
                                    $present = $items->filter(fn($i) => $i->status === 'h')->count();
                                    $total = $items->count();
                                    $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                                @endphp
                                <div class="flex items-center justify-center gap-2">
                                    <div class="text-xs font-bold">{{ $percent }}%</div>
                                    <div class="w-16 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-zinc-400">({{ $present }}/{{ $total }})</div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
