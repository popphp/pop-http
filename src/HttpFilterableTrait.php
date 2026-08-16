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
namespace Pop\Http;

use Pop\Filter\FilterableTrait;

/**
 * HTTP filterable trait
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
trait HttpFilterableTrait
{

    use FilterableTrait;

    /**
     * Cached parse of ini's disable_functions list - a static php.ini setting that cannot
     * change during a request, so re-parsing it on every filter() call is pure waste
     * @var ?array
     */
    protected static ?array $disabledFunctions = null;

    /**
     * Filter values
     *
     * @param  mixed $values
     * @return mixed
     */
    public function filter(mixed $values): mixed
    {
        if (self::$disabledFunctions === null) {
            self::$disabledFunctions = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        }
        $disabledFunctions = self::$disabledFunctions;

        foreach ($this->filters as $filter) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    if (!in_array($value, $disabledFunctions)) {
                        $values[$key] = $filter->filter($value, $key);
                    }
                }
            } else {
                if (!in_array($values, $disabledFunctions)) {
                    $values = $filter->filter($values);
                }
            }
        }

        return $values;
    }

}
