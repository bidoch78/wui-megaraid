<?php

declare(strict_types=1);

namespace megaraid\exceptions;

use megaraid\exceptions\httpException;

class pageNotFoundException extends httpException {

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null) {
        parent::__construct(404, $message, $code, $previous);
    }

}

?>