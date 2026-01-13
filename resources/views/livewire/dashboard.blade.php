<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\Classroom;
use App\Models\Attendance;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalClassrooms = Classroom::count();
        
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        
        $incomeMonth = Transaction::whereBetween('payment_date', [$start, $end])
            ->sum('amount');
            
        $pendingBillings = StudentBilling::where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory'])
            ->latest()
            ->limit(5)
            ->get();

        $recentAttendance = Attendance::with(['classroom'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'students' => $totalStudents,
                'teachers' => $totalTeachers,
                'classrooms' => $totalClassrooms,
                'income_month' => $incomeMonth,
                'pending_billings' => $pendingBillings,
            ],
            'recentTransactions' => $recentTransactions,
            'recentAttendance' => $recentAttendance,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Selamat Datang, {{ auth()->user()->name }}</flux:heading>
            <flux:subheading>Ringkasan aktivitas PKBM hari ini.</flux:subheading>
        </div>
        <div class="text-right hidden md:block">
            <div class="text-sm font-medium text-zinc-500">{{ now()->translatedFormat('l, d F Y') }}</div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon icon="users" class="text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Total Siswa</div>
                    <div class="text-2xl font-bold dark:text-white">{{ $stats['students'] }}</div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon icon="academic-cap" class="text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Total Guru</div>
                    <div class="text-2xl font-bold dark:text-white">{{ $stats['teachers'] }}</div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                    <flux:icon icon="banknotes" class="text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Pendapatan (Bulan Ini)</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($stats['income_month'] / 1000, 0) }}k
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-zinc-900 border rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                    <flux:icon icon="document-minus" class="text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Piutang Tagihan</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        Rp {{ number_format($stats['pending_billings'] / 1000000, 1) }}M
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="border rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b bg-zinc-50 dark:bg-zinc-800 flex justify-between items-center">
                <flux:heading size="md">Transaksi Terakhir</flux:heading>
                <flux:button variant="ghost" size="sm" :href="route('financial.payments')" wire:navigate>Lihat Semua</flux:button>
            </div>
            <div class="divide-y">
                @forelse($recentTransactions as $tx)
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <div class="flex justify-between items-start">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-500">
                                    {{ substr($tx->billing->student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-medium dark:text-white">{{ $tx->billing->student->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $tx->billing->feeCategory->name }} - {{ $tx->payment_method }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-green-600 dark:text-green-400 font-mono">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $tx->payment_date->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-zinc-500 text-sm">Belum ada transaksi masuk.</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="border rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b bg-zinc-50 dark:bg-zinc-800 flex justify-between items-center">
                <flux:heading size="md">Input Presensi Terakhir</flux:heading>
                <flux:button variant="ghost" size="sm" :href="route('academic.attendance')" wire:navigate>Lihat Semua</flux:button>
            </div>
            <div class="divide-y">
                @forelse($recentAttendance as $att)
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <div class="flex justify-between items-center">
                            <div class="flex gap-3 items-center">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                    <flux:icon icon="clipboard-document-check" variant="micro" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <div class="font-medium dark:text-white">Kelas {{ $att->classroom->name }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $att->subject?->name ?? 'Harian' }} • {{ $att->date->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-zinc-400">
                                {{ $att->items_count ?? $att->items()->count() }} Siswa
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-zinc-500 text-sm">Belum ada data presensi.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
