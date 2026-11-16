<?php
// Extract literal HTML from PHP templates without confusing PHP expressions with
// HTML attribute boundaries. Preserve line numbers for actionable diagnostics.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = file_get_contents($argv[1]);
$html = '';
foreach (token_get_all($source) as $token) {
    $text = is_array($token) ? $token[1] : $token;
    $html .= is_array($token) && $token[0] === T_INLINE_HTML
        ? $text
        : preg_replace('/[^\r\n]/', ' ', $text);
}
echo $html;
