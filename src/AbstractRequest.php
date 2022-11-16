<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http;

use Pop\Filter\FilterableTrait;

/**
 * Abstract HTTP request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.1.0
 */
abstract class AbstractRequest extends AbstractHttp
{

    use FilterableTrait;

    /**
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  mixed  $filters
     */
    public function __construct($filters = null)
    {
        if (null !== $filters) {
            if (is_array($filters)) {
                $this->addFilters($filters);
            } else {
                $this->addFilter($filters);
            }
        }
    }

    /**
     * Filter values
     *
     * @param  mixed $values
     * @return mixed
     */
    public function filter($values)
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