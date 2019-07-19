<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Routing\UrlGeneratorTrait
 * @group Routing
 * @group legacy
 */
class UrlGeneratorTraitTest extends UnitTestCase {

  /**
   * @covers ::setUrlGenerator
   * @covers ::getUrlGenerator
   *
   * @expectedDeprecation Drupal\Core\Routing\UrlGeneratorTrait::setUrlGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/2614344
   * @expectedDeprecation Drupal\Core\Routing\UrlGeneratorTrait::getUrlGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the url_generator service instead. See https://www.drupal.org/node/2614344
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
   *
   * @expectedDeprecation Drupal\Core\Routing\UrlGeneratorTrait::redirect() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use new RedirectResponse(Url::fromRoute()) instead. See https://www.drupal.org/node/2614344
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

  /**
   * @covers ::url
   *
   * @expectedDeprecation Drupal\Core\Routing\UrlGeneratorTrait::url() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Url::fromUri() instead. See https://www.drupal.org/node/2614344
   */
  public function testUrl() {
    $route_name = 'some_route_name';
    $generated_url = 'some/generated/url';

    $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->once())
      ->method('generateFromRoute')
      ->with($route_name, [], [])
      ->willReturn($generated_url);

    $url_generator_trait_object = $this->getMockForTrait('Drupal\Core\Routing\UrlGeneratorTrait');
    $url_generator_trait_object->setUrlGenerator($url_generator);

    $url_generator_method = new \ReflectionMethod($url_generator_trait_object, 'url');
    $url_generator_method->setAccessible(TRUE);

    $result = $url_generator_method->invoke($url_generator_trait_object, $route_name);
    $this->assertSame($generated_url, $result);
  }

}
