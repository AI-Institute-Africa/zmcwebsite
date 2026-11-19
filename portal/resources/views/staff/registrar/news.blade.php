@extends('layouts.portal')
@section('title', 'News & Press Statements')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">News & Press Statements</h4>
            <div class="text-muted small">View published news articles and press releases</div>
        </div>
        <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light border btn-sm">
            <i class="ri-arrow-left-line"></i> Back to Dashboard
        </a>
    </div>

    <div class="zmc-card p-0 shadow-sm border-0">
        <div class="p-3 border-bottom">
            <h6 class="fw-bold m-0">
                <i class="ri-newspaper-line me-2" style="color:#2563eb"></i> Latest News
            </h6>
        </div>

        <div class="p-3">
            @forelse($news as $article)
                <div class="card mb-3 border">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold mb-0">{{ $article->title }}</h5>
                            <span class="badge bg-primary-subtle text-primary border-primary">
                                {{ $article->published_at?->format('d M Y H:i') }}
                            </span>
                        </div>
                        
                        @if($article->excerpt)
                            <p class="text-muted mb-2">{{ $article->excerpt }}</p>
                        @endif

                        <div class="mb-3">
                            <p class="mb-0">{{ Str::limit($article->content, 300) }}</p>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            @if($article->category)
                                <span class="badge bg-light text-dark border small">
                                    <i class="ri-price-tag-3-line me-1"></i>{{ ucfirst($article->category) }}
                                </span>
                            @endif
                            @if($article->author)
                                <span class="badge bg-light text-dark border small">
                                    <i class="ri-user-line me-1"></i>{{ $article->author }}
                                </span>
                            @endif
                            @if($article->is_featured)
                                <span class="badge bg-warning-subtle text-warning border-warning small">
                                    <i class="ri-star-line me-1"></i>Featured
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="ri-newspaper-line" style="font-size: 48px;"></i>
                    <p class="mt-2">No news articles published yet.</p>
                </div>
            @endforelse

            @if($news->hasPages())
                <div class="mt-3">
                    {{ $news->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
