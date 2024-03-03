<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\YamlSymfony;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

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
  public function testEncodeDecode($data) {
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
  public function testDecode($string, $data) {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->assertEquals($data, YamlSymfony::decode($string));
  }

  /**
   * Tests our encode settings.
   *
   * @covers ::encode
   */
  public function testEncode() {
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
  public function testGetFileExtension() {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::getFileExtension() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::getFileExtension() instead. See https://www.drupal.org/node/3415489");
    $this->assertEquals('yml', YamlSymfony::getFileExtension());
  }

  /**
   * Tests that invalid YAML throws an exception.
   *
   * @covers ::decode
   */
  public function testError() {
    $this->expectDeprecation("Calling Drupal\Component\Serialization\YamlSymfony::decode() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Component\Serialization\Yaml::decode() instead. See https://www.drupal.org/node/3415489");
    $this->expectException(InvalidDataTypeException::class);
    YamlSymfony::decode('foo: [ads');
  }

  /**
   * Ensures that php object support is disabled.
   *
   * @covers ::encode
   */
  public function testEncodeObjectSupportDisabled() {
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

}
