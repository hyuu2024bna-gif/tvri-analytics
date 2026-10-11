<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-[#003882] border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-[#00265c] focus:bg-[#00265c] active:bg-[#001f4a] focus:outline-none focus:ring-2 focus:ring-[#003882] focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm']) }}>
    {{ $slot }}
</button>
