<?php

namespace Mk4U\Router\Exceptions;

use Mk4U\Http\Status;

class RouterException extends \Exception
{
    public function __construct(protected Status $status = Status::NotFound)
    {
        $this->status = $status;
        parent::__construct($status->message(), $status->value);
    }

    public function getStatus(): array|Status
    {
        return $this->status;
    }
}
