@extends('legal.layout')

@section('title', __('legal.privacy.heading'))

@section('content')
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="legal-badge">
            <i class="bi bi-shield-check"></i>
            {{ __('legal.app_name') }}
        </span>
    </div>

    <h1 class="legal-title">
        {{ __('legal.privacy.heading') }}
    </h1>

    <div class="legal-subtitle">
        {{ __('legal.privacy.subheading') }}
    </div>

    <div class="legal-meta">
        <span><i class="bi bi-building me-1"></i> TVRI Stasiun Aceh</span>
        <span>•</span>
        <span><i class="bi bi-clock-history me-1"></i> {{ __('legal.privacy.last_updated') }}</span>
        <span>•</span>
        <span><i class="bi bi-lock me-1"></i> Privacy & Data Protection</span>
    </div>

    <div class="legal-intro">
        {{ __('legal.privacy.intro') }}
    </div>

    {{-- Section 1: Information We Collect --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-card-checklist text-primary"></i>
            {{ __('legal.privacy.sections.information.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.information.content') }}
        </div>
    </section>

    {{-- Section 2: Platform Integrations --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-diagram-3 text-primary"></i>
            {{ __('legal.privacy.sections.integrations.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.integrations.content') }}
        </div>
    </section>

    {{-- Section 3: OAuth and Access Tokens --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-key text-primary"></i>
            {{ __('legal.privacy.sections.oauth_tokens.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.oauth_tokens.content') }}
        </div>
    </section>

    {{-- Section 4: How We Use Data --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-pie-chart text-primary"></i>
            {{ __('legal.privacy.sections.data_use.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.data_use.content') }}
        </div>
    </section>

    {{-- Section 5: Data Storage and Security --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-shield-shaded text-primary"></i>
            {{ __('legal.privacy.sections.storage_security.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.storage_security.content') }}
        </div>
    </section>

    {{-- Section 6: Data Sharing --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-slash-circle text-primary"></i>
            {{ __('legal.privacy.sections.sharing.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.sharing.content') }}
        </div>
    </section>

    {{-- Section 7: Data Retention --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-archive text-primary"></i>
            {{ __('legal.privacy.sections.retention.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.retention.content') }}
        </div>
    </section>

    {{-- Section 8: User Rights / Data Requests --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-person-gear text-primary"></i>
            {{ __('legal.privacy.sections.user_rights.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.user_rights.content') }}
        </div>
    </section>

    {{-- Section 9: Third-Party Privacy Policies --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-box-arrow-up-right text-primary"></i>
            {{ __('legal.privacy.sections.third_party_policies.title') }}
        </h2>
        <div class="section-body">
            <p>{{ __('legal.privacy.sections.third_party_policies.content') }}</p>
            <ul class="section-links">
                <li>
                    <strong>YouTube / Google:</strong>
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">
                        https://policies.google.com/privacy <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </li>
                <li>
                    <strong>Meta (Facebook & Instagram):</strong>
                    <a href="https://www.facebook.com/privacy/policy" target="_blank" rel="noopener noreferrer">
                        https://www.facebook.com/privacy/policy <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </li>
                <li>
                    <strong>TikTok:</strong>
                    <a href="https://www.tiktok.com/legal/privacy-policy" target="_blank" rel="noopener noreferrer">
                        https://www.tiktok.com/legal/privacy-policy <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </li>
            </ul>
        </div>
    </section>

    {{-- Section 10: Changes to Privacy Policy --}}
    <section class="legal-section">
        <h2 class="section-title">
            <i class="bi bi-arrow-clockwise text-primary"></i>
            {{ __('legal.privacy.sections.changes.title') }}
        </h2>
        <div class="section-body">
            {{ __('legal.privacy.sections.changes.content') }}
        </div>
    </section>

    {{-- Section 11: Contact --}}
    <section class="legal-section mb-0">
        <h2 class="section-title">
            <i class="bi bi-envelope text-primary"></i>
            {{ __('legal.privacy.sections.contact.title') }}
        </h2>
        <div class="section-body">
            <p>{{ __('legal.privacy.sections.contact.content') }}</p>
            <div class="p-3 bg-light rounded border border-light-subtle">
                <strong>{{ __('legal.privacy.sections.contact.administrator') }}</strong>
            </div>
        </div>
    </section>
@endsection
