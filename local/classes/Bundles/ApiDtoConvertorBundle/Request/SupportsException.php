<?php

declare(strict_types=1);

namespace Local\Bundles\ApiDtoConvertorBundle\Request;

use LogicException;

/**
 * Class SupportsException
 * @package Local\Bundles\ApiDtoConvertorBundle\Request
 */
class SupportsException extends LogicException
{
    public static function covered(): self
    {
        return new self('This should have been covered by self::supports(). This is a bug, please report.');
    }
}