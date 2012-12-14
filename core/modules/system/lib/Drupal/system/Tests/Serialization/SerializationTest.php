<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Serialization\SerializationTest.
 */

namespace Drupal\system\Tests\Serialization;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class SerializationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('serialization_test');

  /**
   * The serializer service to test.
   *
   * @var Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  public static function getInfo() {
    return array(
      'name' => 'Serialization tests',
      'description' => 'Funtional tests for serialization system.',
      'group' => 'Serialization',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->serializer = drupal_container()->get('serializer');
  }

  /**
   * Confirms that modules can register normalizers and encoders.
   */
  public function testSerializerComponentRegistration() {
    $object = new \stdClass();
    $format = 'serialization_test';
    $expected = 'Normalized by SerializationTestNormalizer, Encoded by SerializationTestEncoder';

    // Ensure the serialization invokes the expected normalizer and encoder.
    $this->assertIdentical($this->serializer->serialize($object, $format), $expected);

    // Ensure the serialization fails for an unsupported format.
    try {
      $this->serializer->serialize($object, 'unsupported_format');
      $this->fail('The serializer was expected to throw an exception for an unsupported format, but did not.');
    }
    catch (UnexpectedValueException $e) {
    }
  }
}
