<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler;

/**
 * HTTP client abstract curl class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractCurl extends AbstractHandler
{

    /**
     * Curl options
     * @var array
     */
    protected array $options = [];

    /**
     * Curl response
     * @var mixed
     */
    protected mixed $response = null;

    /**
     * Constructor
     *
     * Instantiate the Curl handler object
     *
     * @param  ?array $opts
     * @throws Exception
     */
    public function __construct(?array $opts = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Error: Curl is not available.');
        }

        if (str_contains(get_class($this), 'Multi')) {
            $this->resource = curl_multi_init();
        } else {
            $this->resource = curl_init();
            $this->setOption(CURLOPT_HEADER, true);
            $this->setOption(CURLOPT_RETURNTRANSFER, true);
        }

        if ($opts !== null) {
            $this->setOptions($opts);
        }
    }

    /**
     * Get Curl response
     *
     * @param  mixed $response
     * @return AbstractCurl
     */
    public function setResponse(mixed $response): AbstractCurl
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get Curl response
     *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }

    /**
     * Has a Curl response
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return ($this->response !== null);
    }

    /**
     * Set Curl option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return AbstractCurl
     */
    public function setOption(int $opt, mixed $val): AbstractCurl
    {
        // Set the protected property to keep track of the Curl options.
        $this->options[$opt] = $val;
        if (str_contains(get_class($this), 'Multi')) {
            curl_multi_setopt($this->resource, $opt, $val);
        } else {
            curl_setopt($this->resource, $opt, $val);
        }

        return $this;
    }

    /**
     * Set Curl options
     *
     * @param  array $opts
     * @return AbstractCurl
     */
    public function setOptions(array $opts): AbstractCurl
    {
        // Set the protected property to keep track of the Curl options.
        foreach ($opts as $k => $v) {
            $this->setOption($k, $v);
        }

        return $this;
    }

    /**
     * Get a Curl option
     *
     * @param  int $opt
     * @return mixed
     */
    public function getOption(int $opt): mixed
    {
        return $this->options[$opt] ?? null;
    }

    /**
     * Remove Curl option
     *
     * @param  int $opt
     * @return AbstractCurl
     */
    public function removeOption(int $opt): AbstractCurl
    {
        if (isset($this->options[$opt])) {
            if (str_contains(get_class($this), 'Multi')) {
                curl_multi_setopt($this->resource, $opt, null);
            } else {
                curl_setopt($this->resource, $opt, null);
            }

            unset($this->options[$opt]);
        }
        return $this;
    }

    /**
     * Has a Curl option
     *
     * @param  int $opt
     * @return bool
     */
    public function hasOption(int $opt): bool
    {
        return (isset($this->options[$opt]));
    }

    /**
     * Return curl resource (alias to $this->getResource())
     *
     * @return mixed
     */
    public function curl(): mixed
    {
        return $this->resource;
    }

    /**
     * Get Curl error number
     *
     * @return int
     */
    public function getErrorNumber(): int
    {
        if (str_contains(get_class($this), 'Multi')) {
            return curl_multi_errno($this->resource);
        } else {
            return curl_errno($this->resource);
        }

    }

    /**
     * Get Curl error number
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if (str_contains(get_class($this), 'Multi')) {
            return curl_multi_strerror(curl_multi_errno($this->resource));
        } else {
            return curl_error($this->resource);
        }
    }

    /**
     * Return the Curl version
     *
     * @return array
     */
    public function version(): array
    {
        return curl_version();
    }

}
