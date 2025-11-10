<?php

declare(strict_types=1);

namespace FakeVendor\News\Resource\App;

use BEAR\Resource\ResourceObject;
use Ray\WebContextParam\Annotation\CookieParam;
use Ray\WebContextParam\Annotation\EnvParam;
use Ray\WebContextParam\Annotation\FormParam;
use Ray\WebContextParam\Annotation\QueryParam;
use Ray\WebContextParam\Annotation\ServerParam;

class AttrWebContext extends ResourceObject
{
    public function onGet($id, $name = 'koriym')
    {
    }

    /**
     * Forward compatible attribute
     */
    public function onPost(
        #[CookieParam("c")] string $cookie,
        #[EnvParam("e")] string $env,
        #[FormParam("f")] string $form,
        #[QueryParam("q")] string $query,
        #[ServerParam("s")] string $server
    ) {
    }

    public function onPut(#[CookieParam('c')] string $cookie)
    {
    }

    public function onDelete(string $a, #[CookieParam('c')] string $cookie = 'default')
    {
    }
}
