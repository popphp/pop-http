<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
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
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3.2
 */
trait HttpFilterableTrait
{

    use FilterableTrait;

    /**
     * Filter values
     *
     * @param  mixed $values
     * @return mixed
     */
    public function filter(mixed $values): mixed
    {
        $disabledFunctions = array_filter(array_map('trim', explode(',', ini_get('disable_functions'))));

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
