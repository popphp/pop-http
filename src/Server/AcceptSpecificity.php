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
 * HTTP accept specificity enum
 *
 * Controls how strict an AcceptHeader/Server\Request match must be. Maps directly onto
 * AcceptHeader::matches()'s internal per-entry specificity tiers (exact type/subtype match = 2,
 * a subtype wildcard = 1, a bare full wildcard = 0) - the enum's backing value IS that threshold,
 * so enforcing a minimum is a direct integer comparison, no translation table needed.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
enum AcceptSpecificity: int
{

    /**
     * A bare full wildcard entry (any type, any subtype) may satisfy the match - default, today's behavior
     */
    case Any = 0;

    /**
     * A subtype wildcard or an exact type/subtype match satisfies; a bare full wildcard entry does not
     */
    case Loose = 1;

    /**
     * Only a literal type/subtype match satisfies
     */
    case Exact = 2;

}
