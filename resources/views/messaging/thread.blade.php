{{-- The messages of a conversation, oldest first; the viewer's own messages on the right. --}}
<div style="display: flex; flex-direction: column; gap: 0.75rem;">
    @forelse ($messages as $message)
        @php
            $isOwn = $message->author_id === $viewer->getKey();
        @endphp
        <div style="display: flex; justify-content: {{ $isOwn ? 'flex-end' : 'flex-start' }};">
            <div style="max-width: 80%; padding: 0.625rem 0.875rem; border-radius: 0.75rem; border: 1px solid rgba(127, 127, 127, 0.25); background: {{ $isOwn ? 'rgba(14, 165, 233, 0.12)' : 'rgba(127, 127, 127, 0.08)' }};">
                <div style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 0.25rem;">
                    {{ $message->author_label }} · {{ $message->created_at->translatedFormat('j F Y à H:i') }}
                </div>
                <div style="white-space: pre-line;">{{ $message->body }}</div>
            </div>
        </div>
    @empty
        <p style="opacity: 0.7;">Aucun message.</p>
    @endforelse
</div>
