<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Profile;
use App\Models\Classroom;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination, WithFileUploads;

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

    public ?User $editing = null;
    public ?string $existingPhoto = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . ($this->editing->id ?? 'NULL')],
            'nis' => ['nullable', 'string', 'unique:student_profiles,nis,' . ($this->editing?->latestProfile?->profileable_id ?? 'NULL')],
            'nisn' => ['nullable', 'string', 'unique:student_profiles,nisn,' . ($this->editing?->latestProfile?->profileable_id ?? 'NULL')],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'dob' => ['nullable', 'date'],
            'pob' => ['nullable', 'string'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'photo' => ['nullable', 'image', 'max:1024'], // 1MB Max
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
        ];
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
                'nis' => $this->nis,
                'nisn' => $this->nisn,
                'phone' => $this->phone,
                'address' => $this->address,
                'dob' => $this->dob ?: null,
                'pob' => $this->pob,
                'photo' => $photoPath,
                'father_name' => $this->father_name,
                'mother_name' => $this->mother_name,
                'guardian_name' => $this->guardian_name,
                'guardian_phone' => $this->guardian_phone,
                'classroom_id' => $this->classroom_id,
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

        $this->reset(['name', 'email', 'nis', 'nisn', 'phone', 'address', 'dob', 'pob', 'classroom_id', 'photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'editing', 'existingPhoto']);
        $this->dispatch('close-modal', 'student-modal');
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

        $this->dispatch('open-modal', 'student-modal');
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
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhereHas('latestProfile', fn($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn($sq) => $sq->where('nis', 'like', "%{$this->search}%")->orWhere('nisn', 'like', "%{$this->search}%"))))
                ->latest()
                ->paginate(15),
            'classrooms' => Classroom::with('academicYear')->get(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Manajemen Siswa</flux:heading>
            <flux:subheading>Kelola data murid, profil, dan penempatan kelas.</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari siswa..." icon="magnifying-glass" class="w-64" />
            
            <flux:modal.trigger name="student-modal">
                <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Siswa</flux:button>
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
                            <div class="flex items-center gap-3">
                                <flux:avatar 
                                    :src="$profile?->photo ? asset('storage/' . $profile->photo) : null" 
                                    :name="$student->name" 
                                    :initials="$student->initials()" 
                                    size="sm" 
                                />
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $student->email }}</span>
                                </div>
                            </div>
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
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $student->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus siswa ini?" wire:click="delete({{ $student->id }})" />
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

    <flux:modal name="student-modal" class="max-w-3xl">
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
                        
                        <flux:input wire:model="name" label="Nama Lengkap" required />
                        <flux:input wire:model="email" label="Email" type="email" required />
                        
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
</div>
