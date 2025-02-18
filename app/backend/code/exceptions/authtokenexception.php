<?php

declare(strict_types=1);

namespace megaraid\exceptions;

use \megaraid\exceptions\httpException;

class authTokenException extends httpException {

    public function __construct($message = "", int $code = 0, ?\Throwable $previous = null) {
        parent::__construct(498, $message, $code, $previous);
    }

}

?>