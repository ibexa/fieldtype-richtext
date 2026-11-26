<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway as LanguageGateway;

return [
    LanguageGateway::CONTENT_LANGUAGE_TABLE => [
        ['disabled' => 0, 'id' => 2, 'locale' => 'eng-US', 'name' => 'English (American)'],
    ],
    Gateway::CONTENT_ITEM_TABLE => [
        0 => [
            'content_type_id' => '3',
            'current_version' => '1',
            'id' => '4',
            'initial_language_id' => '2',
            'language_mask' => '3',
            'modified' => '1033917596',
            'name' => 'Users',
            'owner_id' => '14',
            'published' => '1033917596',
            'remote_id' => 'f5c88a2209584891056f987fd965b0ba',
            'section_id' => '2',
            'status' => '1',
        ],
        1 => [
            'content_type_id' => '4',
            'current_version' => '2',
            'id' => '10',
            'initial_language_id' => '2',
            'language_mask' => '3',
            'modified' => '1072180405',
            'name' => 'Anonymous User',
            'owner_id' => '14',
            'published' => '1033920665',
            'remote_id' => 'faaeb9be3bd98ed09f606fc16d144eca',
            'section_id' => '2',
            'status' => '1',
        ],
    ],
];
