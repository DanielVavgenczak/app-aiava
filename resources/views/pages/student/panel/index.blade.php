<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.student-panel')] class extends Component {
    //
}; ?>

<div>
    <div class="p-5 bg-white">
        Bem vindo {{ Auth::guard('student')->user()->full_name }}
    </div>
</div>
