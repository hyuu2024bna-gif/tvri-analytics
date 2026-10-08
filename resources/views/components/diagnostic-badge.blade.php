@props(['value'])

@php
    $val = strtoupper(trim($value ?? ''));
    $color = match (true) {
        in_array($val, ['OK', 'SET', 'CONFIGURED', 'YES', 'GRANTED', 'ACCESSIBLE', 'VALID (NOT EXPIRED)', 'PRESENT']) => 'bg-green-900/50 text-green-400 border-green-700',
        in_array($val, ['NO', 'NOT SET', 'NOT FOUND', 'EMPTY', 'EXPIRED', 'NO ACCESS', 'NO ACCOUNT']) => 'bg-red-900/50 text-red-400 border-red-700',
        str_starts_with($val, 'ERROR') => 'bg-red-900/50 text-red-400 border-red-700',
        in_array($val, ['UNKNOWN', 'CANNOT CHECK', 'CANNOT VERIFY', 'CANNOT DETERMINE FROM APPLICATION CREDENTIALS']) => 'bg-yellow-900/50 text-yellow-400 border-yellow-700',
        str_starts_with($val, 'NEVER') => 'bg-blue-900/50 text-blue-400 border-blue-700',
        default => 'bg-gray-700/50 text-gray-300 border-gray-600',
    };
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $color }}">
    {{ $value }}
</span>
