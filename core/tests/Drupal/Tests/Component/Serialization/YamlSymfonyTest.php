<?php

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\YamlSymfony;

/**
 * Tests the YamlSymfony serialization implementation.
 *
 * @group Drupal
 * @group Serialization
 * @coversDefaultClass \Drupal\Component\Serialization\YamlSymfony
 */
class YamlSymfonyTest extends YamlTestBase {

  /**
   * Tests encoding and decoding basic data structures.
   *
   * @covers ::encode
   * @covers ::decode
   * @dataProvider providerEncodeDecodeTests
   */
  public function testEncodeDecode($data) {
    $this->assertEquals($data, YamlSymfony::decode(YamlSymfony::encode($data)));
  }

  /**
   * Tests decoding YAML node anchors.
   *
   * @covers ::decode
   * @dataProvider providerDecodeTests
   */
  public function testDecode($string, $data) {
    $this->assertEquals($data, YamlSymfony::decode($string));
  }

  /**
   * Tests our encode settings.
   *
   * @covers ::encode
   */
  public function testEncode() {
    $this->assertEquals('foo:
  bar: \'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis\'
', YamlSymfony::encode(['foo' => ['bar' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sapien ex, venenatis vitae nisi eu, posuere luctus dolor. Nullam convallis']]));
  }

  /**
   * @covers ::getFileExtension
   */
  public function testGetFileExtension() {
    $this->assertEquals('yml', YamlSymfony::getFileExtension());
  }

  /**
   * Tests that invalid YAML throws an exception.
   *
   * @covers ::decode
   */
  public function testError() {
    $this->setExpectedException(InvalidDataTypeException::class);
    YamlSymfony::decode('foo: [ads');
  }

  /**
   * Ensures that php object support is disabled.
   *
   * @covers ::encode
   */
  public function testObjectSupportDisabled() {
    $this->setExpectedException(InvalidDataTypeException::class, 'Object support when dumping a YAML file has been disabled.');
    $object = new \stdClass();
    $object->foo = 'bar';
    YamlSymfony::encode([$object]);
  }

}
