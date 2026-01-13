<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceItem;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $date = '';
    public string $notes = '';

    public array $attendance_data = []; // [student_id => status]

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedSubjectId(): void
    {
        $this->loadAttendance();
    }

    public function updatedDate(): void
    {
        $this->loadAttendance();
    }

    public function loadAttendance(): void
    {
        if (!$this->classroom_id || !$this->date) {
            $this->attendance_data = [];
            return;
        }

        $attendance = Attendance::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'date' => $this->date,
        ])->first();

        if ($attendance) {
            $this->notes = $attendance->notes ?? '';
            $this->attendance_data = $attendance->items->pluck('status', 'student_id')->toArray();
        } else {
            $this->notes = '';
            $this->attendance_data = [];
            
            // Default to present for all students in classroom
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })->get();

            foreach ($students as $student) {
                $this->attendance_data[$student->id] = 'h';
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->date || !$this->academic_year_id) {
            return;
        }

        DB::transaction(function () {
            $attendance = Attendance::updateOrCreate(
                [
                    'classroom_id' => $this->classroom_id,
                    'subject_id' => $this->subject_id,
                    'date' => $this->date,
                ],
                [
                    'academic_year_id' => $this->academic_year_id,
                    'teacher_id' => auth()->id(),
                    'notes' => $this->notes,
                ]
            );

            // Sync items
            foreach ($this->attendance_data as $studentId => $status) {
                AttendanceItem::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $status,
                    ]
                );
            }
        });

        $this->dispatch('attendance-saved');
        \Flux::toast('Presensi berhasil disimpan.');
    }

    public function with(): array
    {
        $students = [];
        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })
                ->get();
        }

        return [
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))->get(),
            'subjects' => Subject::all(),
            'students' => $students,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Presensi Siswa</flux:heading>
            <flux:subheading>Rekap kehadiran siswa per kelas dan mata pelajaran.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran (Opsional)">
            <option value="">Semua-Harian</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="date" type="date" label="Tanggal" />
    </div>

    @if($classroom_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-center">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center">
                                    <flux:radio.group wire:model="attendance_data.{{ $student->id }}" class="flex gap-4">
                                        <flux:radio value="h" label="Hadir" />
                                        <flux:radio value="s" label="Sakit" />
                                        <flux:radio value="i" label="Izin" />
                                        <flux:radio value="a" label="Alpa" />
                                    </flux:radio.group>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex flex-col gap-4">
                <flux:textarea wire:model="notes" label="Catatan Tambahan" placeholder="Catatan untuk hari ini..." rows="2" />
                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="save">Simpan Presensi</flux:button>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="check-badge" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas untuk memulai absensi.</p>
        </div>
    @endif
</div>
