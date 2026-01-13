<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $nis = '';

    public string $nisn = '';

    public string $phone = '';

    public string $address = '';

    public string $dob = '';

    public string $pob = '';

    public ?int $classroom_id = null;

    // New fields
    public $photo;

    public string $father_name = '';

    public string $mother_name = '';

    public string $guardian_name = '';

    public string $guardian_phone = '';

    public ?int $birth_order = null;

    public ?int $total_siblings = null;

    public string $previous_school = '';

    public string $status = 'baru';

    // Periodic Data fields
    public float $weight = 0;

    public float $height = 0;

    public float $head_circumference = 0;

    public int $semester = 1;

    public ?int $current_academic_year_id = null;

    public ?User $editing = null;

    public ?User $viewing = null;

    public ?string $existingPhoto = null;

    public bool $hasExistingPeriodicData = false;

    public ?string $periodicDataLastUpdated = null;

    public function rules(): array
    {
        $profileId = $this->editing?->latestProfile?->profileable_id;
        
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.($this->editing->id ?? 'NULL')],
            'nis' => ['nullable', 'string', $this->nis ? 'unique:student_profiles,nis,'.$profileId : ''],
            'nisn' => ['nullable', 'string', $this->nisn ? 'unique:student_profiles,nisn,'.$profileId : ''],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'dob' => ['required', 'date'],
            'pob' => ['required', 'string'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'photo' => ['nullable', 'image', 'max:1024'], // 1MB Max
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'birth_order' => ['nullable', 'integer', 'min:1'],
            'total_siblings' => ['nullable', 'integer', 'min:1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:baru,mutasi,naik_kelas,lulus,keluar'],
        ];
    }

    public function mount(): void
    {
        $this->current_academic_year_id = \App\Models\AcademicYear::where('is_active', true)->first()?->id;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $photoPath = $this->existingPhoto;
            if ($this->photo) {
                // Delete old photo if exists
                if ($this->existingPhoto) {
                    Storage::disk('public')->delete($this->existingPhoto);
                }
                $photoPath = $this->photo->store('photos', 'public');
            }

            $profileData = [
                'nis' => $this->nis ?: null,
                'nisn' => $this->nisn ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'dob' => $this->dob ?: null,
                'pob' => $this->pob,
                'photo' => $photoPath,
                'father_name' => $this->father_name ?: null,
                'mother_name' => $this->mother_name ?: null,
                'guardian_name' => $this->guardian_name ?: null,
                'guardian_phone' => $this->guardian_phone ?: null,
                'classroom_id' => $this->classroom_id,
                'birth_order' => $this->birth_order,
                'total_siblings' => $this->total_siblings,
                'previous_school' => $this->previous_school ?: null,
                'status' => $this->status,
            ];

            if ($this->editing) {
                $this->editing->update([
                    'name' => $this->name,
                    'email' => $this->email,
                ]);

                $profile = $this->editing->latestProfile->profileable;
                $profile->update($profileData);
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]);

                $studentProfile = StudentProfile::create($profileData);

                $user->profiles()->create([
                    'profileable_id' => $studentProfile->id,
                    'profileable_type' => StudentProfile::class,
                ]);
            }
        });

        $this->reset(['name', 'email', 'nis', 'nisn', 'phone', 'address', 'dob', 'pob', 'classroom_id', 'photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'birth_order', 'total_siblings', 'previous_school', 'status', 'editing', 'viewing', 'existingPhoto']);

        session()->flash('success', 'Data siswa berhasil disimpan!');
        $this->dispatch('student-saved');
    }

    public function edit(User $user): void
    {
        $this->editing = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        $profile = $user->latestProfile->profileable;
        $this->nis = $profile->nis ?? '';
        $this->nisn = $profile->nisn ?? '';
        $this->phone = $profile->phone ?? '';
        $this->address = $profile->address ?? '';
        $this->dob = $profile->dob ? $profile->dob->format('Y-m-d') : '';
        $this->pob = $profile->pob ?? '';
        $this->existingPhoto = $profile->photo;
        $this->father_name = $profile->father_name ?? '';
        $this->mother_name = $profile->mother_name ?? '';
        $this->guardian_name = $profile->guardian_name ?? '';
        $this->guardian_phone = $profile->guardian_phone ?? '';
        $this->classroom_id = $profile->classroom_id;
        $this->birth_order = $profile->birth_order;
        $this->total_siblings = $profile->total_siblings;
        $this->previous_school = $profile->previous_school ?? '';
        $this->status = $profile->status ?? 'baru';

        $this->dispatch('open-modal', 'student-modal');
    }

    public function viewDetails(User $user): void
    {
        $this->viewing = $user;
        $this->dispatch('open-modal', 'detail-modal');
    }

    public function openPeriodic(User $user): void
    {
        $this->editing = $user;
        
        // Preload existing periodic data for current academic year and semester
        $profile = $user->latestProfile?->profileable;
        if ($profile) {
            $existingRecord = \App\Models\StudentPeriodicRecord::where('student_profile_id', $profile->id)
                ->where('academic_year_id', $this->current_academic_year_id)
                ->where('semester', $this->semester)
                ->first();
            
            if ($existingRecord) {
                $this->weight = $existingRecord->weight;
                $this->height = $existingRecord->height;
                $this->head_circumference = $existingRecord->head_circumference;
                $this->hasExistingPeriodicData = true;
                $this->periodicDataLastUpdated = $existingRecord->updated_at->diffForHumans();
            } else {
                // Reset to default if no existing record
                $this->weight = 0;
                $this->height = 0;
                $this->head_circumference = 0;
                $this->hasExistingPeriodicData = false;
                $this->periodicDataLastUpdated = null;
            }
        }
        
        $this->dispatch('open-modal', 'periodic-modal');
    }

    public function updatedSemester(): void
    {
        if ($this->editing) {
            $profile = $this->editing->latestProfile?->profileable;
            if ($profile) {
                $existingRecord = \App\Models\StudentPeriodicRecord::where('student_profile_id', $profile->id)
                    ->where('academic_year_id', $this->current_academic_year_id)
                    ->where('semester', $this->semester)
                    ->first();
                
                if ($existingRecord) {
                    $this->weight = $existingRecord->weight;
                    $this->height = $existingRecord->height;
                    $this->head_circumference = $existingRecord->head_circumference;
                    $this->hasExistingPeriodicData = true;
                    $this->periodicDataLastUpdated = $existingRecord->updated_at->diffForHumans();
                } else {
                    $this->weight = 0;
                    $this->height = 0;
                    $this->head_circumference = 0;
                    $this->hasExistingPeriodicData = false;
                    $this->periodicDataLastUpdated = null;
                }
            }
        }
    }

    public function createNew(): void
    {
        $this->reset(['name', 'email', 'nis', 'nisn', 'phone', 'address', 'dob', 'pob', 'classroom_id', 'photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'birth_order', 'total_siblings', 'previous_school', 'status', 'editing', 'existingPhoto']);
        $this->resetValidation();
    }

    public function savePeriodic(int $studentProfileId): void
    {
        $this->validate([
            'weight' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'head_circumference' => 'required|numeric|min:0',
            'semester' => 'required|integer|in:1,2',
        ]);

        \App\Models\StudentPeriodicRecord::updateOrCreate(
            [
                'student_profile_id' => $studentProfileId,
                'academic_year_id' => $this->current_academic_year_id,
                'semester' => $this->semester,
            ],
            [
                'weight' => $this->weight,
                'height' => $this->height,
                'head_circumference' => $this->head_circumference,
                'recorded_by' => auth()->id(),
            ]
        );

        $this->reset(['weight', 'height', 'head_circumference', 'semester', 'hasExistingPeriodicData', 'periodicDataLastUpdated']);
        
        session()->flash('success', 'Data periodik berhasil disimpan!');
        $this->dispatch('periodic-saved');
    }

    public function delete(User $user): void
    {
        if ($user->latestProfile) {
            $profile = $user->latestProfile->profileable;
            if ($profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }
            $profile->delete();
            $user->latestProfile->delete();
        }
        $user->delete();
    }

    public function with(): array
    {
        return [
            'students' => User::where('role', 'siswa')
                ->with(['latestProfile.profileable.classroom'])
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhereHas('latestProfile', fn ($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn ($sq) => $sq->where('nis', 'like', "%{$this->search}%")->orWhere('nisn', 'like', "%{$this->search}%"))))
                ->latest()
                ->paginate(15),
            'classrooms' => Classroom::with('academicYear')->get(),
        ];
    }
}; ?>

<div class="p-6">
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show"  class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon icon="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                <span class="text-green-800 dark:text-green-200">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Manajemen Siswa</flux:heading>
            <flux:subheading>Kelola data murid, profil, dan penempatan kelas.</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari siswa..." icon="magnifying-glass" class="w-64" />
            
            <flux:modal.trigger name="student-modal">
                <flux:button variant="primary" icon="plus" wire:click="createNew">Tambah Siswa</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Siswa</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">NIS/NISN</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Kelas</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Orang Tua/Wali</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($students as $student)
                    @php $profile = $student->latestProfile?->profileable; @endphp
                    <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <button type="button" class="flex items-center gap-3 cursor-pointer text-left" wire:click="viewDetails({{ $student->id }})" x-on:click="$flux.modal('detail-modal').show()">
                                <flux:avatar 
                                    :src="$profile?->photo ? asset('storage/' . $profile->photo) : null" 
                                    :name="$student->name" 
                                    :initials="$student->initials()" 
                                    size="sm" 
                                />
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ $student->name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $student->email ?? '-' }}</span>
                                </div>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            <div class="flex flex-col">
                                <span>NIS: {{ $profile->nis ?? '-' }}</span>
                                <span class="text-xs">NISN: {{ $profile->nisn ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($profile?->classroom)
                                <flux:badge size="sm" variant="neutral">
                                    {{ $profile->classroom->name }}
                                </flux:badge>
                            @else
                                <span class="text-xs text-red-500 italic">Belum ada kelas</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            <div class="flex flex-col">
                                <span>{{ $profile->father_name ?: ($profile->mother_name ?: ($profile->guardian_name ?: '-')) }}</span>
                                <span class="text-xs">{{ $profile->guardian_phone ?: ($profile->phone ?: '-') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="chart-bar" wire:click="openPeriodic({{ $student->id }})" x-on:click="$flux.modal('periodic-modal').show()" tooltip="Data Periodik" />
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $student->id }})" x-on:click="$flux.modal('student-modal').show()" tooltip="Edit Siswa" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus siswa ini?" wire:click="delete({{ $student->id }})" tooltip="Hapus Siswa" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400 italic">
                            Belum ada data siswa ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>

    <flux:modal name="student-modal" class="max-w-3xl" x-on:student-saved.window="$flux.modal('student-modal').close()">
        <form wire:submit="save" class="space-y-8">
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">{{ $editing ? 'Edit Profil Siswa' : 'Tambah Siswa Baru' }}</flux:heading>
                    <flux:subheading>Lengkapi data identitas dan akademik siswa.</flux:subheading>
                </div>
                
                <div class="flex flex-col items-center gap-2">
                    <div class="relative group">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-lg object-cover border-2 border-primary-500" />
                        @elseif ($existingPhoto)
                            <img src="{{ asset('storage/' . $existingPhoto) }}" class="w-24 h-24 rounded-lg object-cover" />
                        @else
                            <div class="w-24 h-24 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400">
                                <flux:icon icon="user" class="w-12 h-12" />
                            </div>
                        @endif
                        
                        <label class="absolute inset-0 flex items-center justify-center bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity rounded-lg cursor-pointer">
                            <flux:icon icon="camera" class="w-6 h-6" />
                            <input type="file" wire:model="photo" class="hidden" accept="image/*" />
                        </label>
                    </div>
                    <flux:text size="xs">Foto Profil (Max 1MB)</flux:text>
                    @error('photo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <flux:heading size="md" class="border-b pb-1">Identitas Siswa</flux:heading>
                        
                        <flux:input wire:model="name" label="Nama Lengkap" />
                        <flux:input wire:model="email" label="Email" type="email" />
                        
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="nis" label="NIS" />
                            <flux:input wire:model="nisn" label="NISN" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="pob" label="Tempat Lahir" />
                            <flux:input wire:model="dob" label="Tanggal Lahir" type="date" />
                        </div>

                        <flux:input wire:model="phone" label="No. Telepon / WA" />
                        
                        <flux:select wire:model="classroom_id" label="Kelas">
                            <option value="">Pilih Kelas</option>
                            @foreach($classrooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->academicYear->name }})</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:textarea wire:model="address" label="Alamat" resize="none" rows="3" />

                        <div class="grid grid-cols-2 gap-3">
                            <flux:input type="number" wire:model="birth_order" label="Anak Ke-" />
                            <flux:input type="number" wire:model="total_siblings" label="Dari ... Bersaudara" />
                        </div>

                        <flux:input wire:model="previous_school" label="Asal Sekolah" />
                        
                        <flux:select wire:model="status" label="Status Siswa">
                            <option value="baru">Baru</option>
                            <option value="mutasi">Mutasi / Pindahan</option>
                            <option value="naik_kelas">Naik Kelas</option>
                            <option value="lulus">Lulus</option>
                            <option value="keluar">Keluar</option>
                        </flux:select>
                    </div>

                    <div class="space-y-4">
                        <flux:heading size="md" class="border-b pb-1">Data Orang Tua / Wali</flux:heading>
                        
                        <flux:input wire:model="father_name" label="Nama Ayah" />
                        <flux:input wire:model="mother_name" label="Nama Ibu" />
                        
                        <div class="pt-4 space-y-4">
                            <flux:heading size="sm">Kontak Wali (Jika Ada)</flux:heading>
                            <flux:input wire:model="guardian_name" label="Nama Wali" />
                            <flux:input wire:model="guardian_phone" label="No. Telp Wali" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan</span>
                    <span wire:loading>Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="periodic-modal" class="max-w-md" x-on:periodic-saved.window="$flux.modal('periodic-modal').close()">
        <form wire:submit.prevent="savePeriodic({{ $editing?->latestProfile?->profileable_id ?? 0 }})" class="space-y-6">
            <div>
                <flux:heading size="lg">Data Periodik Siswa</flux:heading>
                <flux:subheading>Input data berat badan, tinggi, dan lingkar kepala.</flux:subheading>
                
                @if($hasExistingPeriodicData)
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            <div>
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Data sudah ada</span>
                                <span class="text-xs text-blue-600 dark:text-blue-400 block">Terakhir diupdate {{ $periodicDataLastUpdated }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-200">Belum ada data untuk semester ini</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <flux:select wire:model.live="semester" label="Semester">
                    <option value="1">Ganjil (1)</option>
                    <option value="2">Genap (2)</option>
                </flux:select>

                <flux:input type="number" step="0.5" wire:model="weight" label="Berat Badan (kg)" suffix="kg" />
                <flux:input type="number" step="1" wire:model="height" label="Tinggi Badan (cm)" suffix="cm" />
                <flux:input type="number" step="0.1" wire:model="head_circumference" label="Lingkar Kepala (cm)" suffix="cm" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="detail-modal" class="max-w-4xl">
        @if($viewing)
            @php $viewProfile = $viewing->latestProfile?->profileable; @endphp
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Detail Siswa</flux:heading>
                        <flux:subheading>Informasi lengkap data siswa</flux:subheading>
                    </div>
                </div>

                <div class="flex items-start gap-6 pb-6 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-shrink-0">
                        @if($viewProfile?->photo)
                            <img src="{{ asset('storage/' . $viewProfile->photo) }}" class="w-32 h-32 rounded-lg object-cover border-2 border-zinc-200 dark:border-zinc-700" alt="{{ $viewing->name }}" />
                        @else
                            <div class="w-32 h-32 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                <flux:icon icon="user" class="w-16 h-16 text-zinc-400" />
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 space-y-2">
                        <div>
                            <flux:heading size="xl">{{ $viewing->name }}</flux:heading>
                            <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $viewing->email }}</flux:text>
                        </div>
                        
                        <div class="flex gap-2 items-center">
                            @if($viewProfile?->classroom)
                                <flux:badge variant="primary">{{ $viewProfile->classroom->name }}</flux:badge>
                            @else
                                <flux:badge variant="danger">Belum ada kelas</flux:badge>
                            @endif
                            
                            <flux:badge variant="neutral">
                                {{ ucfirst(str_replace('_', ' ', $viewProfile?->status ?? 'baru')) }}
                            </flux:badge>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <flux:heading size="md" class="border-b pb-2">Identitas Siswa</flux:heading>
                        
                        <div class="space-y-3">
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">NIS</flux:text>
                                <flux:text>{{ $viewProfile?->nis ?? '-' }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">NISN</flux:text>
                                <flux:text>{{ $viewProfile?->nisn ?? '-' }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Tempat, Tanggal Lahir</flux:text>
                                <flux:text>
                                    {{ $viewProfile?->pob ?? '-' }}{{ $viewProfile?->dob ? ', ' . $viewProfile->dob->format('d F Y') : '' }}
                                </flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. Telepon</flux:text>
                                <flux:text>{{ $viewProfile?->phone ?? '-' }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Alamat</flux:text>
                                <flux:text>{{ $viewProfile?->address ?? '-' }}</flux:text>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Anak Ke-</flux:text>
                                    <flux:text>{{ $viewProfile?->birth_order ?? '-' }}</flux:text>
                                </div>
                                <div>
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Dari ... Bersaudara</flux:text>
                                    <flux:text>{{ $viewProfile?->total_siblings ?? '-' }}</flux:text>
                                </div>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Asal Sekolah</flux:text>
                                <flux:text>{{ $viewProfile?->previous_school ?? '-' }}</flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <flux:heading size="md" class="border-b pb-2">Data Orang Tua / Wali</flux:heading>
                        
                        <div class="space-y-3">
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Ayah</flux:text>
                                <flux:text>{{ $viewProfile?->father_name ?? '-' }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Ibu</flux:text>
                                <flux:text>{{ $viewProfile?->mother_name ?? '-' }}</flux:text>
                            </div>
                            
                            <div class="pt-4 space-y-3">
                                <flux:heading size="sm">Kontak Wali</flux:heading>
                                
                                <div>
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Wali</flux:text>
                                    <flux:text>{{ $viewProfile?->guardian_name ?? '-' }}</flux:text>
                                </div>
                                
                                <div>
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. Telp Wali</flux:text>
                                    <flux:text>{{ $viewProfile?->guardian_phone ?? '-' }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $periodicRecords = $viewProfile?->periodicRecords()
                        ->with('academicYear')
                        ->orderBy('academic_year_id', 'desc')
                        ->orderBy('semester', 'desc')
                        ->limit(3)
                        ->get();
                @endphp

                @if($periodicRecords && $periodicRecords->count() > 0)
                    <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="md" class="mb-4">Data Periodik Terbaru</flux:heading>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($periodicRecords as $record)
                                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">
                                        {{ $record->academicYear->name }} - Semester {{ $record->semester }}
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Berat:</span>
                                            <span class="font-medium">{{ $record->weight }} kg</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Tinggi:</span>
                                            <span class="font-medium">{{ $record->height }} cm</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Ling. Kepala:</span>
                                            <span class="font-medium">{{ $record->head_circumference }} cm</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="ghost">Tutup</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" icon="pencil-square" wire:click="edit({{ $viewing->id }})" x-on:click="$flux.modal('detail-modal').close(); $flux.modal('student-modal').show()">
                        Edit Data
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
