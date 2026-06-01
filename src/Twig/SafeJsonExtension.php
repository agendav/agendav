<?php

namespace AgenDAV\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Adds a `|js_json` filter for embedding values inside inline <script>
 * blocks. Plain `|json_encode` does not escape '<', '>', '&', "'" or '"',
 * which means a string containing '</script>' would terminate the script
 * tag and let an attacker inject HTML. The flags below force those bytes
 * (and U+2028/U+2029, which old JS parsers treat as line terminators) to
 * their `\uXXXX` form, keeping the value strictly inside the JS string.
 *
 * The filter is marked `is_safe=html` so callers don't need a trailing
 * `|raw` — and forgetting it can no longer turn an inline data dump into
 * an HTML-escaped, broken string.
 */
class SafeJsonExtension extends AbstractExtension
{
    private const FLAGS = JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES;

    public function getFilters(): array
    {
        return [
            new TwigFilter('js_json', [$this, 'jsJson'], ['is_safe' => ['html']]),
        ];
    }

    public function jsJson(mixed $value): string
    {
        return (string) json_encode($value, self::FLAGS);
    }
}
