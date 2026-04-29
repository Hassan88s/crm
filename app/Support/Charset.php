<?php

namespace App\Support;

class Charset
{
    /**
     * Try to convert $text to UTF-8 from the given $charset.
     * Handles common alias issues (Windows-1250, ISO-8859-2, etc.) and
     * never throws — falls back to the original string on any failure.
     */
    public static function toUtf8(?string $text, ?string $charset): string
    {
        if ($text === null) return '';
        if ($text === '')   return '';

        $charset = trim((string) $charset);
        if ($charset === '' || strcasecmp($charset, 'UTF-8') === 0) {
            return $text;
        }

        $normalised = self::normalise($charset);
        if ($normalised === null || strcasecmp($normalised, 'UTF-8') === 0) {
            return $text;
        }

        try {
            $out = @mb_convert_encoding($text, 'UTF-8', $normalised);
            if (is_string($out) && $out !== '') return $out;
        } catch (\Throwable $e) {
            // ignore, fall through
        }

        // Last-ditch: try with iconv (more permissive about names) ignoring errors
        try {
            $out = @iconv($normalised, 'UTF-8//IGNORE', $text);
            if (is_string($out) && $out !== '') return $out;
        } catch (\Throwable $e) {
            // ignore
        }

        return $text;
    }

    /**
     * Map an arbitrary charset name to one mbstring/iconv recognises.
     * Returns null if it really can't be matched.
     */
    public static function normalise(string $charset): ?string
    {
        $c = strtoupper(trim($charset));

        // Common aliases that newer PHP rejects in all-caps form
        static $aliases = [
            'WINDOWS-1250' => 'CP1250',
            'WINDOWS-1251' => 'CP1251',
            'WINDOWS-1252' => 'CP1252',
            'WINDOWS-1253' => 'CP1253',
            'WINDOWS-1254' => 'CP1254',
            'WINDOWS-1255' => 'CP1255',
            'WINDOWS-1256' => 'CP1256',
            'WINDOWS-1257' => 'CP1257',
            'WINDOWS-1258' => 'CP1258',
            'WIN-1250' => 'CP1250',
            'WIN-1252' => 'CP1252',
            'CP-1250' => 'CP1250',
            'CP-1251' => 'CP1251',
            'CP-1252' => 'CP1252',
            'MACROMAN' => 'MacRoman',
            'MAC-ROMAN' => 'MacRoman',
            'ANSI_X3.4-1968' => 'ASCII',
            'US-ASCII' => 'ASCII',
            'X-USER-DEFINED' => 'UTF-8',
            // Quoted-printable / printable-ascii edge cases
            '7BIT' => 'ASCII',
            '8BIT' => 'UTF-8',
            'BINARY' => 'UTF-8',
        ];

        if (isset($aliases[$c])) return $aliases[$c];

        // If mbstring supports it (case-insensitive lookup), keep it
        $supported = mb_list_encodings();
        foreach ($supported as $enc) {
            if (strcasecmp($enc, $charset) === 0) return $enc;
        }

        // Many ISO-8859-N variants are accepted as-is even after normalisation
        if (preg_match('/^ISO[-_]8859-?(\d+)$/i', $c, $m)) {
            return 'ISO-8859-' . $m[1];
        }

        // Drop unknown — caller will fall back to original bytes
        return null;
    }
}
