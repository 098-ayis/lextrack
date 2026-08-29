<div>
    {{-- Well begun is half done. - Aristotle --}}
    @if ($this->unreadCount > 0)
        <span class="message-nav-badge">
            {{ $this->unreadCount }}
        </span>
    @endif
</div>
