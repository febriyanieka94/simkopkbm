<?php

declare(strict_types=1);

use App\Models\TeacherAssignment;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $teacher_id = null;
    public ?int $subject_id = null;
    public string $type = 'subject_teacher';

    public ?TeacherAssignment $editing = null;

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'type' => ['required', 'in:class_teacher,subject_teacher,homeroom'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'academic_year_id' => $this->academic_year_id,
            'classroom_id' => $this->classroom_id,
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->subject_id,
            'type' => $this->type,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            TeacherAssignment::create($data);
        }

        $this->reset(['teacher_id', 'subject_id', 'type', 'editing']);
        $this->dispatch('close-modal', 'assignment-modal');
    }

    public function edit(TeacherAssignment $assignment): void
    {
        $this->editing = $assignment;
        $this->teacher_id = $assignment->teacher_id;
        $this->subject_id = $assignment->subject_id;
        $this->type = $assignment->type;
        $this->classroom_id = $assignment->classroom_id;

        $this->dispatch('open-modal', 'assignment-modal');
    }

    public function delete(TeacherAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function with(): array
    {
        return [
            'assignments' => TeacherAssignment::with(['teacher', 'subject', 'classroom'])
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->when($this->classroom_id, fn($q) => $q->where('classroom_id', $this->classroom_id))
                ->get(),
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))->get(),
            'subjects' => Subject::all(),
            'teachers' => User::where('role', 'guru')->get(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penugasan Guru</flux:heading>
            <flux:subheading>Atur penugasan guru untuk mata pelajaran dan kelas.</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:select wire:model.live="academic_year_id" class="w-48">
                <option value="">Semua Tahun</option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="classroom_id" class="w-48">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </flux:select>

            <flux:modal.trigger name="assignment-modal">
                <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Penugasan</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Guru</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Kelas</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Mata Pelajaran</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Tipe</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($assignments as $assignment)
                    <tr wire:key="{{ $assignment->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $assignment->teacher->name }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $assignment->classroom->name }} ({{ $assignment->classroom->academicYear->name }})
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $assignment->subject?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            <flux:badge size="sm" variant="outline">
                                {{ match($assignment->type) {
                                    'class_teacher' => 'Guru Kelas',
                                    'subject_teacher' => 'Guru Mapel',
                                    'homeroom' => 'Wali Kelas',
                                    default => $assignment->type
                                } }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $assignment->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus penugasan ini?" wire:click="delete({{ $assignment->id }})" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400 italic">
                            Belum ada penugasan guru yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="assignment-modal" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Penugasan' : 'Tambah Penugasan Baru' }}</flux:heading>
                <flux:subheading>Lengkapi detail penugasan guru di bawah ini.</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="classroom_id" label="Kelas" required>
                    <option value="">Pilih Kelas</option>
                    @foreach(Classroom::where('academic_year_id', $academic_year_id)->get() as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="teacher_id" label="Guru" required>
                <option value="">Pilih Guru</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model.live="type" label="Tipe Penugasan" class="flex flex-col gap-2">
                <flux:radio value="subject_teacher" label="Guru Mata Pelajaran" />
                <flux:radio value="class_teacher" label="Guru Kelas" />
                <flux:radio value="homeroom" label="Wali Kelas" />
            </flux:radio.group>

            @if($type === 'subject_teacher')
                <flux:select wire:model="subject_id" label="Mata Pelajaran" required>
                    <option value="">Pilih Mata Pelajaran</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
