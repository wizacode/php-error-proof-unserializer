<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

class InvalidSerializedStringException extends \InvalidArgumentException implements SerializedStringExceptionInterface
{
    public function __construct()
    {
        parent::__construct("Invalid serialized string");
    }
}
