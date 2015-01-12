<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Serialization\YamlTest.
 */

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Serialization\Yaml
 * @group Serialization
 */
class YamlTest extends UnitTestCase {

  /**
   * @covers ::decode
   */
  public function testDecode() {
    // Test that files without line break endings are properly interpreted.
    $yaml = 'foo: bar';
    $expected = array(
      'foo' => 'bar',
    );
    $this->assertSame($expected, Yaml::decode($yaml));
    $yaml .= "\n";
    $this->assertSame($expected, Yaml::decode($yaml));
    $yaml .= "\n";
    $this->assertSame($expected, Yaml::decode($yaml));

    $yaml = "{}\n";
    $expected = array();
    $this->assertSame($expected, Yaml::decode($yaml));

    $yaml = '';
    $this->assertNULL(Yaml::decode($yaml));
    $yaml .= "\n";
    $this->assertNULL(Yaml::decode($yaml));
    $yaml .= "\n";
    $this->assertNULL(Yaml::decode($yaml));
  }

  /**
   * @covers ::encode
   */
  public function testEncode() {
    $decoded = array(
      'foo' => 'bar',
    );
    $this->assertSame('foo: bar' . "\n", Yaml::encode($decoded));
  }

  /**
   * @covers ::getFileExtension
   */
  public function testGetFileExtension() {
    $this->assertEquals('yml', Yaml::getFileExtension());
  }

}
