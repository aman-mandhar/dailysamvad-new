@php
    $img1 = $img1 ?? asset('images/frontend/ads/img1.png');
    $img2 = $img2 ?? asset('images/frontend/ads/dr-caremax-1.jpg');
    $img3 = $img3 ?? asset('images/frontend/ads/ih401.jpg');
    $img4 = $img4 ?? asset('images/frontend/ads/img4.png');
    $img5 = $img5 ?? asset('images/frontend/ads/img5.png');
    $img6 = $img6 ?? asset('images/frontend/ads/img6.png');
    $img7 = $img7 ?? asset('images/frontend/ads/img7.png');
    $img8 = $img8 ?? asset('images/frontend/ads/img8.png');
    $img9 = $img9 ?? asset('images/frontend/ads/img9.png');
    $img10 = $img10 ?? asset('images/frontend/ads/img10.png');
    $img11 = $img11 ?? asset('images/frontend/ads/img11.png');
    $img12 = $img12 ?? asset('images/frontend/ads/img12.png');
    $img13 = $img13 ?? asset('images/frontend/ads/img13.png');
    $img14 = $img14 ?? asset('images/frontend/ads/img14.png');
    $img15 = $img15 ?? asset('images/frontend/ads/img15.png');
    
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
        <div class="ds-3in1-advertisement__grid">
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url1 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img4 }}" alt="Advertisement 1" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url2 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img5 }}" alt="Advertisement 2" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url3 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img6 }}" alt="Advertisement 3" class="ds-3in1-advertisement__image">
            </a>
        </div>
    </div>
    <div class="ds-3in1-advertisement__grid">
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url1 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img7 }}" alt="Advertisement 1" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url2 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img8 }}" alt="Advertisement 2" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url3 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img9 }}" alt="Advertisement 3" class="ds-3in1-advertisement__image">
            </a>
        </div>
    </div>
    <div class="ds-3in1-advertisement__grid">
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url1 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img10 }}" alt="Advertisement 1" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url2 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img11 }}" alt="Advertisement 2" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url3 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img12 }}" alt="Advertisement 3" class="ds-3in1-advertisement__image">
            </a>
        </div>
    </div>
    <div class="ds-3in1-advertisement__grid">
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url1 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img13 }}" alt="Advertisement 1" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url2 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img14 }}" alt="Advertisement 2" class="ds-3in1-advertisement__image">
            </a>
        </div>
        <div class="ds-3in1-advertisement__item">
            <a href="{{ $url3 ?? '#' }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $img15 }}" alt="Advertisement 3" class="ds-3in1-advertisement__image">
            </a>
        </div>
    </div>
</div>
