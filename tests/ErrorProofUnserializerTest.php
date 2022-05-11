<?php

/**
 * @author    Wizacha DevTeam <dev@wizacha.com>
 * @copyright Copyright (c) Wizacha
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Wizacode\ErrorProofUnserializer;

use PHPUnit\Framework\TestCase;

class ErrorProofUnserializerTest extends TestCase
{
    private const BACKTRACK_LIMIT_KEY = 'pcre.backtrack_limit';
    private string $backtrackLimit;

    public function setUp(): void
    {
        $this->backtrackLimit = (string) \ini_get(
            self::BACKTRACK_LIMIT_KEY
        );
    }

    public function tearDown(): void
    {
        \ini_set(
            self::BACKTRACK_LIMIT_KEY,
            $this->backtrackLimit
        );
    }

    /**
     * @dataProvider brokenSerializationProvider
     */
    public function testFix(string $input, string $expectedString, mixed $expectedData): void
    {
        static::assertEquals(
            $expectedString,
            ErrorProofUnserializer::fix($input)
        );

        static::assertEquals(
            $expectedData,
            ErrorProofUnserializer::process($input)
        );
    }

    /**
     * @dataProvider truncatedSerializationProvider
     */
    public function testTruncatedSerializationThrowException(string $input): void
    {
        $this->expectException(TruncatedSerializedStringException::class);
        $this->expectExceptionMessage('Truncated serialized string');

        ErrorProofUnserializer::process($input);
    }

    /**
     * @dataProvider invalidSerializationProvider
     */
    public function testInvalidSerializationThrowException(string $input): void
    {
        $this->expectException(InvalidSerializedStringException::class);
        $this->expectExceptionMessage('Invalid serialized string');

        ErrorProofUnserializer::process($input);
    }

    public function testEarlyReturnValidSerialization(): void
    {
        static::assertEquals(
            'hello world',
            ErrorProofUnserializer::process('s:11:"hello world";')
        );
    }

    public function testRegexErrorThrowException(): void
    {
        \ini_set(self::BACKTRACK_LIMIT_KEY, '10');

        $this->expectException(RegexStringException::class);
        $this->expectExceptionMessage('Backtrack limit exhausted');

        ErrorProofUnserializer::fix(
            \sprintf(
                's:1:"%s";',
                str_repeat('hello', 2 ** 10)
            )
        );
    }

    /**
     *
     * @param mixed $data
     * @return mixed[]
     */
    private function getArguments(mixed $data): array
    {
        $serialized = \serialize($data);
        $brokenSerialized = \preg_replace_callback(
            '/\bs:(?<actual_length>\d+):/',
            function (array $matches) {
                $actualLength = (int)$matches['actual_length'];
                $brokenLength = $actualLength + 666;
                return \sprintf(
                    's:%s:',
                    $brokenLength
                );
            },
            $serialized
        );

        static::assertNotEquals($serialized, $brokenSerialized);

        return [
            'input' => $brokenSerialized,
            'expectedString' => $serialized,
            'expectedData' => $data,
        ];
    }

    /**
     *
     * @return mixed[]
     */
    public function brokenSerializationProvider(): array
    {
        // phpcs:disable
        return [
            'string alone' => $this->getArguments(
                'hello "world";'
            ),
            'next is string' => $this->getArguments(
                ['hello "world";', 'a']
            ),
            'next is integer' => $this->getArguments(
                ['hello "world";', 123]
            ),
            'next is decimal' => $this->getArguments(
                ['hello "world";', 1.0023]
            ),
            'next is array' => $this->getArguments(
                ['hello "world";', ['Y', 12]]
            ),
            'next is null' => $this->getArguments(
                ['hello "world";', null]
            ),
            'next is object' => $this->getArguments(
                ['hello "world";', new ErrorProofUnserializer('inception')],
            ),
            'next is boolean' => $this->getArguments(
                ['hello "world";', false]
            ),
            'array end' => $this->getArguments(
                [['hello "world";']]
            ),
            'unexpected array end signal in string' =>  $this->getArguments(
                [['hello "w";}orld";']]
            ),
            'extreme unexpected array end signal in string' =>  $this->getArguments(
                [[0, 'hello "w";}orld";', 1, null]]
            ),
            'new lines' => $this->getArguments(
                [
                    <<<SER
                        "SALUT les cop\r \r\nain "ça" swingue ?

                            des "quo\ntes"; ";;""";\n\r";"; ici
                        SER,
                    [1, 2, 3],
                    null
                ]
            ),
            'inception' => $this->getArguments(
                new ErrorProofUnserializer('Hello "; "world;";;"'),
            ),
        ];
        // phpcs:enable
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function truncatedSerializationProvider(): array
    {
        return [
            'cutted' => ['s:10:"hell'],
            'not serialization at all' => ['👹👹👹👹👹👹'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function invalidSerializationProvider(): array
    {
        return [
            '👹' => ['👹👹👹👹👹👹;'],
            'look a like' => ['u:5:"hello";'],
            'kinda near' => ['s:i:"hello";']
        ];
    }
}
