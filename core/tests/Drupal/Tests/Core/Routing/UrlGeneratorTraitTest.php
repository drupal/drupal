<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\UrlGeneratorTraitTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Routing\UrlGeneratorTrait
 * @group Routing
 */
class UrlGeneratorTraitTest extends UnitTestCase {

  /**
   * @covers ::setUrlGenerator
   * @covers ::getUrlGenerator
   */
  public function testGetUrlGenerator() {
    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $url_generator_trait_object = $this->getMockForTrait('Drupal\Core\Routing\UrlGeneratorTrait');
    $url_generator_trait_object->setUrlGenerator($url_generator);

    $url_generator_method = new \ReflectionMethod($url_generator_trait_object, 'getUrlGenerator');
    $url_generator_method->setAccessible(TRUE);
    $result = $url_generator_method->invoke($url_generator_trait_object);
    $this->assertEquals($url_generator, $result);
  }

}
