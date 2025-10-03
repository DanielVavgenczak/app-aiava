<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Mary\Traits\Toast;
new class extends Component {
    use WithFileUploads, Toast;
    public $user;
    public $open = false;

    #[Validate('image|max:51200')]
    public $avatar;
    public $image = '';

    public function mount()
    {
        $this->user = Auth::guard('teacher')->user();
        $this->image = $this->user->profile?->avatar;
    }

    #[On('open-modal-update-profile')]
    public function openModal($open)
    {
        $this->open = $open;
    }

    public function uploadImage()
    {
        $this->validate();
        try {
            if ($this->avatar) {
                $directory = 'profiles';
                $filename = $this->generateAvatarName($this->avatar);
                $path = $this->avatar->storeAs($directory, $filename, 'public');
                if ($this->user->profile && $this->user->profile->avatar) {
                    // Deleta a imagem antiga
                    Storage::disk('public')->delete($this->user->profile->avatar);
                }
                $this->user->profile()->updateOrCreate(
                    [
                        'teacher_id' => $this->user->id,
                    ],
                    [
                        'avatar_path' => 'profile',
                        'avatar' => $path,
                    ],
                );
                $this->avatar = null;
              }
              $this->success('Imagem de perfil atualizada com sucesso!');
              $this->open = false;
              return $this->redirect(route('teacher.profile'), navigate: true);
        } catch (\Exception $err) {
            if (!empty($err->getMessage())) {
                $this->error($err->getMessage());
            }
        }
    }
    private function generateAvatarName($file)
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '-' . md5(time()) . '.' . $extension;
    }
}; ?>

<x-mary-modal wire:model="open" class="">
    <div>
        <h2 class="text-lg font-semibold text-gray-600">Editar perfil</h2>
    </div>
    <div class="flex justify-center">
        <form wire:submit="uploadImage">
            <div class="my-4">
                <div>
                    <x-mary-file wire:model="avatar" accept="image/png, image/jpeg, image/jpg">
                        <img src="{{ asset('storage/'.$image) }}" alt="" class="h-32" />
                    </x-mary-file>
                </div>
            </div>
            <!-- Botões -->
            <div class="flex justify-end gap-2 mt-6">
                <x-mary-button label="Cancelar" wire:click="$set('open', false)" class="btn-ghost" />
                <x-mary-button label="Salvar" type="submit" class="bg-pink-600 text-gray-50" />
            </div>
        </form>
    </div>
</x-mary-modal>
