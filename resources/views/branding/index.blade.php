@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--theme-primary)]">Brand Customization</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Customize your navbar look</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Only the navbar theme color and navbar logo can be changed here. The rest of the system UI keeps the default styling.
                </p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <form action="{{ route('branding.update') }}" method="POST" enctype="multipart/form-data" class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm lg:p-8" data-loading-message="Saving your branding customization...">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Navbar Theme</h3>
                    <p class="mt-2 text-sm text-slate-500">Choose the main color used by the navbar, active nav tab, and navbar accents.</p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-[auto_1fr] md:items-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-[24px] border border-slate-200 bg-slate-50">
                        <input type="color" id="themePrimaryPicker" value="{{ old('theme_primary', $savedThemeColor) }}" class="h-14 w-14 cursor-pointer rounded-xl border-0 bg-transparent p-0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Navbar color</label>
                        <input
                            type="text"
                            id="themePrimaryInput"
                            name="theme_primary"
                            value="{{ old('theme_primary', $savedThemeColor) }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-[var(--theme-primary)] focus:ring-4 focus:ring-[var(--theme-primary-soft)]"
                            placeholder="#4F81C7"
                        >
                        <p class="mt-2 text-xs text-slate-500">Use a full hex color like <span class="font-semibold text-slate-700">#4F81C7</span>.</p>
                    </div>
                </div>

                <div class="mt-8 border-t border-slate-100 pt-8">
                    <h3 class="text-xl font-semibold text-slate-900">Navbar Logo</h3>
                    <p class="mt-2 text-sm text-slate-500">Upload a custom logo to replace the default navbar logo for your license workspace.</p>

                    <div class="mt-5 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-5">
                        <label class="block text-sm font-medium text-slate-700">Upload logo</label>
                        <input
                            type="file"
                            name="brand_logo"
                            accept="image/*"
                            class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-2xl file:border-0 file:bg-[var(--theme-primary-soft)] file:px-4 file:py-2.5 file:font-medium file:text-[var(--theme-primary-ink)] hover:file:bg-[var(--theme-primary-soft-strong)]"
                        >
                        <p class="mt-3 text-xs text-slate-500">Maximum logo file size: 10 MB.</p>
                        <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_brand_logo" value="1" class="rounded border-slate-300">
                            Remove current custom logo and use the default one
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[var(--theme-primary)] px-6 py-3 font-semibold text-white transition hover:bg-[var(--theme-primary-dark)]">
                        Save Branding
                    </button>
                </div>
            </form>

            <aside class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
                <h3 class="text-xl font-semibold text-slate-900">Live Preview</h3>
                <p class="mt-2 text-sm text-slate-500">Preview of your navbar color and logo setup.</p>

                <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200">
                    <div class="px-5 py-5 text-white" style="background: {{ $branding['header_bg'] }};">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white/10">
                                    <img src="{{ $branding['logo_url'] ?: asset('assets/img/logo.png') }}" alt="Brand logo preview" class="h-full w-full object-contain">
                                </span>
                                <div>
                                    <p class="text-lg font-semibold">tugon.</p>
                                    <p class="text-xs text-white/70">Feedback System</p>
                                </div>
                            </div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
                                <i class="bi bi-bell text-lg"></i>
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border px-4 py-3 text-sm font-semibold" style="background: {{ $branding['soft'] }}; color: {{ $branding['primary'] }}; border-color: {{ $branding['soft_strong'] }};">
                                <i class="bi bi-speedometer2 mr-2"></i>Dashboard
                            </div>
                            <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-medium text-white/90">
                                <i class="bi bi-shop mr-2"></i>Stores
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-800">Current values</p>
                    <p class="mt-3 text-sm text-slate-600">Navbar color: <span class="font-semibold text-slate-900">{{ strtoupper($savedThemeColor) }}</span></p>
                    <p class="mt-2 text-sm text-slate-600">Custom logo: <span class="font-semibold text-slate-900">{{ $branding['logo_url'] ? 'Uploaded' : 'Default logo' }}</span></p>
                </div>
            </aside>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const colorPicker = document.getElementById('themePrimaryPicker');
            const textInput = document.getElementById('themePrimaryInput');

            if (!colorPicker || !textInput) {
                return;
            }

            colorPicker.addEventListener('input', () => {
                textInput.value = colorPicker.value.toUpperCase();
            });

            textInput.addEventListener('input', () => {
                const normalized = textInput.value.trim();

                if (/^#?[0-9a-fA-F]{6}$/.test(normalized)) {
                    colorPicker.value = normalized.startsWith('#') ? normalized : `#${normalized}`;
                }
            });
        })();
    </script>
@endpush
