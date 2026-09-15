@props([
    'url',
    'color' => 'primary',
    'align' => 'left',
])

<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 26px 0;">
    <tr>
        <td align="{{ $align }}">
            <a href="{{ $url }}" target="_blank" rel="noopener" style="display: inline-block; padding: 12px 20px; border-radius: 6px; background-color: #162d4a; color: #ffffff; font-size: 14px; line-height: 1.2; font-weight: 600; text-decoration: none;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
