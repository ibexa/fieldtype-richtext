<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RichText extends Constraint
{
    public $message = 'Invalid value';
}

class_alias(RichText::class, 'EzSystems\EzPlatformRichText\Validator\Constraints\RichText');
