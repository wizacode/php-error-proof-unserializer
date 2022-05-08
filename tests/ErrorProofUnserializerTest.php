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
    public function testFix(string $input, string $expected): void
    {
        static::assertEquals(
            $expected,
            ErrorProofUnserializer::fix($input)
        );
    }

    /**
     * @dataProvider brokenSerializationProvider
     */
    public function testProcess(string $input, string $expected): void
    {
        static::assertNotEquals(
            false,
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
     * @return array<string, array<int,string>>
     */
    public function brokenSerializationProvider(): array
    {
        // phpcs:disable
        return [
            'next is string' => [
                's:4:"hello "world";";s:2:"a";',
                's:14:"hello "world";";s:1:"a";',
            ],
            'next is integer' => [
                's:4:"hello "world";";i:123;',
                's:14:"hello "world";";i:123;',
            ],
            'next is decimal' => [
                's:4:"hello "world";";d:1.0023;',
                's:14:"hello "world";";d:1.0023;',
            ],
            'next is array' => [
                's:4:"hello "world";";a:2:{s:1:"Y",i:12}',
                's:14:"hello "world";";a:2:{s:1:"Y",i:12}',
            ],
            'next is null' => [
                's:4:"hello "world";";N;',
                's:14:"hello "world";";N;',
            ],
            'next is object' => [
                <<<SER
                    s:4:"hello "world";";O:12:"Tests\MyEnum":2:{s:8:"\000*\000value";s:6:"hello";s:22:"\000MyCLabs\Enum\Enum\000key";s:5:"HELLO";}
                    SER,
                <<<SER
                    s:14:"hello "world";";O:12:"Tests\MyEnum":2:{s:8:"\000*\000value";s:5:"hello";s:22:"\000MyCLabs\Enum\Enum\000key";s:5:"HELLO";}
                    SER,
            ],
            'next is boolean' => [
                's:4:"hello "world";";b:0;',
                's:14:"hello "world";";b:0;',
            ],
            'array end' => [
                'a:1:{i:0;s:4:"hello "world";";}',
                'a:1:{i:0;s:14:"hello "world";";}',
            ],
            'unexpected array end signal in string' =>  [
                'a:1:{i:0;s:4:"hello "w";}orld";";}',
                'a:1:{i:0;s:17:"hello "w";}orld";";}',
            ],
            'extreme unexpected array end signal in string' =>  [
                'a:2:{i:0;s:4:"hello "w";}orld";";i:1;N;}',
                'a:2:{i:0;s:17:"hello "w";}orld";";i:1;N;}',
            ],
            'end of string' => [
                's:4:"hello "world";";',
                's:14:"hello "world";";'
            ],
            'various use case' => [
                <<<SER
                    a:4:{i:0;O:12:"Tests\MyEnum":2:{s:8:"\000*\000value";s:5:"hello";s:22:"\000MyCLabs\Enum\Enum\000key";s:5:"HELLO";}i:1;a:4:{s:4:"text";s:60:"SALUT les copain "ça" swingue ?
                    des "quotes"; ";;""";";"; ici";s:7:"integer";i:32;s:5:"float";d:74.000001;i:0;N;}i:2;s:2:"32";i:3;N;}
                    SER,
                <<<SER
                    a:4:{i:0;O:12:"Tests\MyEnum":2:{s:8:"\000*\000value";s:5:"hello";s:22:"\000MyCLabs\Enum\Enum\000key";s:5:"HELLO";}i:1;a:4:{s:4:"text";s:62:"SALUT les copain "ça" swingue ?
                    des "quotes"; ";;""";";"; ici";s:7:"integer";i:32;s:5:"float";d:74.000001;i:0;N;}i:2;s:2:"32";i:3;N;}
                    SER,
            ],
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
