<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Profile;
use App\Models\TeacherProfile;
use App\Models\StaffProfile;
use App\Models\Level;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    public string $search = '';
    
    // User fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'guru'; // guru, staf
    
    // Profile fields
    public string $nip = '';
    public string $phone = '';
    public string $address = '';
    
    // Teacher specific
    public string $education_level = '';
    
    // Staff specific
    public string $position = ''; // Kepala Sekolah, Kepala PKBM, Admin
    public ?int $level_id = null;
    public string $department = '';

    public ?User $editingUser = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                Rule::unique('users', 'email')->ignore($this->editingUser?->id)
            ],
            'password' => $this->editingUser ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:guru,staf',
            'nip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'education_level' => 'required_if:role,guru|nullable|string',
            'position' => 'required_if:role,staf|nullable|string',
            'level_id' => 'nullable|exists:levels,id',
            'department' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            if ($this->editingUser) {
                // Update User
                $this->editingUser->update([
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                ]);

                if ($this->password) {
                    $this->editingUser->update(['password' => Hash::make($this->password)]);
                }

                $profile = $this->editingUser->profile;
                $profileable = $profile->profileable;

                // Handle Role Change (Complex case)
                if (($this->role === 'guru' && !($profileable instanceof TeacherProfile)) ||
                    ($this->role === 'staf' && !($profileable instanceof StaffProfile))) {
                    
                    // Delete old profileable
                    $profileable->delete();

                    // Create new profileable
                    if ($this->role === 'guru') {
                        $newProfileable = TeacherProfile::create([
                            'nip' => $this->nip,
                            'phone' => $this->phone,
                            'address' => $this->address,
                            'education_level' => $this->education_level,
                        ]);
                    } else {
                        $newProfileable = StaffProfile::create([
                            'nip' => $this->nip,
                            'phone' => $this->phone,
                            'address' => $this->address,
                            'position' => $this->position,
                            'level_id' => $this->level_id,
                            'department' => $this->department,
                        ]);
                    }

                    $profile->update([
                        'profileable_id' => $newProfileable->id,
                        'profileable_type' => get_class($newProfileable),
                    ]);
                } else {
                    // Update existing
                    if ($this->role === 'guru') {
                        $profileable->update([
                            'nip' => $this->nip,
                            'phone' => $this->phone,
                            'address' => $this->address,
                            'education_level' => $this->education_level,
                        ]);
                    } else {
                        $profileable->update([
                            'nip' => $this->nip,
                            'phone' => $this->phone,
                            'address' => $this->address,
                            'position' => $this->position,
                            'level_id' => $this->level_id,
                            'department' => $this->department,
                        ]);
                    }
                }
                \Flux::toast('Data PTK berhasil diperbarui.');
            } else {
                // Create User
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'role' => $this->role,
                ]);

                // Create Profileable
                if ($this->role === 'guru') {
                    $profileable = TeacherProfile::create([
                        'nip' => $this->nip,
                        'phone' => $this->phone,
                        'address' => $this->address,
                        'education_level' => $this->education_level,
                    ]);
                } else {
                    $profileable = StaffProfile::create([
                        'nip' => $this->nip,
                        'phone' => $this->phone,
                        'address' => $this->address,
                        'position' => $this->position,
                        'level_id' => $this->level_id,
                        'department' => $this->department,
                    ]);
                }

                // Create Profile
                Profile::create([
                    'user_id' => $user->id,
                    'profileable_id' => $profileable->id,
                    'profileable_type' => get_class($profileable),
                ]);
                
                \Flux::toast('Data PTK berhasil ditambahkan.');
            }
        });

        $this->reset(['name', 'email', 'password', 'role', 'nip', 'phone', 'address', 'education_level', 'position', 'level_id', 'department', 'editingUser']);
        $this->dispatch('close-modal');
    }

    public function edit(User $user): void
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';

        $profile = $user->profile;
        $profileable = $profile?->profileable;

        if ($profileable) {
            $this->nip = $profileable->nip ?? '';
            $this->phone = $profileable->phone ?? '';
            $this->address = $profileable->address ?? '';

            if ($profileable instanceof TeacherProfile) {
                $this->education_level = $profileable->education_level ?? '';
            } elseif ($profileable instanceof StaffProfile) {
                $this->position = $profileable->position ?? '';
                $this->level_id = $profileable->level_id;
                $this->department = $profileable->department ?? '';
            }
        }
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $profile = $user->profile;
            if ($profile) {
                $profile->profileable?->delete();
                $profile->delete();
            }
            $user->delete();
        });
        \Flux::toast('Data PTK berhasil dihapus.');
    }

    public function with(): array
    {
        $users = User::with(['profile.profileable'])
            ->whereIn('role', ['guru', 'staf'])
            ->when($this->search, function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(10);

        return [
            'users' => $users,
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Manajemen PTK</flux:heading>
            <flux:subheading>Pendidik dan Tenaga Kependidikan (Guru & Staf).</flux:subheading>
        </div>
        <flux:modal.trigger name="add-ptk">
            <flux:button variant="primary" icon="plus">Tambah PTK</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..." icon="magnifying-glass" />
        </div>
    </div>

    <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama / Email</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Role / Jabatan</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kontak</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($users as $user)
                    @php
                        $profileable = $user->profile?->profileable;
                        $position = $user->role === 'guru' ? 'Guru / Pendidik' : ($profileable?->position ?? 'Tenaga Kependidikan');
                        if($user->role === 'staf' && $profileable?->level) {
                            $position .= ' (' . $profileable->level->name . ')';
                        }
                    @endphp
                    <tr wire:key="{{ $user->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :variant="$user->role === 'guru' ? 'success' : 'primary'">
                                {{ strtoupper($user->role) }}
                            </flux:badge>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $position }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-zinc-900 dark:text-white">{{ $profileable->phone ?? '-' }}</div>
                            <div class="text-xs text-zinc-500 line-clamp-1">{{ $profileable->address ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:modal.trigger name="add-ptk">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="edit({{ $user->id }})" />
                                </flux:modal.trigger>
                                <flux:button variant="ghost" icon="trash" size="sm" wire:click="delete({{ $user->id }})" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t">
            {{ $users->links() }}
        </div>
    </div>

    <flux:modal name="add-ptk" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUser ? 'Edit PTK' : 'Tambah PTK' }}</flux:heading>
                <flux:subheading>Isi informasi akun dan data profil PTK.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="name" label="Nama Lengkap" placeholder="Nama tanpa gelar" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="email@contoh.com" />
                
                <div class="md:col-span-2">
                    <flux:input wire:model="password" label="Password {{ $editingUser ? '(Kosongkan jika tidak diubah)' : '' }}" type="password" />
                </div>

                <flux:select wire:model.live="role" label="Status PTK">
                    <option value="guru">Pendidik (Guru)</option>
                    <option value="staf">Tenaga Kependidikan (Staf)</option>
                </flux:select>

                <flux:input wire:model="nip" label="NIP / No. Pegawai" placeholder="Optional" />
            </div>

            <flux:separator variant="subtle" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="phone" label="No. Telepon" icon="phone" />
                
                @if($role === 'guru')
                    <flux:input wire:model="education_level" label="Pendidikan Terakhir" placeholder="Contoh: S1 Pendidikan Biologi" />
                @else
                    <flux:select wire:model.live="position" label="Jabatan">
                        <option value="">Pilih Jabatan</option>
                        <option value="Kepala PKBM">Kepala PKBM</option>
                        <option value="Kepala Sekolah">Kepala Sekolah (Jenjang)</option>
                        <option value="Bendahara">Bendahara</option>
                        <option value="Administrasi">Administrasi / Operator</option>
                        <option value="Lainnya">Lainnya</option>
                    </flux:select>

                    @if($position === 'Kepala Sekolah')
                        <flux:select wire:model="level_id" label="Jenjang">
                            <option value="">Pilih Jenjang</option>
                            @foreach($levels as $lvl)
                                <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    <flux:input wire:model="department" label="Bagian / Departemen" placeholder="Contoh: Tata Usaha" />
                @endif
            </div>

            <div class="col-span-2">
                <flux:textarea wire:model="address" label="Alamat Lengkap" rows="2" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="save">Simpan Data</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
