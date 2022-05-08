<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   ProprietaryWizacode\
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

use Wizacode\ErrorProofUnserializer\InvalidSerializedStringException;

class ErrorProofUnserializer
{
    private string $serialized;

    public function __construct(
        string $serialized
    ) {
        $this->serialized = $serialized;
    }

    /**
     * @return mixed
     */
    public static function process(string $serialized)
    {
        $unserializer = new self($serialized);

        return $unserializer->unserialize();
    }

    public static function fix(string $serialized): string
    {
        $unserializer = new self($serialized);

        return $unserializer->repairIncorrectLength();
    }

    /**
     * @return mixed
     */
    private function unserialize()
    {
        if ($this->isTruncated()) {
            throw new TruncatedSerializedStringException();
        }

        $unserialized = @\unserialize($this->serialized);

        /**
         * early return properly unserialized string
         */
        if ($unserialized !== false) {
            return $unserialized;
        }

        $repairedLengthUnserialized = @\unserialize(
            $this->repairIncorrectLength()
        );

        if (false === $repairedLengthUnserialized) {
            throw new InvalidSerializedStringException();
        }

        return $repairedLengthUnserialized;
    }

    /**
     * Attempt to repair incorrect length in serialized data
     */
    private function repairIncorrectLength(): string
    {
        return \preg_replace_callback(
            $this->getPattern(),
            function (array $matches) {
                $actualString = $matches['actual_string'];
                $expectedLength = \strlen($actualString);

                return \sprintf(
                    's:%s:"%s',
                    $expectedLength,
                    $actualString
                );
            },
            $this->serialized
        );
    }

    private function getPattern(): string
    {
        $commonSignals = [
            's:\d+:"',  # string
            'i:\d+;',  # integer
            'd:\d+(\.\d+)?;', # decimal (float)
            'a:\d+:{',  # array
            'N;',  # null
            'O:\d+:"',  # object
            'b:\d;',  # boolean
            '$',  # string end
        ];

        $nextSignals = [
            ...$commonSignals,
            \sprintf(
                '}+(%s)',
                \implode(
                    '|',
                    $commonSignals
                )
            ),  # array/object stop signals
        ];

        $lookAheadPattern = \sprintf(
            '(?=";(%s))',
            \implode('|', $nextSignals)
        );

        return \sprintf(
            '/(s\:(?<actual_length>\d+):"(?<actual_string>.*?))%s/s',
            $lookAheadPattern
        );
    }

    private function isTruncated(): bool
    {
        $lastChar = $this->serialized[-1];

        return ($lastChar !== ';'
            && $lastChar !== '}'
        );
    }
}
