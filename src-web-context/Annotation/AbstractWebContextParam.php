<?php

namespace Ray\WebContextParam\Annotation;

abstract class AbstractWebContextParam
{
    /**
     * Key of Super global value
     */
    const GLOBAL_KEY = '';

    /**
     * @param string $key Key of query parameter
     */
    public function __construct(public string $key)
    {
    }
}
