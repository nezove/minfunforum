<!-- Структурированные данные для поисковых систем -->
@if(isset($topic))
    <!-- Schema.org для темы форума -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "DiscussionForumPosting",
        "headline": "{{ \App\Helpers\SeoHelper::cleanText($topic->title) }}",
        "description": "{{ \App\Helpers\SeoHelper::truncate($topic->content, 160) }}",
        "author": {
            "@type": "Person",
            "name": "{{ $topic->user->name }}"
        },
        "datePublished": "{{ $topic->created_at->toISOString() }}",
        "dateModified": "{{ $topic->updated_at->toISOString() }}",
        "interactionStatistic": {
            "@type": "InteractionCounter",
            "interactionType": "https://schema.org/ReplyAction",
            "userInteractionCount": {{ $topic->replies_count ?? 0 }}
        },
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ config('app.name') }}",
            "url": "{{ url('/') }}"
        },
        "url": "{{ url()->current() }}"
    }
    </script>
@elseif(isset($user))
    <!-- Schema.org для профиля пользователя -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfilePage",
        "mainEntity": {
            "@type": "Person",
            "name": "{{ $user->name }}",
            @if($user->bio)
            "description": "{{ \App\Helpers\SeoHelper::truncate($user->bio, 160) }}",
            @endif
            @if($user->website)
            "url": "{{ $user->website }}",
            @endif
            @if($user->location)
            "homeLocation": "{{ $user->location }}",
            @endif
            "memberOf": {
                "@type": "Organization",
                "name": "{{ config('app.name') }}"
            }
        },
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ config('app.name') }}",
            "url": "{{ url('/') }}"
        },
        "url": "{{ url()->current() }}"
    }
    </script>
@elseif(isset($category))
    <!-- Schema.org для категории -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "{{ $category->name }}",
        @if($category->description)
        "description": "{{ \App\Helpers\SeoHelper::truncate($category->description, 160) }}",
        @endif
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ config('app.name') }}",
            "url": "{{ url('/') }}"
        },
        "url": "{{ url()->current() }}"
    }
    </script>
@else
    <!-- Schema.org для главной страницы -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ config('app.name') }}",
        "description": "{{ $seoDescription ?? 'Форум разработчиков и IT-специалистов' }}",
        "url": "{{ url('/') }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ url('/') }}/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
@endif