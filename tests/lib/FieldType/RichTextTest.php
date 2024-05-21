<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as ApiInvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as CoreValue;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\FieldTypeRichText\FieldType\RichText\Type as RichTextType;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Ibexa\FieldTypeRichText\RichText\ConverterDispatcher;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\InputHandler;
use Ibexa\FieldTypeRichText\RichText\Normalizer\Aggregate;
use Ibexa\FieldTypeRichText\RichText\RelationProcessor;
use Ibexa\FieldTypeRichText\RichText\Validator\Validator;
use Ibexa\FieldTypeRichText\RichText\Validator\ValidatorDispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @group fieldType
 * @group ezrichtext
 */
class RichTextTest extends TestCase
{
    /**
     * @return \Ibexa\FieldTypeRichText\FieldType\RichText\Type
     */
    protected function getFieldType()
    {
        $inputHandler = new InputHandler(
            new DOMDocumentFactory(),
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

        $textExtractor = $this->createMock(TextExtractorInterface::class);

        $fieldType = new RichTextType($inputHandler, $textExtractor);
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTransformationProcessorMock()
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

    /**
     * @covers \Ibexa\Core\FieldType\FieldType::getValidatorConfigurationSchema
     */
    public function testValidatorConfigurationSchema()
    {
        $fieldType = $this->getFieldType();
        self::assertEmpty(
            $fieldType->getValidatorConfigurationSchema(),
            'The validator configuration schema does not match what is expected.'
        );
    }

    /**
     * @covers \Ibexa\Core\FieldType\FieldType::getSettingsSchema
     */
    public function testSettingsSchema()
    {
        $fieldType = $this->getFieldType();
        self::assertSame(
            [],
            $fieldType->getSettingsSchema(),
            'The settings schema does not match what is expected.'
        );
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Type::acceptValue
     */
    public function testAcceptValueInvalidType()
    {
        $this->expectException(ApiInvalidArgumentException::class);

        $this->getFieldType()->acceptValue($this->createMock(CoreValue::class));
    }

    public static function providerForTestAcceptValueValidFormat()
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

    /**
     * @covers \Ibexa\Core\FieldType\Author\Type::acceptValue
     *
     * @dataProvider providerForTestAcceptValueValidFormat
     */
    public function testAcceptValueValidFormat($input)
    {
        $fieldType = $this->getFieldType();
        $fieldType->acceptValue($input);
    }

    public static function providerForTestAcceptValueInvalidFormat()
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

    /**
     * @covers \Ibexa\Core\FieldType\Author\Type::acceptValue
     *
     * @dataProvider providerForTestAcceptValueInvalidFormat
     */
    public function testAcceptValueInvalidFormat($input, Exception $expectedException)
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

    public function providerForTestValidate()
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
                        '/section/para/link: using scripts in links is not allowed',
                        null,
                        [],
                        'xml'
                    ),
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
                        '/section/para/link: using scripts in links is not allowed',
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

    /**
     * @dataProvider providerForTestValidate
     *
     * @param string $xmlString
     * @param array $expectedValidationErrors
     */
    public function testValidate($xmlString, array $expectedValidationErrors)
    {
        $fieldType = $this->getFieldType();
        $value = new Value($xmlString);

        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition|\PHPUnit\Framework\MockObject\MockObject $fieldDefinitionMock */
        $fieldDefinitionMock = $this->createMock(APIFieldDefinition::class);

        $validationErrors = $fieldType->validate($fieldDefinitionMock, $value);

        self::assertEquals($expectedValidationErrors, $validationErrors);
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Type::toPersistenceValue
     */
    public function testToPersistenceValue()
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

    /**
     * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Type::getName
     *
     * @dataProvider providerForTestGetName
     */
    public function testGetName($xmlString, $expectedName)
    {
        $value = new Value($xmlString);

        $fieldType = $this->getFieldType();
        self::assertEquals(
            $expectedName,
            $fieldType->getName($value, $this->createMock(APIFieldDefinition::class), 'eng-US')
        );
    }

    /**
     * @todo format does not really matter for the method tested, but the fixtures here should be replaced
     * by valid docbook anyway
     */
    public static function providerForTestGetName()
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
     *
     * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Type::getRelations
     */
    public function testGetRelations()
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
                Relation::LINK => [
                    'locationIds' => [72, 61],
                    'contentIds' => [70, 75],
                ],
                Relation::EMBED => [
                    'locationIds' => [],
                    'contentIds' => [],
                ],
            ],
            $fieldType->getRelations($fieldType->acceptValue($xml))
        );
    }

    /**
     * @param string $relativePath
     *
     * @return string
     */
    protected function getAbsolutePath($relativePath)
    {
        $absolutePath = realpath(__DIR__ . '/../../../' . $relativePath);
        if (false === $absolutePath) {
            throw new RuntimeException("Unable to determine the absolute path for '{$relativePath}'");
        }

        return $absolutePath;
    }

    protected function provideFieldTypeIdentifier()
    {
        return 'ezrichtext';
    }

    public function provideDataForGetName()
    {
        return [];
    }
}
