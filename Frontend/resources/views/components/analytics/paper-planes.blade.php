<div data-ui="paper-planes" aria-hidden="true" {{ $attributes->class('landing-plane-map') }}>
    <svg class="landing-plane-route" aria-hidden="true" viewBox="0 0 520 250" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 214C112 216 98 62 212 83C325 103 294 220 495 39" stroke-dasharray="7 7" />
        <circle cx="18" cy="214" r="4" />
        <circle cx="495" cy="39" r="4" />
    </svg>
    @foreach (['blue', 'coral', 'mint', 'pink'] as $tone)
        <svg class="landing-plane landing-plane-{{ $tone }} landing-plane-{{ $loop->iteration }}" aria-hidden="true" viewBox="0 0 42 36" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">
            <path class="landing-plane-main" d="M3 17.5 38 3l-12 30-7.5-12L3 17.5Z" />
            <path class="landing-plane-fold" d="m18.5 21 7.5 12 .5-18.5L38 3 18.5 21Z" />
            <path class="landing-plane-tail" d="m18.5 21-4 7" />
        </svg>
    @endforeach
</div>
