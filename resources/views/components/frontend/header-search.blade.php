<div id="ds-header-search" class="ds-header-search" data-search-panel hidden>
    <div class="ds-container ds-header-search__inner">
        <form class="ds-header-search__form" action="{{ route('search') }}" method="GET" role="search">
            <label class="ds-visually-hidden" for="ds-header-search-input">Search news</label>
            <input id="ds-header-search-input" data-search-input type="search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="समाचार खोजें">
            <button class="ds-header-search__submit" type="submit">Search</button>
            <button class="ds-header-search__close" type="button" data-search-close>
                <span class="ds-visually-hidden">Close search</span>
                <svg class="ds-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
        </form>
    </div>
</div>
