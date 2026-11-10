<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('acceptedFormats')]
    public function test_it_normalizes_the_supported_input_formats(string $input): void
    {
        $this->assertSame('+2250712345678', PhoneNumber::normalize($input));
    }

    /** @return array<string, array{string}> */
    public static function acceptedFormats(): array
    {
        return [
            'local' => ['0712345678'],
            'spaces' => ['07 12 34 56 78'],
            'country code with plus' => ['+225 07 12 34 56 78'],
            'international prefix' => ['00225 07 12 34 56 78'],
            'country code without plus' => ['2250712345678'],
            'dots' => ['07.12.34.56.78'],
            'dashes' => ['07-12-34-56-78'],
            'parentheses' => ['(07) 12 34 56 78'],
            'slashes' => ['07/12/34/56/78'],
            'non-breaking spaces' => ["07\u{00A0}12\u{202F}34 56 78"],
            'surrounding whitespace' => ['  0712345678  '],
        ];
    }

    #[DataProvider('mobilePrefixes')]
    public function test_it_accepts_every_ivorian_mobile_prefix(string $prefix): void
    {
        $this->assertSame('+225'.$prefix.'12345678', PhoneNumber::normalize($prefix.'12345678'));
    }

    /** @return array<string, array{string}> */
    public static function mobilePrefixes(): array
    {
        return ['01' => ['01'], '05' => ['05'], '07' => ['07']];
    }

    #[DataProvider('rejectedFormats')]
    public function test_it_rejects_invalid_numbers(string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
    }

    /** @return array<string, array{string}> */
    public static function rejectedFormats(): array
    {
        return [
            'other country' => ['+226 07 12 34 56 78'],
            'landline prefix' => ['0212345678'],
            'nine digits' => ['071234567'],
            'eleven digits' => ['07123456789'],
            'letters' => ['07123456a8'],
            'words' => ['inconnu'],
            'too many plus signs' => ['+225 +225 07 12 34 56 78'],
            'plus in the middle' => ['0712+345678'],
            'country code only' => ['+225'],
            'blank' => ['   '],
        ];
    }

    public function test_it_rejects_null_and_empty_values(): void
    {
        $this->assertNull(PhoneNumber::normalize(null));
        $this->assertNull(PhoneNumber::normalize(''));
    }

    public function test_it_masks_a_canonical_number(): void
    {
        $this->assertSame('+225 07 ** ** 78', PhoneNumber::mask('+2250712345678'));
    }

    public function test_it_masks_a_missing_or_invalid_number(): void
    {
        $this->assertSame('+225 ** ** ** **', PhoneNumber::mask(null));
        $this->assertSame('+225 ** ** ** **', PhoneNumber::mask('0712345678'));
    }

    public function test_it_extracts_the_local_number_from_the_canonical_value(): void
    {
        $this->assertSame('0712345678', PhoneNumber::local('+2250712345678'));
    }

    public function test_it_returns_no_local_number_for_other_values(): void
    {
        $this->assertNull(PhoneNumber::local(null));
        $this->assertNull(PhoneNumber::local('0712345678'));
        $this->assertNull(PhoneNumber::local('+2260712345678'));
    }
}
