@props(['url'])

<tr>
    <td class="email-header" style="padding: 26px 32px; background-color: #162d4a; border-radius: 10px 10px 0 0;">
        <a href="{{ $url }}" style="display: inline-block; color: #ffffff; text-decoration: none;">
            <span style="display: block; color: #ffffff; font-size: 22px; line-height: 1.2; font-weight: 700; letter-spacing: 0.1px;">
                {{ $slot }}
            </span>
            <span style="display: block; margin-top: 5px; color: #c9d8ea; font-size: 12px; line-height: 1.4; font-weight: 500; letter-spacing: 0.2px;">
                Bicol University Legal Affairs Office
            </span>
        </a>
    </td>
</tr>
