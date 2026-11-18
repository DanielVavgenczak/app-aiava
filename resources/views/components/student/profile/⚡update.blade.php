<?php

use Livewire\Component;
use Livewire\Attributes\{Layout,On, Validate};
use Livewire\WithFileUploads;
use App\Models\Teacher\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mary\Traits\Toast;

new #[Layout('layouts::student')] class extends Component {
    use WithFileUploads, Toast;
    public $open = false;

    #[Validate('image|max:2048')]
    public $avatar;
    public $image = '';

    private function currentStudent(): ?Student
    {
        return Auth::guard('student')->user();
    }

    public function mount()
    {
        $this->image = $this->currentStudent()?->avatar_url ?? '';
    }

    #[On('open-modal-update-profile')]
    public function openModal($open)
    {
        $this->open = $open;
    }

    public function uploadImage()
    {
        $this->validate();

        $student = $this->currentStudent();
        if (! $student) {
            $this->redirect(route('student.login'));

            return;
        }

        try {
            if ($this->avatar) {
                $directory = 'profiles';
                $filename = $this->generateAvatarName($this->avatar);
                $path = $this->avatar->storeAs($directory, $filename, 'public');
                if ($student->profile && $student->profile->avatar) {
                    Storage::disk('public')->delete($student->profile->avatar);
                }
                $student->profile()->updateOrCreate(
                    [
                        'student_id' => $student->id,
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

            return $this->redirect(route('student.profile'), navigate: true);
        } catch (\Exception $err) {
            Log::error('Falha ao atualizar o avatar do aluno', [
                'student_id' => $student->id,
                'exception' => $err,
            ]);
            $this->error('Não foi possível atualizar a imagem de perfil. Tente novamente.');
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
                    <x-mary-file wire:model="avatar" accept="image/png, image/jpeg, image/jpg"
                        crop-after-change
                        crop-mime-type="image/jpeg"
                        :crop-config="['aspectRatio' => 1, 'autoCropArea' => 1, 'viewMode' => 1]">
                        <img src="{{ $image }}" alt="Prévia da foto de perfil" class="h-32 object-cover rounded-full" />
                    </x-mary-file>
                </div>
            </div>
            <!-- Botões -->
            <div class="flex justify-end gap-2 mt-6">
                <x-mary-button label="Cancelar" wire:click="$set('open', false)" class="btn-ghost" />
                <x-mary-button label="Salvar" type="submit" class="bg-blue-400 text-gray-50" />
            </div>
        </form>
    </div>
</x-mary-modal>
