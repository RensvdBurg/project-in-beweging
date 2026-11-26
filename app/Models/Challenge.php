<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'description_extra', 'img', 'kcal_burned', 'difficulty', 'xp_reward'])]
class Challenge extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kcal_burned' => 'integer',
            'xp_reward' => 'integer',
        ];
    }

    /**
     * @return HasMany<ChallengeCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(ChallengeCompletion::class);
    }

    /**
     * Afbeelding als volledige URL: externe URL's blijven ongewijzigd,
     * paden worden relatief aan public/ opgelost.
     */
    public function imageUrl(?int $width = null): string
    {
        if (! Str::startsWith($this->img, ['http://', 'https://', '//'])) {
            return asset($this->img);
        }

        return $width === null ? $this->img : self::sizedUnsplashUrl($this->img, $width);
    }

    /**
     * Unsplash-afbeeldingen in het gevraagde formaat laden (vroeger pages/learn/challenge-image.php),
     * zodat de lijst kleine plaatjes ophaalt. Andere URL's blijven ongewijzigd.
     */
    private static function sizedUnsplashUrl(string $url, int $width): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'images.unsplash.com') {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['auto'] = 'format';
        $query['fit'] = 'crop';
        $query['w'] = max(80, min($width, 1600));
        $query['q'] = 80;

        return 'https://images.unsplash.com'
            .($parts['path'] ?? '')
            .'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986)
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
