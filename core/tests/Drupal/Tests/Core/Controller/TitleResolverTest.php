<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\TitleResolverTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\TitleResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the title resolver.
 *
 * @see \Drupal\Core\Controller\TitleResolver
 */
class TitleResolverTest extends UnitTestCase {

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * The actual tested title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  public static function getInfo() {
    return array(
      'name' => 'Title resolver',
      'description' => 'Tests the title resolver.',
      'group' => 'Routing',
    );
  }

  protected function setUp() {
    $this->controllerResolver = $this->getMock('\Drupal\Core\Controller\ControllerResolverInterface');
    $this->translationManager = $this->getMock('\Drupal\Core\StringTranslation\TranslationInterface');

    $this->titleResolver = new TitleResolver($this->controllerResolver, $this->translationManager);
  }

  /**
   * Tests a static title without a context.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitle() {
    $request = new Request();
    $route = new Route('/test-route', array('_title' => 'static title'));

    $this->translationManager->expects($this->once())
      ->method('translate')
      ->with('static title', array(), array())
      ->will($this->returnValue('translated title'));

    $this->assertEquals('translated title', $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with a context.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithContext() {
    $request = new Request();
    $route = new Route('/test-route', array('_title' => 'static title', '_title_context' => 'context'));

    $this->translationManager->expects($this->once())
      ->method('translate')
      ->with('static title', array(), array('context' => 'context'))
      ->will($this->returnValue('translated title with context'));

    $this->assertEquals('translated title with context', $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a dynamic title.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testDynamicTitle() {
    $request = new Request();
    $route = new Route('/test-route', array('_title' => 'static title', '_title_callback' => 'Drupal\Tests\Core\Controller\TitleCallback::example'));

    $callable = array(new TitleCallback(), 'example');
    $this->controllerResolver->expects($this->once())
      ->method('getControllerFromDefinition')
      ->with('Drupal\Tests\Core\Controller\TitleCallback::example')
      ->will($this->returnValue($callable));
    $this->controllerResolver->expects($this->once())
      ->method('getArguments')
      ->with($request, $callable)
      ->will($this->returnValue(array('example')));

    $this->assertEquals('test example', $this->titleResolver->getTitle($request, $route));
  }

}

/**
 * Provides an example title callback for the testDynamicTitle method above.
 */
class TitleCallback {

  /**
   * Gets the example string.
   *
   * @param string $value
   *   The dynamic value.
   *
   * @return string
   *   Returns the example string.
   */
  public function example($value) {
    return String::format('test @value', array('@value' => $value));
  }

}
