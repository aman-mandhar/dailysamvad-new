<?php

namespace App\Enums;

enum AdvertisementPosition: string
{
    case HeaderTop = 'HEADER_TOP';
    case HomeSidebarTop = 'HOME_SIDEBAR_TOP';
    case HomeSidebarMiddle = 'HOME_SIDEBAR_MIDDLE';
    case HomeSidebarBottom = 'HOME_SIDEBAR_BOTTOM';
    case HomeBetweenSections = 'HOME_BETWEEN_SECTIONS';
    case ArticleTop = 'ARTICLE_TOP';
    case ArticleAfterFeaturedImage = 'ARTICLE_AFTER_FEATURED_IMAGE';
    case ArticleAfterParagraph1 = 'ARTICLE_AFTER_PARAGRAPH_1';
    case ArticleAfterParagraph2 = 'ARTICLE_AFTER_PARAGRAPH_2';
    case ArticleAfterParagraph3 = 'ARTICLE_AFTER_PARAGRAPH_3';
    case ArticleAfterParagraph4 = 'ARTICLE_AFTER_PARAGRAPH_4';
    case ArticleAfterParagraph5 = 'ARTICLE_AFTER_PARAGRAPH_5';
    case ArticleBottom = 'ARTICLE_BOTTOM';
    case ArticleSidebar = 'ARTICLE_SIDEBAR';
    case ArticleSidebarTop = 'ARTICLE_SIDEBAR_TOP';
    case ArticleSidebarBottom = 'ARTICLE_SIDEBAR_BOTTOM';
    case CategoryTop = 'CATEGORY_TOP';
    case CategorySidebar = 'CATEGORY_SIDEBAR';
    case TagTop = 'TAG_TOP';
    case SearchTop = 'SEARCH_TOP';
    case SearchInline = 'SEARCH_INLINE';
    case ArchiveTop = 'ARCHIVE_TOP';
    case ArchiveInline = 'ARCHIVE_INLINE';
    case AuthorTop = 'AUTHOR_TOP';
    case FooterTop = 'FOOTER_TOP';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => str($case->value)->replace('_', ' ')->title()->toString()])->all();
    }

    /** @return list<self> */
    public static function paragraphPositions(): array
    {
        return [self::ArticleAfterParagraph1, self::ArticleAfterParagraph2, self::ArticleAfterParagraph3, self::ArticleAfterParagraph4, self::ArticleAfterParagraph5];
    }
}
