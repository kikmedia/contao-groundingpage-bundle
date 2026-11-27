<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests\Util;

use Kikmedia\GroundingPageBundle\Util\PlainText;
use PHPUnit\Framework\TestCase;

class PlainTextTest extends TestCase
{
    public function testDecodesTheEntitiesThatContao5Stores(): void
    {
        self::assertSame(
            'A & B <b>"x"</b> (=)',
            PlainText::decode('A &#38; B &#60;b&#62;&#34;x&#34;&#60;/b&#62; &#40;&#61;&#41;', true)
        );
    }

    public function testRestoresContaoBasicEntities(): void
    {
        self::assertSame("A & <B>\u{A0}C", PlainText::decode('A [&] [lt]B[gt][nbsp]C', true));
    }

    public function testKeepsRawValuesAsContao6StoresThem(): void
    {
        self::assertSame('Write &amp; [lt] <b>', PlainText::decode('Write &amp; [lt] <b>', false));
    }

    public function testTreatsMissingValuesAsEmptyText(): void
    {
        self::assertSame('', PlainText::decode(null, true));
    }
}
