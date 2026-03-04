<picture>
    <source srcset="{{ asset('image/logo.webp') }}" type="image/webp">
    <img src="{{ asset('image/logo.png') }}" 
         width="106" height="96"
         {{ $attributes }}>
</picture>