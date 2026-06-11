<?php

namespace App\Tests\Unit\Util;

use App\Util\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('e164Provider')]
    public function testToE164(?string $input, ?string $expected): void
    {
        self::assertSame($expected, PhoneNormalizer::toE164($input));
    }

    /** @return iterable<string, array{0: ?string, 1: ?string}> */
    public static function e164Provider(): iterable
    {
        yield 'empty' => [null, null];
        yield 'russian mobile with 8' => ['8 912 295-22-92', '+79122952292'];
        yield 'russian mobile with +7' => ['+7 982 650-13-86', '+79826501386'];
        yield 'ten digits starting with 9' => ['9161234567', '+79161234567'];
        yield 'invalid length' => ['12345', null];
    }

    #[DataProvider('digitsProvider')]
    public function testToDigits(?string $input, string $expected): void
    {
        self::assertSame($expected, PhoneNormalizer::toDigits($input));
    }

    /** @return iterable<string, array{0: ?string, 1: string}> */
    public static function digitsProvider(): iterable
    {
        yield 'empty' => [null, ''];
        yield 'with 8 prefix' => ['8 912 295-22-92', '79122952292'];
        yield 'ten digits' => ['9161234567', '79161234567'];
    }
}
