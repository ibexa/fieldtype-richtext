<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType;

use DOMDocument;
use Exception;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as ApiInvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\Author\Type as AuthorType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as CoreValue;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\FieldTypeRichText\FieldType\RichText\Type;
use Ibexa\FieldTypeRichText\FieldType\RichText\Type as RichTextType;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Ibexa\FieldTypeRichText\RichText\ConverterDispatcher;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentLoader;
use Ibexa\FieldTypeRichText\RichText\InputHandler;
use Ibexa\FieldTypeRichText\RichText\Normalizer\Aggregate;
use Ibexa\FieldTypeRichText\RichText\RelationProcessor;
use Ibexa\FieldTypeRichText\RichText\Validator\Validator;
use Ibexa\FieldTypeRichText\RichText\Validator\ValidatorDispatcher;
use Ibexa\FieldTypeRichText\RichText\XMLSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(FieldType::class)]
#[CoversClass(Type::class)]
#[CoversClass(AuthorType::class)]
#[Group('fieldType')]
#[Group('ibexa_richtext')]
class RichTextTest extends TestCase
{
    protected function getFieldType(): Type
    {
        $inputHandler = new InputHandler(
            new DOMDocumentFactory(new XMLSanitizer()),
            new ConverterDispatcher([
                'http://docbook.org/ns/docbook' => null,
            ]),
            new Aggregate(),
            new ValidatorDispatcher([
                'http://docbook.org/ns/docbook' => null,
            ]),
            new Validator([
                $this->getAbsolutePath('src/bundle/Resources/richtext/schemas/docbook/ezpublish.rng'),
                $this->getAbsolutePath('src/bundle/Resources/richtext/schemas/docbook/docbook.iso.sch.xsl'),
            ]),
            new RelationProcessor()
        );

        $fieldType = new RichTextType($inputHandler, self::createStub(TextExtractorInterface::class), new DOMDocumentLoader());
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getTransformationProcessorMock(): TransformationProcessor&MockObject
    {
        return $this->getMockForAbstractClass(
            TransformationProcessor::class,
            [],
            '',
            false,
            true,
            true
        );
    }

    public function testValidatorConfigurationSchema(): void
    {
        $fieldType = $this->getFieldType();
        self::assertEmpty(
            $fieldType->getValidatorConfigurationSchema(),
            'The validator configuration schema does not match what is expected.'
        );
    }

    public function testSettingsSchema(): void
    {
        $fieldType = $this->getFieldType();
        self::assertSame(
            [],
            $fieldType->getSettingsSchema(),
            'The settings schema does not match what is expected.'
        );
    }

    public function testAcceptValueInvalidType(): void
    {
        $this->expectException(ApiInvalidArgumentException::class);

        $this->getFieldType()->acceptValue(self::createStub(CoreValue::class));
    }

    /**
     * @phpstan-return list<array{string}>
     */
    public static function providerForTestAcceptValueValidFormat(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
  <title>This is a heading.</title>
  <para>This is a paragraph.</para>
</section>
',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
  <h1>This is not valid, but acceptValue() will not validate it.</h1>
</section>',
            ],
        ];
    }

    #[DataProvider('providerForTestAcceptValueValidFormat')]
    public function testAcceptValueValidFormat(string $input): void
    {
        $fieldType = $this->getFieldType();
        $fieldType->acceptValue($input);
    }

    /**
     * @phpstan-return list<array{string, \Exception}>
     */
    public static function providerForTestAcceptValueInvalidFormat(): array
    {
        return [
            [
                'This is not XML at all!',
                new InvalidArgumentException(
                    '$xmlString',
                    "Start tag expected, '<' not found"
                ),
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?><unknown xmlns="http://www.w3.org/2013/foobar"><format /></unknown>',
                new NotFoundException(
                    'Validator',
                    'http://www.w3.org/2013/foobar'
                ),
            ],
        ];
    }

    #[DataProvider('providerForTestAcceptValueInvalidFormat')]
    public function testAcceptValueInvalidFormat(string $input, Exception $expectedException): void
    {
        try {
            $fieldType = $this->getFieldType();
            $fieldType->acceptValue($input);
            self::fail('An InvalidArgumentException was expected! None thrown.');
        } catch (InvalidArgumentException $e) {
            self::assertEquals($expectedException->getMessage(), $e->getMessage());
        } catch (NotFoundException $e) {
            self::assertEquals($expectedException->getMessage(), $e->getMessage());
        } catch (Exception $e) {
            self::fail(
                'Unexpected exception thrown! ' . get_class($e) . ' thrown with message: ' . $e->getMessage()
            );
        }
    }

    /**
     * @phpstan-return list<array{string, \Ibexa\Contracts\Core\FieldType\ValidationError[]}>
     */
    public static function providerForTestValidate(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
  <h1>This is a heading.</h1>
</section>',
                [
                    new ValidationError(
                        "Validation of XML content failed:\n" .
                        'Error in 3:0: Element section has extra content: h1',
                        null,
                        [],
                        'xml'
                    ),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <title>This is a heading.</title>
</section>',
                [
                    new ValidationError(
                        "Validation of XML content failed:\n" .
                        "/*[local-name()='section' and namespace-uri()='http://docbook.org/ns/docbook']: The root element must have a version attribute.",
                        null,
                        [],
                        'xml'
                    ),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <para><link xlink:href="javascript:alert(\'XSS\');">link</link></para>
</section>',
                [
                    new ValidationError(
                        "Validation of XML content failed:\n" .
                        '/section/para/link: links must start with one of: http://, https://, mailto:, tel:, ezcontent://, ezlocation://, ezremote://, ezurl://, /, #',
                        null,
                        [],
                        'xml'
                    ),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
  <para><link xlink:href="jAvAsCriPt:alert(\'XSS\');">link</link></para>
</section>',
                [
                    new ValidationError(
                        "Validation of XML content failed:\n" .
                        '/section/para/link: links must start with one of: http://, https://, mailto:, tel:, ezcontent://, ezlocation://, ezremote://, ezurl://, /, #',
                        null,
                        [],
                        'xml'
                    ),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
  <para><link xlink:href="https://example.com/foo&lt;bar">link</link></para>
</section>',
                [
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <para><link xlink:href="vbscript:alert(\'XSS\');">link</link></para>
</section>',
                [
                    new ValidationError(
                        "Validation of XML content failed:\n" .
                        '/section/para/link: links must start with one of: http://, https://, mailto:, tel:, ezcontent://, ezlocation://, ezremote://, ezurl://, /, #',
                        null,
                        [],
                        'xml'
                    ),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <para><link xlink:href="http://example.org">link</link></para>
</section>',
                [],
            ],
        ];
    }

    #[DataProvider('providerForTestValidate')]
    public function testValidate(string $xmlString, array $expectedValidationErrors): void
    {
        $fieldType = $this->getFieldType();
        $value = new Value($this->createDocument($xmlString));

        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition|\PHPUnit\Framework\MockObject\MockObject $fieldDefinitionMock */
        $fieldDefinitionMock = self::createStub(APIFieldDefinition::class);

        $validationErrors = $fieldType->validate($fieldDefinitionMock, $value);

        self::assertEquals($expectedValidationErrors, $validationErrors);
    }

    public function testToPersistenceValue(): void
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0">
  <title>This is a heading.</title>
  <para>This is a paragraph.</para>
</section>
';

        $fieldType = $this->getFieldType();
        $fieldValue = $fieldType->toPersistenceValue($fieldType->acceptValue($xmlString));

        self::assertIsString($fieldValue->data);
        self::assertSame($xmlString, $fieldValue->data);
    }

    public function testFromPersistenceValueWithInvalidXmlIdDoesNotEmitWarning(): void
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para xml:id="227">Lorem ipsum</para></section>';

        $value = $this->getFieldType()->fromPersistenceValue(new FieldValue(['data' => $xmlString]));

        self::assertNotNull($value->xml->documentElement);
        self::assertStringContainsString('xml:id="227"', (string)$value);
    }

    public function testFromPersistenceValueWithNullData(): void
    {
        $value = $this->getFieldType()->fromPersistenceValue(new FieldValue(['data' => null]));

        self::assertSame(Value::EMPTY_VALUE, trim((string)$value));
    }

    private function createDocument(string $xmlString): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML($xmlString);

        return $document;
    }

    #[DataProvider('providerForTestGetName')]
    public function testGetName(string $xmlString, string $expectedName): void
    {
        $value = new Value($this->createDocument($xmlString));

        $fieldType = $this->getFieldType();
        self::assertEquals(
            $expectedName,
            $fieldType->getName($value, self::createStub(APIFieldDefinition::class), 'eng-US')
        );
    }

    /**
     * @todo format does not really matter for the method tested, but the fixtures here should be replaced
     * by valid docbook anyway
     *
     * @phpstan-return list<array{string, string}>
     */
    public static function providerForTestGetName(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><header level="1">This is a piece of text</header></section>',
                'This is a piece of text',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><header level="1">This is a piece of <emphasize>text</emphasize></header></section>',
                /* @todo FIXME: should probably be "This is a piece of text" */
                'This is a piece of',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><header level="1"><strong>This is a piece</strong> of text</header></section>',
                /* @todo FIXME: should probably be "This is a piece of text" */
                'This is a piece',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><header level="1"><strong><emphasize>This is</emphasize> a piece</strong> of text</header></section>',
                /* @todo FIXME: should probably be "This is a piece of text" */
                'This is',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><paragraph><table class="default" border="0" width="100%" custom:summary="wai" custom:caption=""><tr><td><paragraph>First cell</paragraph></td><td><paragraph>Second cell</paragraph></td></tr><tr><td><paragraph>Third cell</paragraph></td><td><paragraph>Fourth cell</paragraph></td></tr></table></paragraph><paragraph>Text after table</paragraph></section>',
                /* @todo FIXME: should probably be "First cell" */
                'First cellSecond cell',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><paragraph xmlns:tmp="http://ibexa.co/namespaces/ezpublish3/temporary/"><ul><li><paragraph xmlns:tmp="http://ibexa.co/namespaces/ezpublish3/temporary/">List item</paragraph></li></ul></paragraph></section>',
                'List item',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><paragraph xmlns:tmp="http://ibexa.co/namespaces/ezpublish3/temporary/"><ul><li><paragraph xmlns:tmp="http://ibexa.co/namespaces/ezpublish3/temporary/">List <emphasize>item</emphasize></paragraph></li></ul></paragraph></section>',
                'List item',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/" />',
                '',
            ],

            [
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/"><paragraph><strong><emphasize>A simple</emphasize></strong> paragraph!</paragraph></section>',
                'A simple',
            ],

            ['<section><paragraph>test</paragraph></section>', 'test'],

            ['<section><paragraph><link node_id="1">test</link><link object_id="1">test</link></paragraph></section>', 'test'],
        ];
    }

    /**
     * @todo handle embeds when implemented
     */
    public function testGetRelations(): void
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para><link xlink:href="ezlocation://72">link1</link></para>
    <para><link xlink:href="ezlocation://61">link2</link></para>
    <para><link xlink:href="ezlocation://61">link3</link></para>
    <para><link xlink:href="ezcontent://70">link4</link></para>
    <para><link xlink:href="ezcontent://75">link5</link></para>
    <para><link xlink:href="ezcontent://75">link6</link></para>
</section>
EOT;

        $fieldType = $this->getFieldType();
        self::assertEquals(
            [
                RelationType::LINK->value => [
                    'locationIds' => [72, 61],
                    'contentIds' => [70, 75],
                ],
                RelationType::EMBED->value => [
                    'locationIds' => [],
                    'contentIds' => [],
                ],
            ],
            $fieldType->getRelations($fieldType->acceptValue($xml))
        );
    }

    protected function getAbsolutePath(string $relativePath): string
    {
        $absolutePath = realpath(__DIR__ . '/../../../' . $relativePath);
        if (false === $absolutePath) {
            throw new RuntimeException("Unable to determine the absolute path for '{$relativePath}'");
        }

        return $absolutePath;
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_richtext';
    }
}
