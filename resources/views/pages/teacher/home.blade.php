<div class="">
    <div class="py-4"></div>
    <div class="p-5">
        <div class="w-full mb-6">
            <h1 class="text-2xl font-bold text-gray-800" id="teacher-welcome-{{ Auth::guard('teacher')->user()->id }}">
                Olá, {{ Auth::guard('teacher')->user()->first_name }}! 👋
            </h1>
            <p class="text-gray-500 mt-1">Bem-vindo ao seu painel de controle</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="teacher-stats-{{ Auth::guard('teacher')->user()->id }}">
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500" id="teacher-stat-disciplines-{{ Auth::guard('teacher')->user()->id }}">
                <p class="text-sm text-gray-500">Disciplinas</p>
                <p class="text-2xl font-bold text-gray-800">{{ Auth::guard('teacher')->user()->disciplines->count() ?? 0 }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500" id="teacher-stat-classes-{{ Auth::guard('teacher')->user()->id }}">
                <p class="text-sm text-gray-500">Turmas</p>
                <p class="text-2xl font-bold text-gray-800">{{ Auth::guard('teacher')->user()->disciplines->flatMap->classes->unique('id')->count() ?? 0 }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500" id="teacher-stat-students-{{ Auth::guard('teacher')->user()->id }}">
                <p class="text-sm text-gray-500">Alunos</p>
                <p class="text-2xl font-bold text-gray-800">{{ Auth::guard('teacher')->user()->disciplines->flatMap->classes->flatMap->students->unique('id')->count() ?? 0 }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-500" id="teacher-stat-topics-{{ Auth::guard('teacher')->user()->id }}">
                <p class="text-sm text-gray-500">Tópicos no Fórum</p>
                <p class="text-2xl font-bold text-gray-800">{{ Auth::guard('teacher')->user()->disciplines->flatMap->forumTopics->count() ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="teacher-quick-actions-{{ Auth::guard('teacher')->user()->id }}">
            <a href="{{ route('teacher.discipline') }}" wire:navigate 
               class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border-2 border-blue-200"
               id="btn-create-discipline-{{ Auth::guard('teacher')->user()->id }}">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-plus-circle" class="w-8 h-8 text-blue-600" />
                    <div>
                        <p class="font-semibold text-gray-700">Nova Disciplina</p>
                        <p class="text-sm text-gray-500">Crie uma nova disciplina</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('teacher.classes') }}" wire:navigate 
               class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors border-2 border-green-200"
               id="btn-create-class-{{ Auth::guard('teacher')->user()->id }}">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-users" class="w-8 h-8 text-green-600" />
                    <div>
                        <p class="font-semibold text-gray-700">Nova Turma</p>
                        <p class="text-sm text-gray-500">Crie uma nova turma</p>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- tour home -->
        <div x-data class="fixed inset-0 z-40 bg-gray-900/50" x-show="$store.tourguide.openMai">
            <!-- Centralizando a div -->
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <!--TODO: create header to all tours page --> 
                
                <div class="flex items-center justify-between">
                  <h2 class="text-[14px] font-normal text-gray-700">
                    Opções da home
                  </h2>
                  <x-mary-button icon="o-x-mark" class="btn btn-sm btn-ghost" x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
                <!-- dont have button to actions -->
                <div class="p-4 bg-gray-100">
                  <p class="text-sm tracking-wide text-gray-600">
                    Em breve
                  </p>
                </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
</div>
