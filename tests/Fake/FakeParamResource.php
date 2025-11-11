<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Ray\WebContextParam\Annotation\CookieParam;
use Ray\WebContextParam\Annotation\EnvParam;
use Ray\WebContextParam\Annotation\FormParam;
use Ray\WebContextParam\Annotation\QueryParam;
use Ray\WebContextParam\Annotation\ServerParam;

class FakeParamResource extends ResourceObject
{
    public function onGet($id, $name = 'koriym')
    {
    }

    public function onPost(
        #[CookieParam('c')] string $cookie,
        #[EnvParam('e')] string $env,
        #[FormParam('f')]string $form,
        #[QueryParam('q')]string $query,
        #[ServerParam('s')]string $server
    ) {
    }

    public function onPut(#[CookieParam('c')] string $cookie)
    {
    }

    public function onDelete(string $a, #[CookieParam('c')] string $cookie = 'default')
    {
    }
}
