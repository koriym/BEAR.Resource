<?php
/**
 * This file is part of the BEAR.Resource package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\Resource;

/**
 * @deprecated
 */
final class Param
{
    /**
     * @param string $class
     * @param string $method
     * @param string $param
     */
    public function __construct(public $class, public $method, public $param)
    {
    }
}
