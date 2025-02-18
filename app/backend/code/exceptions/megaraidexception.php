<?php

declare(strict_types=1);

namespace megaraid\exceptions;

class megaraidException extends \Exception {

    const LOGIN_FAILED = 1010;
    const MIDDLEWARE_ERROR = 910;
    const TOKEN_GENERATOR = 920;
    const TOKEN_VALIDATOR = 921;
        
}

?>