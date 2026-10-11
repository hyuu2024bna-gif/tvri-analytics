@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#003882] focus:ring-[#003882] rounded-lg shadow-sm']) }}>
