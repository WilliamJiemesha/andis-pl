<?php

namespace Tests\Feature;

use App\Support\PriceCode;
use Tests\TestCase;

class PriceCodeTest extends TestCase
{
    public function test_price_code_decodes_custom_letter_format(): void
    {
        $this->assertSame(95000, PriceCode::decode('NRPK'));
        $this->assertSame(9558, PriceCode::decode('NRRA'));
    }

    public function test_price_code_encodes_custom_letter_format(): void
    {
        $this->assertSame('NRPK', PriceCode::encode(95000));
        $this->assertSame('NRRA', PriceCode::encode(9558));
    }
}
