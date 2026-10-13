{{-- As páginas de erro (404...) não passam pelo grupo "web", logo podem não ter sessão. --}}
@php
    $flash = app()->bound('request') && app('request')->hasSession() ? app('request')->session() : null;
@endphp
@if ($flash?->get('success'))
    <div class="container"><p class="aviso aviso-ok" role="status">{{ $flash->get('success') }}</p></div>
@endif
@if ($flash?->get('error'))
    <div class="container"><p class="aviso aviso-erro" role="alert">{{ $flash->get('error') }}</p></div>
@endif
