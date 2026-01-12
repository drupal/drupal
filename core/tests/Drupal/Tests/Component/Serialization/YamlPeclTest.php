<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\YamlPecl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Tests the YamlPecl serialization implementation.
 */
#[CoversClass(YamlPecl::class)]
#[Group('Drupal')]
#[Group('Serialization')]
#[RequiresPhpExtension('yaml')]
class YamlPeclTest extends YamlTestBase {

  /**
   * Tests encoding and decoding basic data structures.
   *
   * @legacy-covers ::encode
   * @legacy-covers ::decode
   */
  #[DataProvider('providerEncodeDecodeTests')]
  public function testEncodeDecode(array $data): void {
    $this->assertEquals($data, YamlPecl::decode(YamlPecl::encode($data)));
  }

  /**
   * Ensures that php object support is disabled.
   */
  public function testObjectSupportDisabled(): void {
    $object = new \stdClass();
    $object->foo = 'bar';
    $this->assertEquals(['O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}'], YamlPecl::decode(YamlPecl::encode([$object])));
    $this->assertEquals(0, ini_get('yaml.decode_php'));
  }

  /**
   * Tests decoding YAML node anchors.
   */
  #[DataProvider('providerDecodeTests')]
  public function testDecode($string, $data): void {
    $this->assertEquals($data, YamlPecl::decode($string));
  }

  /**
   * Tests our encode settings.
   */
  public function testEncode(): void {
    // cSpell:disable
    $this->assertEquals('---
foo:
  bar: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis
...
', YamlPecl::encode(['foo' => ['bar' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis']]));
    // cSpell:enable
  }

  /**
   * Tests YAML boolean callback.
   *
   * @param string $string
   *   String value for the YAML boolean.
   * @param string|bool $expected
   *   The expected return value.
   */
  #[DataProvider('providerBoolTest')]
  public function testApplyBooleanCallbacks($string, $expected): void {
    $this->assertEquals($expected, YamlPecl::applyBooleanCallbacks($string, 'bool', NULL));
  }

  /**
   * Tests get file extension.
   */
  public function testGetFileExtension(): void {
    $this->assertEquals('yml', YamlPecl::getFileExtension());
  }

  /**
   * Tests that invalid YAML throws an exception.
   *
   * @legacy-covers ::errorHandler
   */
  public function testError(): void {
    $this->expectException(InvalidDataTypeException::class);
    YamlPecl::decode('foo: [ads');
  }

}
