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
 * HTTP quality value class
 *
 * Generic RFC 7231 Section 5.3.1 "value;q=N" list parser - has no knowledge of media types,
 * so it applies equally to Accept, Accept-Language, Accept-Encoding and Accept-Charset.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class QualityValue
{

    /**
     * The raw value (e.g. 'text/html', 'en-US')
     * @var string
     */
    protected string $value;

    /**
     * The quality weight, 0.0 - 1.0
     * @var float
     */
    protected float $quality;

    /**
     * Constructor
     *
     * @param  string $value
     * @param  float  $quality
     */
    public function __construct(string $value, float $quality = 1.0)
    {
        $this->value   = $value;
        $this->quality = $quality;
    }

    /**
     * Get the raw value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the quality weight
     *
     * @return float
     */
    public function getQuality(): float
    {
        return $this->quality;
    }

    /**
     * Parse a raw header value into a list of QualityValue entries, sorted by quality
     * descending (stable sort - original order preserved among entries of equal quality).
     * A missing or malformed 'q' parameter defaults to 1.0 rather than being dropped or
     * throwing - this is client-controlled input, and a malformed weight shouldn't fail
     * the request, only fail to deprioritize correctly.
     *
     * @param  string $header
     * @return QualityValue[]
     */
    public static function parseList(string $header): array
    {
        $entries = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $value    = trim(array_shift($segments));
            $quality  = 1.0;

            foreach ($segments as $segment) {
                $segment = trim($segment);
                if (stripos($segment, 'q=') === 0) {
                    $q = trim(substr($segment, 2));
                    if (is_numeric($q) && ((float)$q >= 0) && ((float)$q <= 1)) {
                        $quality = (float)$q;
                    }
                }
            }

            $entries[] = new QualityValue($value, $quality);
        }

        usort($entries, function(QualityValue $a, QualityValue $b) {
            return $b->getQuality() <=> $a->getQuality();
        });

        return $entries;
    }

}
