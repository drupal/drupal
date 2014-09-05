<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Normalizer\NormalizerBaseTest.
 */

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Tests\UnitTestCase;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\NormalizerBase
 * @group serialization
 */
class NormalizerBaseTest extends UnitTestCase {

  /**
   * Tests the supportsNormalization method.
   *
   * @dataProvider providerTestSupportsNormalization
   *
   * @param bool $expected_return
   *   The expected boolean return value from supportNormalization.
   * @param mixed $data
   *   The data passed to supportsNormalization.
   * @param string $supported_interface_or_class
   *   (optional) the supported interface or class to set on the normalizer.
   */
  public function testSupportsNormalization($expected_return, $data, $supported_interface_or_class = NULL) {
    $normalizer_base = $this->getMockForAbstractClass('Drupal\Tests\serialization\Unit\Normalizer\TestNormalizerBase');

    if (isset($supported_interface_or_class)) {
      $normalizer_base->setSupportedInterfaceOrClass($supported_interface_or_class);
    }

    $this->assertSame($expected_return, $normalizer_base->supportsNormalization($data));
  }

  /**
   * Data provider for testSupportsNormalization.
   *
   * @return array
   *   An array of provider data for testSupportsNormalization.
   */
  public function providerTestSupportsNormalization() {
    return array(
      // Something that is not an object should return FALSE immediately.
      array(FALSE, array()),
      // An object with no class set should return FALSE.
      array(FALSE, new \stdClass()),
      // Set a supported Class.
      array(TRUE, new \stdClass(), 'stdClass'),
      // Set a supported interface.
      array(TRUE, new \RecursiveArrayIterator(), 'RecursiveIterator'),
      // Set a different class.
      array(FALSE, new \stdClass(), 'ArrayIterator'),
      // Set a different interface.
      array(FALSE, new \stdClass(), 'RecursiveIterator'),
    );
  }

}

/**
 * Test class for NormalizerBase.
 */
abstract class TestNormalizerBase extends NormalizerBase {

  /**
   * Sets the protected supportedInterfaceOrClass property.
   *
   * @param string $supported_interface_or_class
   *   The class name to set.
   */
  public function setSupportedInterfaceOrClass($supported_interface_or_class) {
    $this->supportedInterfaceOrClass = $supported_interface_or_class;
  }

}
