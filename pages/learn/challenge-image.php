<?php

function getChallengeImageUrl(string $imageUrl, int $width): string
{
    $parts = parse_url($imageUrl);
    if (
        !is_array($parts)
        || ($parts['scheme'] ?? '') !== 'https'
        || ($parts['host'] ?? '') !== 'images.unsplash.com'
    ) {
        return $imageUrl;
    }

    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $query['auto'] = 'format';
    $query['fit'] = 'crop';
    $query['w'] = max(80, min($width, 1600));
    $query['q'] = 80;

    return 'https://images.unsplash.com'
        . ($parts['path'] ?? '')
        . '?'
        . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}
