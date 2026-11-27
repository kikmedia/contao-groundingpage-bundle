<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Util;

use Contao\CoreBundle\Migration\Version600\OutputEncodingMigration;

final class PlainText
{
    public static function isInputEncoded(): bool
    {
        return !class_exists(OutputEncodingMigration::class);
    }

    public static function decode(mixed $value, bool $inputEncoded): string
    {
        $value = (string) $value;

        if (!$inputEncoded) {
            return $value;
        }

        $value = str_replace(['[&]', '[lt]', '[gt]', '[nbsp]', '[-]'], ['&', '<', '>', '&nbsp;', '&shy;'], $value);

        return html_entity_decode($value, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
    }
}
