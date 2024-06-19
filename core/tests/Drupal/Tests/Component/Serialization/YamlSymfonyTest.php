<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\YamlSymfony;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Tests the YamlSymfony serialization implementation.
 *
 * @group Drupal
 * @group Serialization
 * @group legacy
 * @coversDefaultClass \Drupal\Component\Serialization\YamlSymfony
 */
class YamlSymfonyTest extends YamlTestBase {
  use ExpectDeprecationTrait;

  /**
   * Tests encoding and decoding basic data structures.
   *
   * @covers ::encode
   * @covers ::decode
   * @dataProvider providerEncodeDecodeTests
   */
  public function testEncodeDecode($data): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::encode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::encode() instead. See https://www.drupal.org/node/3415489");
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->assertEquals($data, YamlSymfony::decode(YamlSymfony::encode($data)));
  }

  /**
   * Tests decoding YAML node anchors.
   *
   * @covers ::decode
   * @dataProvider providerDecodeTests
   */
  public function testDecode($string, $data): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->assertEquals($data, YamlSymfony::decode($string));
  }

  /**
   * Tests our encode settings.
   *
   * @covers ::encode
   */
  public function testEncode(): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::encode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::encode() instead. See https://www.drupal.org/node/3415489");
    // cSpell:disable
    $this->assertEquals('foo:
  bar: \'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis\'
', YamlSymfony::encode(['foo' => ['bar' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis']]));
    // cSpell:enable
  }

  /**
   * @covers ::getFileExtension
   */
  public function testGetFileExtension(): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::getFileExtension() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::getFileExtension() instead. See https://www.drupal.org/node/3415489");
    $this->assertEquals('yml', YamlSymfony::getFileExtension());
  }

  /**
   * Tests that invalid YAML throws an exception.
   *
   * @covers ::decode
   */
  public function testError(): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->expectException(InvalidDataTypeException::class);
    YamlSymfony::decode('foo: [ads');
  }

  /**
   * Ensures that php object support is disabled.
   *
   * @covers ::encode
   */
  public function testEncodeObjectSupportDisabled(): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::encode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::encode() instead. See https://www.drupal.org/node/3415489");
    $this->expectException(InvalidDataTypeException::class);
    $this->expectExceptionMessage('Object support when dumping a YAML file has been disabled.');
    $object = new \stdClass();
    $object->foo = 'bar';
    YamlSymfony::encode([$object]);
  }

  /**
   * Ensures that decoding PHP objects does not work in Symfony.
   *
   * @covers ::decode
   */
  public function testDecodeObjectSupportDisabled(): void {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->expectException(InvalidDataTypeException::class);
    $this->expectExceptionMessageMatches('/^Object support when parsing a YAML file has been disabled/');
    $yaml = <<<YAML
    obj: !php/object "O:8:\"stdClass\":1:{s:3:\"foo\";s:3:\"bar\";}"
    YAML;

    YamlSymfony::decode($yaml);
  }

  /**
   * Tests that YAML custom tags are supported and parsed.
   *
   * @covers ::decode
   *
   * @dataProvider taggedValuesProvider
   */
  public function testCustomTagSupport($expected, $yaml): void {
    try {
      $this->assertEquals($expected, YamlSymfony::decode($yaml));
    }
    catch (InvalidDataTypeException $e) {
      $message = 'Custom tag support is not enabled. Enable the `Yaml::PARSE_CUSTOM_TAGS` flag to prevent the %s exception.';
      $this->fail(sprintf($message, InvalidDataTypeException::class));
    }
  }

  /**
   * Data provider for testCustomTagSupport().
   *
   * @return array
   *   A list of test data.
   */
  public function taggedValuesProvider() {
    return [
      'sequences' => [
        [
          new TaggedValue('foo', ['yaml']),
          new TaggedValue('quz', ['bar']),
        ],
        <<<YAML
- !foo
    - yaml
- !quz [bar]
YAML
      ],
      'mappings' => [
        new TaggedValue('foo', [
          'foo' => new TaggedValue('quz', ['bar']),
          'quz' => new TaggedValue('foo', ['quz' => 'bar']),
        ]),
        <<<YAML
!foo
foo: !quz [bar]
quz: !foo
   quz: bar
YAML
      ],
      'inline' => [
        [
          new TaggedValue('foo', [
            'foo',
            'bar',
          ]),
          new TaggedValue('quz',
            [
              'foo' => 'bar',
              'quz' => new TaggedValue('bar', ['one' => 'bar']),
            ]),
        ],
        <<<YAML
- !foo [foo, bar]
- !quz {foo: bar, quz: !bar {one: bar}}
YAML
      ],
    ];
  }

}
