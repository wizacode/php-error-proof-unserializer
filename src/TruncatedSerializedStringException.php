<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   MIT
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

class TruncatedSerializedStringException extends \InvalidArgumentException implements SerializedStringExceptionInterface
{
    public function __construct()
    {
        parent::__construct("Truncated serialized string");
    }
}
