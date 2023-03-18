<?php

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\YamlPecl;
use Drupal\Component\Serialization\YamlSymfony;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Serialization\Yaml
 * @group Serialization
 */
class YamlTest extends TestCase {

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockParser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->mockParser = $this->getMockBuilder('\stdClass')
      ->addMethods(['encode', 'decode', 'getFileExtension'])
      ->getMock();
    YamlParserProxy::setMock($this->mockParser);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    YamlParserProxy::setMock(NULL);
    parent::tearDown();
  }

  /**
   * @covers ::decode
   */
  public function testDecode() {
    $this->mockParser
      ->expects($this->once())
      ->method('decode');
    YamlStub::decode('test');
  }

  /**
   * @covers ::getFileExtension
   */
  public function testGetFileExtension() {
    $this->mockParser
      ->expects($this->never())
      ->method('getFileExtension');
    $this->assertEquals('yml', YamlStub::getFileExtension());
  }

  /**
   * Tests all YAML files are decoded in the same way with Symfony and PECL.
   *
   * This test is a little bit slow but it tests that we do not have any bugs in
   * our YAML that might not be decoded correctly in any of our implementations.
   *
   * @todo This should exist as an integration test not part of our unit tests.
   *   https://www.drupal.org/node/2597730
   *
   * @requires extension yaml
   * @dataProvider providerYamlFilesInCore
   */
  public function testYamlFiles($file) {
    $data = file_get_contents($file);
    try {
      $this->assertEquals(YamlSymfony::decode($data), YamlPecl::decode($data), $file);
    }
    catch (InvalidDataTypeException $e) {
      // Provide file context to the failure so the exception message is useful.
      $this->fail("Exception thrown parsing $file:\n" . $e->getMessage());
    }
  }

  /**
   * Ensures that decoding php objects does not work in PECL.
   *
   * @requires extension yaml
   *
   * @see \Drupal\Tests\Component\Serialization\YamlTest::testObjectSupportDisabledSymfony()
   */
  public function testObjectSupportDisabledPecl() {
    $object = new \stdClass();
    $object->foo = 'bar';
    // In core all Yaml encoding is done via Symfony and it does not support
    // objects so in order to encode an object we have to use the PECL
    // extension.
    // @see \Drupal\Component\Serialization\Yaml::encode()
    $yaml = YamlPecl::encode([$object]);
    $this->assertEquals(['O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}'], YamlPecl::decode($yaml));
  }

  /**
   * Ensures that decoding php objects does not work in Symfony.
   *
   * @requires extension yaml
   *
   * @see \Drupal\Tests\Component\Serialization\YamlTest::testObjectSupportDisabledPecl()
   */
  public function testObjectSupportDisabledSymfony() {
    $this->expectException(InvalidDataTypeException::class);
    $this->expectExceptionMessageMatches('/^Object support when parsing a YAML file has been disabled/');
    $object = new \stdClass();
    $object->foo = 'bar';
    // In core all Yaml encoding is done via Symfony and it does not support
    // objects so in order to encode an object we have to use the PECL
    // extension.
    // @see \Drupal\Component\Serialization\Yaml::encode()
    $yaml = YamlPecl::encode([$object]);
    YamlSymfony::decode($yaml);
  }

  /**
   * Data provider that lists all YAML files in core.
   */
  public function providerYamlFilesInCore() {
    $files = [];
    $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/../../../../../', \RecursiveDirectoryIterator::FOLLOW_SYMLINKS));
    foreach ($dirs as $dir) {
      $pathname = $dir->getPathname();
      // Exclude core/node_modules.
      if ($dir->getExtension() == 'yml' && !str_contains($pathname, '/../../../../../node_modules')) {
        if (str_contains($dir->getRealPath(), 'invalid_file')) {
          // There are some intentionally invalid files provided for testing
          // library API behaviors, ignore them.
          continue;
        }
        $files[] = [$dir->getRealPath()];
      }
    }
    return $files;
  }

}

class YamlStub extends Yaml {

  public static function getSerializer() {
    return '\Drupal\Tests\Component\Serialization\YamlParserProxy';
  }

}

class YamlParserProxy implements SerializationInterface {

  /**
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected static $mock;

  public static function setMock($mock) {
    static::$mock = $mock;
  }

  public static function encode($data) {
    return static::$mock->encode($data);
  }

  public static function decode($raw) {
    return static::$mock->decode($raw);
  }

  public static function getFileExtension() {
    return static::$mock->getFileExtension();
  }

}
