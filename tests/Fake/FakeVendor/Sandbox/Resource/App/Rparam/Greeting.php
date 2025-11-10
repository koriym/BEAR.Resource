<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Rparam;

use BEAR\Resource\Annotation\AppName;
use BEAR\Resource\Annotation\ResourceParam;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Assisted;
use Ray\Di\Di\Named;

class Greeting extends ResourceObject
{
    #[ResourceParam(uri: 'app://self/rparam/login#login_id', param: 'name')]
    public function onGet(?string $name = null, #[Assisted, Named(AppName::class)] ?string $appName = null)
    {
        $this->body = [
            'name' => $name,
            'appName' => $appName
        ];

        return $this;
    }

    public function onPut(string $name)
    {
    }

    #[ResourceParam(uri: 'app://self/rparam/login{?name}#nickname', templated: true, param: 'id')]
    public function onPost(string $id, string $name)
    {
        $this['id'] = $id;

        return $this;
    }
}
