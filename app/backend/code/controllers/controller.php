<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use megaraid\request;
use megaraid\middlewares\middleware;

abstract class controller {

    protected ?request $request = null;

    public function __construct(request $request = null) {
        $this->request = $request;
    }

    public function getAuth():middleware {
        return $this->request->getMiddleware("auth");
    }

}

?>