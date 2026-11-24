<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ Str::limit($person->professional_bio ?: $person->full_name.' professional portfolio', 155) }}">
    <meta name="theme-color" content="#f7f9fc">
    <title>{{ $person->full_name }} · Professional Portfolio</title>
    <link rel="stylesheet" href="{{ asset('css/portfolio.css') }}?v={{ filemtime(public_path('css/portfolio.css')) }}-portfolio-refresh">
</head>
<body>
    <header class="portfolio-header">
        <div class="header-inner">
            <a class="portfolio-brand" href="#top" aria-label="{{ $person->full_name }} portfolio home">
                <span class="brand-monogram">{{ collect(explode(' ', $person->full_name))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('') }}</span>
                <span class="brand-copy"><strong>{{ $person->full_name }}</strong><small>{{ $person->designation ?: 'Professional Portfolio' }}</small></span>
            </a>
            <nav class="portfolio-nav" aria-label="Portfolio sections">
                <a href="#about">About</a>
                @if ($person->employmentHistory->isNotEmpty())<a href="#experience">Experience</a>@endif
                @if ($person->educations->isNotEmpty())<a href="#education">Education</a>@endif
                @if ($person->skills->isNotEmpty())<a href="#skills">Skills</a>@endif
                @if ($person->portfolioProjects->isNotEmpty())<a href="#projects">Projects</a>@endif
                @if ($person->certifications->isNotEmpty())<a href="#certifications">Certifications</a>@endif
                <a href="#contact">Contact</a>
            </nav>
            <button class="header-contact" type="button" data-vcard-download>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM19 8v6m-3-3h6"/></svg>
                Save Contact
            </button>
        </div>
    </header>

    <main id="top" class="portfolio-page">
        <section class="mobile-vcard" aria-label="{{ $person->full_name }} contact card">
            <article class="mobile-vcard-card">
                <div class="mobile-vcard-banner">
                    <button class="mobile-vcard-pill" type="button" data-open-qr><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zm4 4h3v3h-3zm3-4v1m-7 4v3"/></svg>Share QR</button>
                </div>
                <div class="mobile-vcard-content">
                    <div class="mobile-vcard-identity-visual">
                        @if ($person->profile_photo_path)
                            <img class="mobile-vcard-photo" src="{{ route('profiles.photo', ['slug' => $person->public_slug]) }}" alt="Portrait of {{ $person->full_name }}">
                        @else
                            <span class="mobile-vcard-photo mobile-vcard-avatar" aria-hidden="true">{{ collect(explode(' ', $person->full_name))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('') }}</span>
                        @endif
                        @if ($person->department)<span class="mobile-vcard-department"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 5V3h8v2m-8 5h.01M12 10h.01m4-.01h.01M8 14h.01m4 0h.01m4 0h.01M8 18h.01m4 0h.01m4 0h.01"/></svg>{{ $person->department }}</span>@endif
                    </div>
                    <h1>{{ $person->full_name }}</h1>
                    @if ($person->designation)<p class="mobile-vcard-title">{{ $person->designation }}</p>@endif
                    @if ($person->organization)<p class="mobile-vcard-company">{{ $person->organization }}</p>@endif
                    @if ($person->location)<p class="mobile-vcard-location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $person->location }}</p>@endif
                    <button class="mobile-vcard-download" type="button" data-vcard-download><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM19 8v6m-3-3h6"/></svg>Save Contact</button>
                    <p class="mobile-vcard-hint">Downloads a standard <strong>.vcf</strong> contact card to add to your phone</p>
                    <nav class="mobile-vcard-actions" aria-label="Quick contact actions">
                        @if ($person->phone)<a href="tel:{{ preg_replace('/[^+\d]/', '', $person->phone) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.8a2 2 0 0 1-.6 1.8L7.7 9.6a16 16 0 0 0 6.7 6.7l1.3-1.3a2 2 0 0 1 1.8-.6l2.8.5a2 2 0 0 1 1.7 2Z"/></svg>Call</a>@endif
                        @if ($person->email)<a href="mailto:{{ $person->email }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>Email</a>@endif
                        @if ($person->phone)<a href="https://wa.me/{{ preg_replace('/\D/', '', $person->phone) }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>WhatsApp</a>@endif
                        @if ($person->website_url)<a href="{{ $person->website_url }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg>Website</a>@endif
                    </nav>
                    @if ($person->professional_bio)<section class="mobile-vcard-section"><h2>About</h2><p class="mobile-vcard-about" data-about-preview>{{ Str::limit($person->professional_bio, 140) }}</p><p class="mobile-vcard-about" data-about-full hidden>{{ $person->professional_bio }}</p>@if (Str::length($person->professional_bio) > 140)<button class="mobile-vcard-expand" type="button" data-about-toggle aria-expanded="false">Read more <span aria-hidden="true">↓</span></button>@endif</section>@endif
                    @if ($person->organization || $person->designation || $person->department || $person->website_url)
                        <section class="mobile-vcard-section"><h2>Organization</h2><div class="mobile-vcard-organization"><div class="mobile-vcard-org-heading"><span>{{ collect(explode(' ', $person->organization ?: $person->full_name))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('') }}</span><div>@if ($person->organization)<strong>{{ $person->organization }}</strong>@endif @if ($person->designation)<small>{{ $person->designation }}</small>@endif</div></div><dl>@if ($person->department)<div><dt>Department</dt><dd>{{ $person->department }}</dd></div>@endif @if ($person->website_url)<div><dt>Website</dt><dd>{{ parse_url($person->website_url, PHP_URL_HOST) ?: $person->website_url }}</dd></div>@endif @if ($person->phone)<div><dt>Contact line</dt><dd>{{ $person->phone }}</dd></div>@endif</dl></div></section>
                    @endif
                    @if ($person->phone || $person->email || $person->website_url)
                        <section class="mobile-vcard-section"><h2>Contact Details</h2><div class="mobile-vcard-contact-list">
                            @if ($person->phone)<div class="mobile-vcard-contact-row"><span class="mobile-vcard-contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.8a2 2 0 0 1-.6 1.8L7.7 9.6a16 16 0 0 0 6.7 6.7l1.3-1.3a2 2 0 0 1 1.8-.6l2.8.5a2 2 0 0 1 1.7 2Z"/></svg></span><div><small>PHONE</small><a href="tel:{{ preg_replace('/[^+\d]/', '', $person->phone) }}">{{ $person->phone }}</a></div><button type="button" data-copy="{{ $person->phone }}" aria-label="Copy phone number"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg></button></div>@endif
                            @if ($person->email)<div class="mobile-vcard-contact-row"><span class="mobile-vcard-contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><div><small>EMAIL</small><a href="mailto:{{ $person->email }}">{{ $person->email }}</a></div><button type="button" data-copy="{{ $person->email }}" aria-label="Copy email address"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg></button></div>@endif
                            @if ($person->website_url)<div class="mobile-vcard-contact-row"><span class="mobile-vcard-contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg></span><div><small>WEBSITE</small><a href="{{ $person->website_url }}" target="_blank" rel="noopener noreferrer">{{ parse_url($person->website_url, PHP_URL_HOST) ?: $person->website_url }}</a></div><button type="button" data-copy="{{ $person->website_url }}" aria-label="Copy website address"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg></button></div>@endif
                        </div></section>
                    @endif
                    @if ($person->location)
                        @php($mapAddress = $person->location)
                        <section class="mobile-vcard-section"><h2>Office Location</h2><div class="mobile-vcard-office"><span class="mobile-vcard-contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><div><strong>{{ $person->organization ?: 'Office Location' }}</strong><p>{{ $mapAddress }}</p></div><a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($mapAddress) }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/></svg>Maps</a></div></section>
                    @endif
                    @if ($onlineLinks->isNotEmpty())
                        <section class="mobile-vcard-section"><h2>Connect Online</h2><div class="mobile-vcard-socials">
                            @foreach ($onlineLinks as $index => $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" @if ($index >= 2) hidden data-extra-social @endif>
                                    <span class="mobile-vcard-social-icon">@if ($link['icon'] === 'linkedin')in @elseif ($link['icon'] === 'whatsapp')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>@elseif ($link['icon'] === 'github')&lt;/&gt;@elseif ($link['icon'] === 'website')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg>@else↗@endif</span>
                                    <span><strong>{{ $link['label'] }}</strong><small>{{ $link['detail'] }}</small></span>
                                </a>
                            @endforeach
                        </div>@if ($onlineLinks->count() > 2)<button class="mobile-vcard-expand" type="button" data-toggle-socials aria-expanded="false" data-expanded-label="Show fewer links">Show all {{ $onlineLinks->count() }} links <span aria-hidden="true">↓</span></button>@endif</section>
                    @endif
                </div>
                <footer class="mobile-vcard-footer"><span>Verified Digital vCard</span><button type="button" data-share-profile><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4m-6.6 7 6.6 4.4"/></svg>Share Card</button></footer>
                <dialog class="mobile-qr-dialog" aria-labelledby="mobile-qr-title"><button class="mobile-qr-close" type="button" data-close-qr aria-label="Close QR code">×</button><p class="mobile-vcard-kicker">DIGITAL IDENTITY</p><h2 id="mobile-qr-title">Share this card</h2>@if ($person->currentQrCode)<img src="{{ route('admin.qr-codes.preview', [$person->currentQrCode, 'png']) }}" alt="QR code for {{ $person->full_name }}’s public profile">@else<p>A profile QR code has not been generated yet.</p>@endif<button type="button" data-share-profile class="mobile-qr-share">Share profile</button></dialog>
            </article>
        </section>
        <section class="hero-section" aria-labelledby="person-name">
            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> DIGITAL PORTFOLIO</span>
                    <h1 id="person-name">{{ $person->full_name }}</h1>
                    <div class="hero-roleline">
                        @if ($person->designation)<span class="hero-title">{{ $person->designation }}</span>@endif
                        @if ($person->organization)<span class="hero-organization">{{ $person->organization }}</span>@endif
                    </div>
                    @if ($person->professional_bio)<p class="hero-bio">{{ $person->professional_bio }}</p>@endif
                    <div class="hero-meta">
                        @if ($person->location)<span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $person->location }}</span>@endif
                        @if ($person->email)<a href="mailto:{{ $person->email }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>{{ $person->email }}</a>@endif
                    </div>
                    <div class="hero-actions">
                        @if ($person->email)<a class="button button-primary" href="mailto:{{ $person->email }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/></svg>Contact {{ Str::before($person->full_name, ' ') }}</a>@endif
                        <button class="button button-secondary" type="button" data-vcard-download><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>Save Contact (.vcf)</button>
                    </div>
                    @if ($person->linkedin_url || $person->github_url || $person->website_url || !empty($person->other_social_links))
                        <nav class="social-links" aria-label="Professional links">
                            @if ($person->linkedin_url)<a class="social-link" href="{{ $person->linkedin_url }}" target="_blank" rel="noopener noreferrer"><span class="linkedin-mark">in</span>LinkedIn</a>@endif
                            @if ($person->github_url)<a class="social-link" href="{{ $person->github_url }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 6v-3.9a3.4 3.4 0 0 0-.9-2.7c3-.3 6.1-1.5 6.1-6.7A5.2 5.2 0 0 0 18.8 5a4.8 4.8 0 0 0-.1-3S17.5 1.7 15 3.4a13.3 13.3 0 0 0-7 0C5.5 1.7 4.3 2 4.3 2a4.8 4.8 0 0 0-.1 3 5.2 5.2 0 0 0-1.4 3.7c0 5.2 3.1 6.4 6.1 6.7a3.4 3.4 0 0 0-.9 2.7V22"/></svg>GitHub</a>@endif
                            @if ($person->website_url)<a class="social-link" href="{{ $person->website_url }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg>Website</a>@endif
                            @foreach ($person->other_social_links ?? [] as $link)<a class="social-link" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>@endforeach
                        </nav>
                    @endif
                </div>
                <aside class="profile-card" aria-label="Profile photograph">
                    <div class="profile-card-backing" aria-hidden="true"></div>
                    <div class="profile-card-front">
                        @if ($person->profile_photo_path)<img class="hero-photo" src="{{ route('profiles.photo', ['slug' => $person->public_slug]) }}" alt="Portrait of {{ $person->full_name }}">@else<div class="hero-placeholder" aria-hidden="true">{{ Str::upper(Str::substr($person->full_name, 0, 1)) }}</div>@endif
                        <div class="profile-card-caption"><span class="availability-dot"></span><span>{{ $person->location ?: 'Open to professional opportunities' }}</span><small>{{ '@'.$person->public_slug }}</small></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="portfolio-section about-section" id="about" aria-labelledby="about-title">
            <div class="section-inner about-layout">
                <div class="about-aside"><div class="section-label"><span>01 <i></i> ABOUT</span><h2 id="about-title">Professional Summary</h2></div>
                    <div class="about-facts">
                        @if ($person->employmentHistory->isNotEmpty())<article class="fact-card"><small>EXPERIENCE</small><strong>{{ $person->employmentHistory->count() }} {{ Str::plural('professional role', $person->employmentHistory->count()) }}</strong></article>@endif
                        @if ($person->designation)<article class="fact-card"><small>CORE FOCUS</small><strong>{{ $person->designation }}</strong></article>@endif
                        @if ($person->location)<article class="fact-card"><small>CURRENT BASE</small><strong>{{ $person->location }}@if ($person->organization) · {{ $person->organization }}@endif</strong></article>@endif
                    </div>
                </div>
                <div class="about-copy">
                    @if ($person->professional_bio)<p>{{ $person->professional_bio }}</p>@else<p>{{ $person->full_name }}@if ($person->designation), {{ Str::lower($person->designation) }}@endif @if ($person->location)based in {{ $person->location }}@endif.</p>@endif
                    @if ($person->department)<p>Currently associated with {{ $person->department }}@if ($person->organization) at {{ $person->organization }}@endif.</p>@endif
                </div>
            </div>
        </section>

        @if ($person->employmentHistory->isNotEmpty())
            <section class="portfolio-section experience-section" id="experience" aria-labelledby="experience-title">
                <div class="section-inner"><div class="section-label"><span>02 <i></i> CAREER HISTORY</span><h2 id="experience-title">Professional Experience</h2><p class="section-intro">Track record of engineering leadership, system architecture, and production delivery.</p></div>
                    <div class="timeline">
                        @foreach ($person->employmentHistory as $job)
                            <article class="timeline-item"><div class="timeline-marker">{{ Str::upper(Str::substr($job->company_name, 0, 2)) }}</div>
                                <div class="timeline-content">
                                    <div class="experience-top"><div class="experience-heading">
                                        @if ($job->company_logo_path)<img class="company-logo" src="{{ Storage::disk('supabase')->exists($job->company_logo_path) ? Storage::disk('supabase')->url($job->company_logo_path) : Storage::disk('public')->url($job->company_logo_path) }}" alt="{{ $job->company_name }} logo" loading="lazy">@endif
                                        <div class="experience-title"><h3>{{ $job->job_title }}</h3>@if ($job->is_current)<span class="current-badge"><span class="availability-dot"></span> CURRENT ROLE</span>@endif<p class="organization-line">{{ $job->company_name }}@if ($job->employment_type)<span>•</span>{{ $job->employment_type }}@endif</p></div>
                                    </div><div class="experience-dates"><span>{{ $job->start_date?->format('M Y') ?: 'Start date not listed' }} – @if ($job->is_current)Present @else{{ $job->end_date?->format('M Y') ?: 'End date not listed' }}@endif</span>@if ($job->location)<small><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $job->location }}</small>@endif</div></div>
                                    @if ($job->description)<p class="timeline-description">{{ $job->description }}</p>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($person->educations->isNotEmpty())
            <section class="portfolio-section education-section" id="education" aria-labelledby="education-title"><div class="section-inner"><div class="section-label"><span>03 <i></i> ACADEMIC BACKGROUND</span><h2 id="education-title">Education</h2></div>
                <div class="education-grid">@foreach ($person->educations as $education)<article class="education-card"><div class="education-card-top"><span class="card-icon">{{ Str::upper(Str::substr($education->institution_name, 0, 2)) }}</span>@if ($education->graduation_year)<span class="year-badge">{{ $education->start_year ?: '—' }} – {{ $education->graduation_year }}</span>@endif</div><h3>{{ $education->qualification ?: $education->institution_name }}</h3>@if ($education->field_of_study)<p class="education-field">{{ $education->field_of_study }}</p>@endif<p class="organization-line">{{ $education->institution_name }}</p>@if ($education->description)<p class="body-copy">{{ $education->description }}</p>@endif</article>@endforeach</div>
            </div></section>
        @endif

        @if ($person->skills->isNotEmpty())
            <section class="portfolio-section skills-section" id="skills" aria-labelledby="skills-title"><div class="section-inner"><div class="section-label"><span>04 <i></i> CAPABILITIES</span><h2 id="skills-title">Skills &amp; Domain Expertise</h2><p class="section-intro">Core technologies, frameworks, and methodologies applied across professional work.</p></div>
                <div class="skill-groups">@foreach ($skillGroups as $groupName => $groupSkills)<article class="skill-group"><div class="skill-group-heading"><span class="skill-group-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/><path d="M7 7h.01M7 17h.01"/></svg></span><h3>{{ $groupName }}</h3></div><div class="skill-list">@foreach ($groupSkills as $skill)<span class="skill-tag">{{ $skill->name }}</span>@endforeach</div></article>@endforeach</div>
            </div></section>
        @endif

        @if ($person->portfolioProjects->isNotEmpty())
            <section class="projects-section" id="projects" aria-labelledby="projects-title"><div class="section-inner"><div class="projects-heading"><div><div class="section-label"><span>05 <i></i> SELECTED WORK</span><h2 id="projects-title">Projects &amp; Portfolio</h2></div></div><p>Professional systems, products, and selected project work.</p></div>
                <div class="project-grid">@foreach ($person->portfolioProjects as $project)<article class="project-card">
                    @if ($project->image_path)<img class="project-image" src="{{ Storage::disk('supabase')->exists($project->image_path) ? Storage::disk('supabase')->url($project->image_path) : Storage::disk('public')->url($project->image_path) }}" alt="{{ $project->title }} preview" loading="lazy">@else<div class="project-image project-placeholder" aria-hidden="true">{{ Str::upper(Str::substr($project->title, 0, 1)) }}</div>@endif
                    <div class="project-content"><h3>{{ $project->title }}</h3>@if ($project->description)<p>{{ $project->description }}</p>@endif @if ($project->technologies)<div class="project-tech">@foreach ($project->technologies as $technology)<span>{{ $technology }}</span>@endforeach</div>@endif<div class="project-links">@if ($project->project_url)<a href="{{ $project->project_url }}" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">↗</span> Live Project</a>@endif @if ($project->github_url)<a href="{{ $project->github_url }}" target="_blank" rel="noopener noreferrer">Source Code</a>@endif</div></div>
                </article>@endforeach</div>
            </div></section>
        @endif

        @if ($person->certifications->isNotEmpty())
            <section class="portfolio-section certification-section" id="certifications" aria-labelledby="certifications-title"><div class="section-inner"><div class="section-label"><span>06 <i></i> RECOGNITION</span><h2 id="certifications-title">Certifications &amp; Achievements</h2></div>
                <div class="certification-grid">@foreach ($person->certifications as $certification)<article class="certification-card">@if ($certification->image_path)<img class="certificate-icon" src="{{ Storage::disk('supabase')->exists($certification->image_path) ? Storage::disk('supabase')->url($certification->image_path) : Storage::disk('public')->url($certification->image_path) }}" alt="" loading="lazy">@else<span class="certificate-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="m8.5 12-1 9 4.5-3 4.5 3-1-9"/></svg></span>@endif<div class="certification-copy"><p class="section-kicker">{{ $certification->issuer ?: 'CERTIFICATION' }}</p><h3>{{ $certification->name }}</h3>@if ($certification->issuer)<p>{{ $certification->issuer }}</p>@endif @if ($certification->issue_date)<small>Issued {{ $certification->issue_date->format('M Y') }}</small>@endif</div>@if ($certification->credential_url)<a class="credential-link" href="{{ $certification->credential_url }}" target="_blank" rel="noopener noreferrer" aria-label="View {{ $certification->name }} credential"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h7v7m0-7L10 14"/><path d="M19 13v7H4V5h7"/></svg></a>@endif</article>@endforeach</div>
            </div></section>
        @endif

        <section class="contact-section" id="contact" aria-labelledby="contact-title"><div class="contact-main"><div class="section-label"><span>07 <i></i> CONNECT</span><h2 id="contact-title">Get in touch with {{ Str::before($person->full_name, ' ') }}</h2></div><p class="contact-intro">Available for professional opportunities, collaboration, and conversation.</p>
                <div class="contact-list">
                    @if ($person->email)<div class="contact-row"><span class="contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><div><small>EMAIL ADDRESS</small><a href="mailto:{{ $person->email }}">{{ $person->email }}</a></div><button class="copy-link" type="button" data-copy="{{ $person->email }}" aria-label="Copy email"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>Copy</button></div>@endif
                    @if ($person->phone)<div class="contact-row"><span class="contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.8a2 2 0 0 1-.6 1.8L7.7 9.6a16 16 0 0 0 6.7 6.7l1.3-1.3a2 2 0 0 1 1.8-.6l2.8.5a2 2 0 0 1 1.7 2Z"/></svg></span><div><small>PHONE / DIRECT</small><a href="tel:{{ preg_replace('/[^+\d]/', '', $person->phone) }}">{{ $person->phone }}</a></div><button class="copy-link" type="button" data-copy="{{ $person->phone }}" aria-label="Copy phone number"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>Copy</button></div>@endif
                    @if ($person->location)<div class="contact-row"><span class="contact-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><div><small>BASE LOCATION</small><strong>{{ $person->location }}</strong></div></div>@endif
                </div>
                <div class="contact-actions">@if ($person->email)<a class="button button-primary" href="mailto:{{ $person->email }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>Send Direct Message</a>@endif<button class="button button-secondary" type="button" data-vcard-download><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M5.5 17a3.5 3.5 0 0 1 7 0m4-7h2m-2 4h2"/></svg>Save to Contacts</button></div>
            </div>
            <aside class="share-card"><p class="section-kicker">DIGITAL IDENTITY PASS</p><h3>Share this Portfolio</h3><p class="share-description">Scan this QR code with any smartphone camera to open or share this public profile.</p>
                @if ($person->currentQrCode)<div class="share-qr-card"><img src="{{ route('admin.qr-codes.preview', [$person->currentQrCode, 'png']) }}" alt="QR code linking to {{ $person->full_name }}’s portfolio"><div><small>PUBLIC PROFILE URL</small><a class="profile-url" href="{{ route('profiles.show', ['slug' => $person->public_slug]) }}">{{ route('profiles.show', ['slug' => $person->public_slug]) }}</a><button class="copy-url" type="button" data-copy="{{ route('profiles.show', ['slug' => $person->public_slug]) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.1 0l3-3a5 5 0 0 0-7.1-7.1l-1.7 1.7m2.7 6.4a5 5 0 0 0-7.1 0l-3 3a5 5 0 0 0 7.1 7.1l1.7-1.7"/></svg>Copy Link</button></div></div>@else<div class="share-qr-placeholder"><span>QR</span><p>QR code not generated yet.</p></div>@endif
                @if ($person->linkedin_url || $person->github_url || $person->website_url || !empty($person->other_social_links))<div class="verified-profiles"><p class="section-kicker">PROFESSIONAL LINKS</p><div class="verified-links">@if ($person->linkedin_url)<a href="{{ $person->linkedin_url }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>@endif @if ($person->github_url)<a href="{{ $person->github_url }}" target="_blank" rel="noopener noreferrer">GitHub</a>@endif @if ($person->website_url)<a href="{{ $person->website_url }}" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg>Website</a>@endif @foreach ($person->other_social_links ?? [] as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>@endforeach</div></div>@endif
            </aside>
        </section>
        <footer class="portfolio-footer"><span>© {{ date('Y') }} <strong>{{ $person->full_name }}</strong>. All rights reserved.</span><span>Powered by <a href="{{ route('admin.dashboard') }}">QR Code Portfolio Management System</a></span></footer>
    </main>
    <script>
        document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(button.dataset.copy); button.dataset.originalLabel = button.textContent.trim(); button.textContent = 'Copied'; window.setTimeout(() => { button.textContent = button.dataset.originalLabel; }, 1600); } catch (error) { window.prompt('Copy this information', button.dataset.copy); }
        }));
        document.querySelectorAll('[data-vcard-download]').forEach((button) => button.addEventListener('click', () => {
            const escapeVcard = (value) => String(value || '').replace(/\\/g, '\\\\').replace(/\n/g, '\\n').replace(/,/g, '\\,').replace(/;/g, '\\;');
            const lines = ['BEGIN:VCARD', 'VERSION:3.0', `FN:${escapeVcard(@js($person->full_name))}`];
            @if ($person->email)lines.push(`EMAIL;TYPE=INTERNET:${escapeVcard(@js($person->email))}`);@endif
            @if ($person->phone)lines.push(`TEL;TYPE=CELL:${escapeVcard(@js($person->phone))}`);@endif
            @if ($person->organization)lines.push(`ORG:${escapeVcard(@js($person->organization))}`);@endif
            @if ($person->designation)lines.push(`TITLE:${escapeVcard(@js($person->designation))}`);@endif
            @if ($person->location)lines.push(`ADR;TYPE=WORK:;;${escapeVcard(@js($person->location))};;;;`);@endif
            @if ($person->website_url)lines.push(`URL:${escapeVcard(@js($person->website_url))}`);@endif
            lines.push('END:VCARD');
            const file = new Blob([lines.join('\r\n')], { type: 'text/vcard;charset=utf-8' });
            const link = document.createElement('a'); link.href = URL.createObjectURL(file); link.download = '{{ Str::slug($person->full_name) ?: 'contact' }}.vcf'; link.click(); URL.revokeObjectURL(link.href);
        }));
        document.querySelector('[data-about-toggle]')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const preview = document.querySelector('[data-about-preview]');
            const fullAbout = document.querySelector('[data-about-full]');
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!isExpanded));
            preview.hidden = !isExpanded;
            fullAbout.hidden = isExpanded;
            button.innerHTML = isExpanded ? 'Read more <span aria-hidden="true">↓</span>' : 'Read less <span aria-hidden="true">↑</span>';
        });
        document.querySelector('[data-toggle-socials]')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const extraLinks = document.querySelectorAll('[data-extra-social]');
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            extraLinks.forEach((link) => { link.hidden = isExpanded; });
            button.setAttribute('aria-expanded', String(!isExpanded));
            button.innerHTML = isExpanded ? `Show all {{ $onlineLinks->count() }} links <span aria-hidden="true">↓</span>` : `${button.dataset.expandedLabel} <span aria-hidden="true">↑</span>`;
        });
        const qrDialog = document.querySelector('.mobile-qr-dialog');
        document.querySelector('[data-open-qr]')?.addEventListener('click', () => qrDialog?.showModal());
        document.querySelector('[data-close-qr]')?.addEventListener('click', () => qrDialog?.close());
        qrDialog?.addEventListener('click', (event) => { if (event.target === qrDialog) qrDialog.close(); });
        document.querySelectorAll('[data-share-profile]').forEach((button) => button.addEventListener('click', async () => {
            const profileUrl = @js(route('profiles.show', ['slug' => $person->public_slug]));
            try {
                if (navigator.share) { await navigator.share({ title: @js($person->full_name), url: profileUrl }); }
                else { await navigator.clipboard.writeText(profileUrl); button.textContent = 'Link copied'; window.setTimeout(() => { button.textContent = 'Share Card'; }, 1600); }
            } catch (error) {
                if (error.name !== 'AbortError') { window.prompt('Copy the profile link', profileUrl); }
            }
        }));
        const sectionLinks = [...document.querySelectorAll('.portfolio-nav a')];
        const sectionObserver = new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) { sectionLinks.forEach((link) => link.classList.toggle('is-active', link.hash === `#${entry.target.id}`)); } }), { rootMargin: '-25% 0px -65% 0px' });
        document.querySelectorAll('main section[id]').forEach((section) => sectionObserver.observe(section));
    </script>
    <script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}" defer></script>
</body>
</html>
