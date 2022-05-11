<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

/**
 * @see https://www.php.net/manual/en/function.preg-last-error.php
 */
class PregErrorException extends \InvalidArgumentException implements SerializedStringExceptionInterface
{
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}
