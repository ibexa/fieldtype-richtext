<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\Command;

use Ibexa\Bundle\FieldTypeRichText\Command\MigrateNamespacesCommand;
use PHPUnit\Framework\TestCase;

class MigrateNamespacesCommandTest extends TestCase
{
    public function providerMigrateNamespaces()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="custom_class">
        This is some text in custom_class with
        <emphasis>something in bold</emphasis>
        and the end.
    </para>
</section>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="custom_class">
        This is some text in custom_class with
        <emphasis>something in bold</emphasis>
        and the end.
    </para>
</section>
',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="custom_class">
        This is some text
    </para>
    <ezembed xlink:href="ezcontent://78" view="embed" ezxhtml:class="ez-embed-type-image">
      <ezconfig>
         <ezvalue key="size">medium</ezvalue>
      </ezconfig>
   </ezembed>
</section>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="custom_class">
        This is some text
    </para>
    <ezembed xlink:href="ezcontent://78" view="embed" ezxhtml:class="ibexa-embed-type-image">
      <ezconfig>
         <ezvalue key="size">medium</ezvalue>
      </ezconfig>
   </ezembed>
</section>
',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ez="http://ez.no/xmlns/ezpublish/docbook" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ez="http://ibexa.co/xmlns/ezpublish/docbook" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:a="http://ez.no/xmlns/annotation" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:a="http://ibexa.co/xmlns/annotation" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:m="http://ez.no/xmlns/module" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:m="http://ibexa.co/xmlns/module" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <para>
        This is some text
    </para>
</section>
',
            ],
        ];
    }

    /**
     * @dataProvider providerMigrateNamespaces
     */
    public function testMigrateNamespaces(string $xmlTextSource, string $xmlTestExpected): void
    {
        $migratedXml = MigrateNamespacesCommand::migrateNamespaces($xmlTextSource);
        self::assertEquals($xmlTestExpected, $migratedXml, 'Docbook with ez.no namespaces was not converted correctly');
    }
}
