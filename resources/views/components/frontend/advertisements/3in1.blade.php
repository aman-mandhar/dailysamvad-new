@php
    $img1 = $img1 ?? asset('images/frontend/ads/img1.png');
    $img2 = $img2 ?? asset('images/frontend/ads/dr-caremax-1.jpg');
    $img3 = $img3 ?? asset('images/frontend/ads/ih401.jpg');
@endphp

<!-- 3in1 Advertisement 3 equal col in one row -->

<div class="ds-3in1-advertisement ds-container">
    <div class="ds-3in1-advertisement__grid">
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url1 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img1 }}" alt="Advertisement 1" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url2 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img2 }}" alt="Advertisement 2" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url3 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img3 }}" alt="Advertisement 3" class="ds-3in1-advertisement__image">
            </a>
        </div>
    </div>
</div>
