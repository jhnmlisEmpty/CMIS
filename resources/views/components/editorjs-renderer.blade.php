@props(['content'])

@php
$blocks = [];
if ($content) {
    try {
        $data = is_string($content) ? json_decode($content, true) : $content;
        $blocks = $data['blocks'] ?? [];
    } catch (\Exception $e) {
        $blocks = [];
    }
}
@endphp

<div {{ $attributes->merge(['class' => 'editorjs-content space-y-3 text-base']) }}>
    @forelse($blocks as $block)
        @switch($block['type'])
            @case('header')
                @php $level = $block['data']['level'] ?? 2; @endphp
                @if($level === 2)
                    <h2 class="text-2xl font-semibold text-gray-900 mt-4 mb-2">{!! $block['data']['text'] !!}</h2>
                @elseif($level === 3)
                    <h3 class="text-xl font-semibold text-gray-900 mt-3 mb-2">{!! $block['data']['text'] !!}</h3>
                @elseif($level === 4)
                    <h4 class="text-lg font-semibold text-gray-900 mt-2 mb-1">{!! $block['data']['text'] !!}</h4>
                @else
                    <h5 class="text-base font-medium text-gray-900 mt-2 mb-1">{!! $block['data']['text'] !!}</h5>
                @endif
                @break
            
            @case('paragraph')
                <p class="text-gray-700 leading-relaxed">{!! $block['data']['text'] !!}</p>
                @break
            
            @case('list')
                @if(($block['data']['style'] ?? 'unordered') === 'ordered')
                    <ol class="list-decimal list-inside space-y-1 text-gray-700 pl-1">
                        @foreach($block['data']['items'] as $item)
                            <li>{!! is_array($item) ? $item['content'] : $item !!}</li>
                        @endforeach
                    </ol>
                @else
                    <ul class="list-disc list-inside space-y-1 text-gray-700 pl-1">
                        @foreach($block['data']['items'] as $item)
                            <li>{!! is_array($item) ? $item['content'] : $item !!}</li>
                        @endforeach
                    </ul>
                @endif
                @break
            
            @case('quote')
                <blockquote class="border-l-4 border-gray-300 pl-4 py-2 my-3 bg-gray-50 rounded-r-md">
                    <p class="text-gray-700 italic">{!! $block['data']['text'] !!}</p>
                    @if(!empty($block['data']['caption']))
                        <cite class="text-sm text-gray-500 mt-1 block">- {{ $block['data']['caption'] }}</cite>
                    @endif
                </blockquote>
                @break
            
            @case('delimiter')
                <div class="flex items-center justify-center py-4">
                    <span class="text-gray-300 text-2xl tracking-widest">***</span>
                </div>
                @break
            
            @default
                @if(!empty($block['data']['text']))
                    <p class="text-gray-700">{!! $block['data']['text'] !!}</p>
                @endif
        @endswitch
    @empty
        <p class="text-gray-400 italic">No content yet.</p>
    @endforelse
</div>

