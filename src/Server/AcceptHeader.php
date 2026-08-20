<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Server;

/**
 * HTTP accept header class
 *
 * RFC 7231 Section 5.3.2 compliant Accept header parser/negotiator. Built on QualityValue's
 * generic 'value;q=N' parsing, adding media-type wildcard matching (*\/*, type/*) and
 * specificity-based precedence (exact match beats type/* beats *\/*). Media-range parameters
 * other than 'q' (e.g. ';level=1') are ignored for matching purposes.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class AcceptHeader
{

    /**
     * Parsed, quality-sorted entries
     * @var QualityValue[]
     */
    protected array $entries;

    /**
     * Constructor
     *
     * A null or empty header is treated as '*\/*' - per RFC 7231, a request with no Accept
     * header implies the client will accept any media type in response.
     *
     * @param  ?string $header
     */
    public function __construct(?string $header = null)
    {
        $header        = (($header !== null) && ($header !== '')) ? $header : '*/*';
        $this->entries = QualityValue::parseList($header);
    }

    /**
     * Get the effective quality for a concrete media type, resolved by specificity:
     * exact type/subtype match > type/* > *\/*. Among entries tied at the same specificity,
     * the highest quality wins. Returns 0.0 if nothing matches, or if the highest-specificity
     * match is explicitly excluded via q=0.
     *
     * @param  string $mediaType
     * @return float
     */
    public function matches(string $mediaType): float
    {
        [$type, $subtype] = self::splitMediaType($mediaType);

        $bestSpecificity = -1;
        $bestQuality     = 0.0;

        foreach ($this->entries as $entry) {
            [$entryType, $entrySubtype] = self::splitMediaType($entry->getValue());

            if (($entryType === $type) && ($entrySubtype === $subtype)) {
                $specificity = 2;
            } else if (($entryType === $type) && ($entrySubtype === '*')) {
                $specificity = 1;
            } else if (($entryType === '*') && ($entrySubtype === '*')) {
                $specificity = 0;
            } else {
                continue;
            }

            if (($specificity > $bestSpecificity) ||
                (($specificity === $bestSpecificity) && ($entry->getQuality() > $bestQuality))) {
                $bestSpecificity = $specificity;
                $bestQuality     = $entry->getQuality();
            }
        }

        return $bestQuality;
    }

    /**
     * Whether any of the given media type(s) is acceptable
     *
     * @param  string|array $types
     * @return bool
     */
    public function accepts(string|array $types): bool
    {
        foreach ((array)$types as $type) {
            if ($this->matches($type) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Given the media types this server can actually respond with, return the client's
     * best match. Ties (equal matches() score) are broken by $available's own order - the
     * server's stated preference wins, since HTTP doesn't mandate an order for equal-quality
     * client preferences. Returns null if every candidate scores 0.
     *
     * @param  string[] $available
     * @return string|null
     */
    public function getPreferredType(array $available): ?string
    {
        $best      = null;
        $bestScore = 0.0;

        foreach ($available as $type) {
            $score = $this->matches($type);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $type;
            }
        }

        return $best;
    }

    /**
     * Split a media type into [type, subtype]. A value with no '/' is treated as [$value, '*']
     * so malformed entries degrade gracefully instead of raising a warning.
     *
     * @param  string $mediaType
     * @return array
     */
    protected static function splitMediaType(string $mediaType): array
    {
        $mediaType = trim($mediaType);
        if (!str_contains($mediaType, '/')) {
            return [$mediaType, '*'];
        }

        [$type, $subtype] = explode('/', $mediaType, 2);
        return [trim($type), trim($subtype)];
    }

}
