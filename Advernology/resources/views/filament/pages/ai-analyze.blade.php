<x-filament-panels::page>
<div class="space-y-6">

    {{-- Controls --}}
    <x-filament::section heading="AI Business Insights" description="Powered by Claude AI — analyzes your recent payment data and surfaces actionable recommendations.">
        <form wire:submit.prevent="analyze">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                    <span wire:loading.remove>Analyze with AI</span>
                    <span wire:loading>Analyzing… please wait</span>
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Loading skeleton --}}
    <div wire:loading class="animate-pulse space-y-4">
        <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="h-28 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="h-28 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="h-28 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
        </div>
        <div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
    </div>

    @if($result)

        @if(isset($result['_raw']))
            {{-- Fallback: JSON parse failed, show raw text --}}
            <x-filament::section heading="AI Insights">
                <div class="prose prose-sm max-w-none dark:prose-invert whitespace-pre-line leading-relaxed">
                    {{ $result['_raw'] }}
                </div>
            </x-filament::section>

        @else

            {{-- Executive Summary --}}
            @if(!empty($result['summary']))
            <div class="rounded-xl bg-primary-50 dark:bg-primary-950 border border-primary-200 dark:border-primary-800 px-5 py-4 flex items-start gap-3">
                <x-heroicon-o-light-bulb class="w-6 h-6 text-primary-500 mt-0.5 shrink-0"/>
                <p class="text-sm font-medium text-primary-800 dark:text-primary-200 leading-relaxed">{{ $result['summary'] }}</p>
            </div>
            @endif

            {{-- Best Opportunities --}}
            @if(!empty($result['opportunities']))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-500"/>
                        Best Opportunities
                    </div>
                </x-slot>
                <div class="space-y-3">
                    @foreach($result['opportunities'] as $opp)
                    @php
                        $priority = strtolower($opp['priority'] ?? 'medium');
                        $badge = match($priority) {
                            'high'   => 'bg-danger-100 text-danger-700 dark:bg-danger-950 dark:text-danger-300',
                            'low'    => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            default  => 'bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-300',
                        };
                        $border = match($priority) {
                            'high'   => 'border-l-danger-500',
                            'low'    => 'border-l-gray-300 dark:border-l-gray-600',
                            default  => 'border-l-warning-400',
                        };
                    @endphp
                    <div class="border-l-4 {{ $border }} pl-4 py-2 bg-white dark:bg-gray-900 rounded-r-lg shadow-sm">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $opp['title'] }}</span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full uppercase tracking-wide {{ $badge }}">{{ $priority }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $opp['detail'] }}</p>
                    </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif

            {{-- Performance: Highlights & Concerns --}}
            @if(!empty($result['performance_highlights']) || !empty($result['performance_concerns']))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                @if(!empty($result['performance_highlights']))
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500"/>
                            What's Working
                        </div>
                    </x-slot>
                    <ul class="space-y-2">
                        @foreach($result['performance_highlights'] as $h)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-check class="w-4 h-4 text-success-500 mt-0.5 shrink-0"/>
                            {{ $h }}
                        </li>
                        @endforeach
                    </ul>
                </x-filament::section>
                @endif

                @if(!empty($result['performance_concerns']))
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500"/>
                            Watch Out For
                        </div>
                    </x-slot>
                    <ul class="space-y-2">
                        @foreach($result['performance_concerns'] as $c)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 text-warning-500 mt-0.5 shrink-0"/>
                            {{ $c }}
                        </li>
                        @endforeach
                    </ul>
                </x-filament::section>
                @endif

            </div>
            @endif

            {{-- Product Insights --}}
            @if(!empty($result['product_insights']))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-shopping-bag class="w-5 h-5 text-info-500"/>
                        Product Insights
                    </div>
                </x-slot>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($result['product_insights'] as $pi)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-0.5">{{ $pi['product'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pi['insight'] }}</p>
                    </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif

            {{-- Recommendations --}}
            @if(!empty($result['recommendations']))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary-500"/>
                        Action Items (Next 30 Days)
                    </div>
                </x-slot>
                <ol class="space-y-3">
                    @foreach($result['recommendations'] as $i => $rec)
                    @php
                        $p = strtolower($rec['priority'] ?? 'medium');
                        $pBadge = match($p) {
                            'high'  => 'bg-danger-100 text-danger-700 dark:bg-danger-950 dark:text-danger-300',
                            'low'   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            default => 'bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-300',
                        };
                    @endphp
                    <li class="flex items-start gap-3">
                        <span class="flex-none w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xs font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full uppercase tracking-wide {{ $pBadge }}">{{ $p }}</span>
                                @if(!empty($rec['timeframe']))
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $rec['timeframe'] }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $rec['action'] }}</p>
                        </div>
                    </li>
                    @endforeach
                </ol>
            </x-filament::section>
            @endif

        @endif
    @endif

</div>
</x-filament-panels::page>
