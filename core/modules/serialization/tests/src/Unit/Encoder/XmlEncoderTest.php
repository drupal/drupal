<?php

namespace Drupal\Tests\serialization\Unit\Encoder;

use Drupal\serialization\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder as BaseXmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
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
   * @var \Symfony\Component\Serializer\Encoder\XmlEncoder|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $baseEncoder;

  /**
   * An array of test data.
   *
   * @var array
   */
  protected $testArray = ['test' => 'test'];

  protected function setUp(): void {
    $this->baseEncoder = $this->createMock(BaseXmlEncoder::class);
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
      ->with($this->testArray, 'test', [])
      ->will($this->returnValue('test'));

    $this->assertEquals('test', $this->encoder->encode($this->testArray, 'test'));
  }

  /**
   * Tests the decode() method.
   */
  public function testDecode() {
    $this->baseEncoder->expects($this->once())
      ->method('decode')
      ->with('test', 'test', [])
      ->will($this->returnValue($this->testArray));

    $this->assertEquals($this->testArray, $this->encoder->decode('test', 'test'));
  }

  /**
   * @covers ::getBaseEncoder
   */
  public function testDefaultEncoderHasSerializer() {
    // The serializer should be set on the Drupal encoder, which should then
    // set it on our default encoder.
    $encoder = new XmlEncoder();
    $serializer = new Serializer([new GetSetMethodNormalizer()]);
    $encoder->setSerializer($serializer);
    $base_encoder = $encoder->getBaseEncoder();
    $this->assertInstanceOf(BaseXmlEncoder::class, $base_encoder);
    // Test the encoder.
    $base_encoder->encode(['a' => new TestObject()], 'xml');
  }

}

class TestObject {

  public function getA() {
    return 'A';
  }

}
