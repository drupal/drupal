<?php

namespace Drupal\Tests\serialization\Unit\Encoder;

use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Encoder\XmlEncoder
 * @group serialization
 */
class XmlEncoderTest extends UnitTestCase {

  /**
   * The XmlEncoder instance.
   *
   * @var \Drupal\serialization\Encoder\XmlEncoder
   */
  protected $encoder;

  /**
   * @var \Symfony\Component\Serializer\Encoder\XmlEncoder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $baseEncoder;

  /**
   * An array of test data.
   *
   * @var array
   */
  protected $testArray = array('test' => 'test');

  protected function setUp() {
    $this->baseEncoder = $this->getMock('Symfony\Component\Serializer\Encoder\XmlEncoder');
    $this->encoder = new XmlEncoder();
    $this->encoder->setBaseEncoder($this->baseEncoder);
  }

  /**
   * Tests the supportsEncoding() method.
   */
  public function testSupportsEncoding() {
    $this->assertTrue($this->encoder->supportsEncoding('xml'));
    $this->assertFalse($this->encoder->supportsEncoding('json'));
  }

  /**
   * Tests the supportsDecoding() method.
   */
  public function testSupportsDecoding() {
    $this->assertTrue($this->encoder->supportsDecoding('xml'));
    $this->assertFalse($this->encoder->supportsDecoding('json'));
  }

  /**
   * Tests the encode() method.
   */
  public function testEncode() {
    $this->baseEncoder->expects($this->once())
      ->method('encode')
      ->with($this->testArray, 'test', array())
      ->will($this->returnValue('test'));

    $this->assertEquals('test', $this->encoder->encode($this->testArray, 'test'));
  }

  /**
   * Tests the decode() method.
   */
  public function testDecode() {
    $this->baseEncoder->expects($this->once())
      ->method('decode')
      ->with('test', 'test', array())
      ->will($this->returnValue($this->testArray));

    $this->assertEquals($this->testArray, $this->encoder->decode('test', 'test'));
  }

}
