<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Score;
use App\Models\ScoreCategory;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public ?int $score_category_id = null;

    public array $scores_data = []; // [student_id => score]

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
        $this->score_category_id = ScoreCategory::first()?->id;
    }

    public function updatedClassroomId(): void
    {
        $this->loadScores();
    }

    public function updatedSubjectId(): void
    {
        $this->loadScores();
    }

    public function updatedScoreCategoryId(): void
    {
        $this->loadScores();
    }

    public function loadScores(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->score_category_id) {
            $this->scores_data = [];
            return;
        }

        $scores = Score::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'score_category_id' => $this->score_category_id,
            'academic_year_id' => $this->academic_year_id,
        ])->get();

        $this->scores_data = $scores->pluck('score', 'student_id')->toArray();
        
        // Ensure all students in classroom have an entry (even if empty)
        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        foreach ($students as $student) {
            if (!isset($this->scores_data[$student->id])) {
                $this->scores_data[$student->id] = '';
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->score_category_id || !$this->academic_year_id) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->scores_data as $studentId => $scoreValue) {
                if ($scoreValue === '' || $scoreValue === null) continue;

                Score::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subject_id,
                        'classroom_id' => $this->classroom_id,
                        'academic_year_id' => $this->academic_year_id,
                        'score_category_id' => $this->score_category_id,
                    ],
                    [
                        'score' => (float) $scoreValue,
                    ]
                );
            }
        });

        \Flux::toast('Nilai berhasil disimpan.');
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
            'categories' => ScoreCategory::all(),
            'students' => $students,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penilaian Siswa</flux:heading>
            <flux:subheading>Input nilai siswa per mata pelajaran dan kategori nilai.</flux:subheading>
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

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran">
            <option value="">Pilih Mata Pelajaran</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="score_category_id" label="Kategori Nilai">
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($classroom_id && $subject_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-48 text-center">Nilai (0-100)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:input 
                                    wire:model="scores_data.{{ $student->id }}" 
                                    type="number" 
                                    min="0" 
                                    max="100" 
                                    step="0.01" 
                                    class="text-center" 
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end">
                <flux:button variant="primary" icon="check" wire:click="save">Simpan Nilai</flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="pencil-square" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian.</p>
        </div>
    @endif
</div>
