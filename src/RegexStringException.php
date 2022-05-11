<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

class RegexStringException extends \InvalidArgumentException implements SerializedStringExceptionInterface
{
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}
