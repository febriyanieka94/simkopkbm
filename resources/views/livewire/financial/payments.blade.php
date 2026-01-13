<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentBilling;
use App\Models\Transaction;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?int $student_id = null;
    public string $search = '';
    
    public ?StudentBilling $selectedBilling = null;
    public float $pay_amount = 0;
    public string $payment_method = 'cash';
    public string $payment_date = '';
    public string $reference_number = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

    public function selectStudent(int $id): void
    {
        $this->student_id = $id;
        $this->search = User::find($id)->name;
    }

    public function selectBilling(StudentBilling $billing): void
    {
        $this->selectedBilling = $billing;
        $this->pay_amount = (float) ($billing->amount - $billing->paid_amount);
    }

    public function recordPayment(): void
    {
        $this->validate([
            'pay_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        if (!$this->selectedBilling) return;

        DB::transaction(function () {
            Transaction::create([
                'student_billing_id' => $this->selectedBilling->id,
                'user_id' => auth()->id(),
                'amount' => $this->pay_amount,
                'payment_date' => $this->payment_date,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'notes' => $this->notes,
            ]);

            $newPaidAmount = $this->selectedBilling->paid_amount + $this->pay_amount;
            $status = 'paid';
            if ($newPaidAmount < $this->selectedBilling->amount) {
                $status = 'partial';
            }

            $this->selectedBilling->update([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
            ]);
        });

        \Flux::toast('Pembayaran berhasil dicatat.');
        $this->reset(['selectedBilling', 'pay_amount', 'reference_number', 'notes']);
        $this->dispatch('close-modal');
    }

    public function with(): array
    {
        $students = [];
        if (strlen($this->search) > 2 && !$this->student_id) {
            $students = User::where('role', 'siswa')
                ->where('name', 'like', "%{$this->search}%")
                ->limit(5)
                ->get();
        }

        $billings = [];
        if ($this->student_id) {
            $billings = StudentBilling::with('feeCategory')
                ->where('student_id', $this->student_id)
                ->where('status', '!=', 'paid')
                ->get();
        }

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'students' => $students,
            'billings' => $billings,
            'recentTransactions' => $recentTransactions,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Transaksi Pembayaran</flux:heading>
            <flux:subheading>Catat pembayaran biaya sekolah dari siswa.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="border rounded-lg p-4 bg-white dark:bg-zinc-900 shadow-sm">
                <flux:heading level="2" size="lg" class="mb-4">Cari Siswa</flux:heading>
                <div class="relative">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Ketik nama siswa..." 
                        icon="user" 
                    />
                    @if($student_id)
                        <button wire:click="$set('student_id', null); $set('search', '')" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-zinc-400 hover:text-zinc-600">
                            <flux:icon icon="x-mark" variant="micro" />
                        </button>
                    @endif
                </div>

                @if(count($students) > 0)
                    <div class="mt-2 border rounded-md divide-y bg-white dark:bg-zinc-800 shadow-lg absolute z-10 w-[calc(100%-2rem)]">
                        @foreach($students as $student)
                            <button 
                                wire:click="selectStudent({{ $student->id }})"
                                class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"
                            >
                                <div class="font-medium dark:text-white">{{ $student->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $student->email }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($student_id)
                <div class="border rounded-lg p-4 bg-white dark:bg-zinc-900 shadow-sm">
                    <flux:heading level="2" size="lg" class="mb-4">Tagihan Belum Lunas</flux:heading>
                    <div class="space-y-3">
                        @forelse($billings as $billing)
                            <div class="p-3 border rounded-lg hover:border-zinc-400 dark:hover:border-zinc-500 cursor-pointer transition" wire:click="selectBilling({{ $billing->id }})">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold dark:text-white">{{ $billing->feeCategory->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $billing->month ?? 'Sekali Bayar' }}</div>
                                    </div>
                                    <flux:badge size="sm" :variant="$billing->status === 'partial' ? 'warning' : 'danger'">
                                        {{ strtoupper($billing->status) }}
                                    </flux:badge>
                                </div>
                                <div class="mt-2 flex justify-between items-end">
                                    <div class="text-xs text-zinc-500">Sisa:</div>
                                    <div class="font-mono text-zinc-900 dark:text-white">Rp {{ number_format($billing->amount - $billing->paid_amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 text-center py-4">Tidak ada tagihan tertunggak.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if($selectedBilling)
                <div class="border rounded-lg p-6 bg-white dark:bg-zinc-900 shadow-sm border-primary/20 bg-primary/5">
                    <flux:heading level="2" size="lg" class="mb-6">Form Pembayaran</flux:heading>
                    
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div class="space-y-1">
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Siswa</div>
                            <div class="font-bold text-lg dark:text-white">{{ $selectedBilling->student->name }}</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Kategori</div>
                            <div class="font-bold text-lg dark:text-white">{{ $selectedBilling->feeCategory->name }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:input wire:model="pay_amount" type="number" label="Nominal Pembayaran" icon="banknotes" />
                        <flux:select wire:model="payment_method" label="Metode Pembayaran">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="other">Lainnya</option>
                        </flux:select>
                        <flux:input wire:model="payment_date" type="date" label="Tanggal Pembayaran" />
                        <flux:input wire:model="reference_number" label="Ref Transaksi (Optional)" placeholder="No. Slip/Ref" />
                    </div>

                    <div class="mt-6">
                        <flux:textarea wire:model="notes" label="Catatan" rows="2" />
                    </div>

                    <div class="mt-8 flex justify-end">
                        <flux:button variant="primary" size="lg" icon="check" wire:click="recordPayment">Simpan Pembayaran</flux:button>
                    </div>
                </div>
            @endif

            <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-zinc-50 dark:bg-zinc-800">
                    <flux:heading level="2" size="md">Transaksi Terakhir</flux:heading>
                </div>
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Tanggal</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Siswa</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Biaya</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($recentTransactions as $tx)
                            <tr wire:key="tx-{{ $tx->id }}">
                                <td class="px-4 py-3 text-zinc-500">{{ $tx->payment_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-medium dark:text-white">{{ $tx->billing->student->name }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $tx->billing->feeCategory->name }}</td>
                                <td class="px-4 py-3 text-right font-mono text-success">
                                    Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
