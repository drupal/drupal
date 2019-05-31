<?php

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
    $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $url_generator_trait_object = $this->getMockForTrait('Drupal\Core\Routing\UrlGeneratorTrait');
    $url_generator_trait_object->setUrlGenerator($url_generator);

    $url_generator_method = new \ReflectionMethod($url_generator_trait_object, 'getUrlGenerator');
    $url_generator_method->setAccessible(TRUE);
    $result = $url_generator_method->invoke($url_generator_trait_object);
    $this->assertEquals($url_generator, $result);
  }

  /**
   * @covers ::redirect
   */
  public function testRedirect() {
    $route_name = 'some_route_name';
    $generated_url = 'some/generated/url';

    $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->once())
      ->method('generateFromRoute')
      ->with($route_name, [], ['absolute' => TRUE])
      ->willReturn($generated_url);

    $url_generator_trait_object = $this->getMockForTrait('Drupal\Core\Routing\UrlGeneratorTrait');
    $url_generator_trait_object->setUrlGenerator($url_generator);

    $url_generator_method = new \ReflectionMethod($url_generator_trait_object, 'redirect');
    $url_generator_method->setAccessible(TRUE);

    $result = $url_generator_method->invoke($url_generator_trait_object, $route_name);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $result);
    $this->assertSame($generated_url, $result->getTargetUrl());
  }

}
