<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\TitleResolverTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\TitleResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Controller\TitleResolver
 * @group Controller
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
   * Tests a static title with a parameter.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   *
   * @dataProvider providerTestStaticTitleWithParameter
   */
  public function testStaticTitleWithParameter($title, $expected_title) {
    $raw_variables = new ParameterBag(array('test' => 'value', 'test2' => 'value2'));
    $request = new Request();
    $request->attributes->set('_raw_variables', $raw_variables);

    $route = new Route('/test-route', array('_title' => $title));

    $this->translationManager->expects($this->once())
      ->method('translate')
      ->with($title, $this->logicalOr($this->arrayHasKey('@test'), $this->arrayHasKey('%test'), $this->arrayHasKey('!test')), array())
      ->will($this->returnValue('static title value'));

    $this->assertEquals($expected_title, $this->titleResolver->getTitle($request, $route));
  }

  public function providerTestStaticTitleWithParameter() {
    return array(
      array('static title @test', 'static title value'),
      array('static title !test', 'static title value'),
      array('static title %test', 'static title value'),
    );
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
    return 'test ' . $value;
  }

}
