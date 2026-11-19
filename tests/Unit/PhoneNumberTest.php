<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('valid')]
    public function test_common_formats_are_normalised(string $input): void
    {
        $this->assertSame('01712345678', PhoneNumber::normalize($input));
    }

    public static function valid(): array
    {
        return [['01712345678'], ['+8801712345678'], ['8801712345678'], ['01712-345678'], ['0171 234 5678'], [' 01712345678 '], ['+880 1712-345678']];
    }

    #[DataProvider('invalid')]
    public function test_invalid_numbers_are_rejected(string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
    }

    public static function invalid(): array
    {
        return [
            'too short' => ['0171234567'],
            'too long' => ['017123456789'],
            'operator digit 2' => ['01212345678'],
            'landline' => ['029123456'],
            'letters' => ['01712abc678'],
            'empty' => [''],
            'other country' => ['+441712345678'],
        ];
    }

    public function test_tells_phone_numbers_apart_from_emails(): void
    {
        $this->assertTrue(PhoneNumber::looksLikePhone('+880 1712-345678'));
        $this->assertFalse(PhoneNumber::looksLikePhone('user@example.com'));
        $this->assertFalse(PhoneNumber::looksLikePhone('napa500'));
    }
}
