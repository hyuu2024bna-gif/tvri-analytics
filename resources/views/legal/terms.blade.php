@extends('legal.layout')

@section('title', __('legal.terms.heading'))

@section('content')
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="legal-badge">
            <i class="bi bi-file-earmark-text"></i>
            {{ __('legal.app_name') }}
        </span>
    </div>

    <h1 class="legal-title">
        {{ __('legal.terms.heading') }}
    </h1>

    <div class="legal-subtitle">
        {{ __('legal.terms.subheading') }}
    </div>

    <div class="legal-meta">
        <span><i class="bi bi-building me-1"></i> TVRI Stasiun Aceh</span>
        <span>•</span>
        <span><i class="bi bi-clock-history me-1"></i> {{ __('legal.terms.last_updated') }}</span>
        <span>•</span>
        <span><i class="bi bi-shield-check me-1"></i> Official Document</span>
    </div>

    <div class="legal-intro">
        {{ __('legal.terms.intro') }}
    </div>

    {{-- Section 1: Acceptance of Terms --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-check2-circle text-primary"></i>
            {{ __('legal.terms.sections.acceptance.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.acceptance.content') }}
        </div>
    </section>

    {{-- Section 2: Description of Service --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-grid-1x2 text-primary"></i>
            {{ __('legal.terms.sections.description.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.description.content') }}
        </div>
    </section>

    {{-- Section 3: Authorized Use --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-person-check text-primary"></i>
            {{ __('legal.terms.sections.authorized_use.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.authorized_use.content') }}
        </div>
    </section>

    {{-- Section 4: Third-Party Platforms --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-share text-primary"></i>
            {{ __('legal.terms.sections.third_party.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.third_party.content') }}
        </div>
    </section>

    {{-- Section 5: Data and Analytics --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-graph-up-arrow text-primary"></i>
            {{ __('legal.terms.sections.data_analytics.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.data_analytics.content') }}
        </div>
    </section>

    {{-- Section 6: Availability --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-server text-primary"></i>
            {{ __('legal.terms.sections.availability.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.availability.content') }}
        </div>
    </section>

    {{-- Section 7: Security --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-shield-lock text-primary"></i>
            {{ __('legal.terms.sections.security.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.security.content') }}
        </div>
    </section>

    {{-- Section 8: Intellectual Property --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-award text-primary"></i>
            {{ __('legal.terms.sections.intellectual_property.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.intellectual_property.content') }}
        </div>
    </section>

    {{-- Section 9: Limitation of Liability --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-exclamation-triangle text-primary"></i>
            {{ __('legal.terms.sections.liability.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.liability.content') }}
        </div>
    </section>

    {{-- Section 10: Changes to Terms --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-arrow-repeat text-primary"></i>
            {{ __('legal.terms.sections.changes.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.terms.sections.changes.content') }}
        </div>
    </section>

    {{-- Section 11: Contact --}}
    <section class="legal-section mb-0">
        <h2 class="section-title">
            <i class="bi bi-envelope text-primary"></i>
            {{ __('legal.terms.sections.contact.title') }}
        </h2>
        <div class="section-body">
            <p>{{ __('legal.terms.sections.contact.content') }}</p>
            <div class="p-3 bg-light rounded border border-light-subtle">
                <strong>{{ __('legal.terms.sections.contact.administrator') }}</strong>
            </div>
        </div>
    </section>
@endsection
