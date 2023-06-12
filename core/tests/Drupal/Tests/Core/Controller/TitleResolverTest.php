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
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @coversDefaultClass \Drupal\Core\Controller\TitleResolver
 * @group Controller
 */
class TitleResolverTest extends UnitTestCase {

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $translationManager;

  /**
   * The mocked argument resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $argumentResolver;

  /**
   * The actual tested title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->controllerResolver = $this->createMock('\Drupal\Core\Controller\ControllerResolverInterface');
    $this->translationManager = $this->createMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->argumentResolver = $this->createMock('\Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface');

    $this->titleResolver = new TitleResolver($this->controllerResolver, $this->translationManager, $this->argumentResolver);
  }

  /**
   * Tests a static title without a context.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitle() {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => 'static title']);
    $this->assertEquals(new TranslatableMarkup('static title', [], [], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title of '0'.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleZero() {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => '0', '_title_context' => '0']);
    $this->assertEquals(new TranslatableMarkup('0', [], ['context' => '0'], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with a context.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithContext() {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => 'static title', '_title_context' => 'context']);
    $this->assertEquals(new TranslatableMarkup('static title', [], ['context' => 'context'], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with a parameter.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   *
   * @dataProvider providerTestStaticTitleWithParameter
   */
  public function testStaticTitleWithParameter($title, $expected_title) {
    $raw_variables = new ParameterBag(['test' => 'value', 'test2' => 'value2']);
    $request = new Request();
    $request->attributes->set('_raw_variables', $raw_variables);

    $route = new Route('/test-route', ['_title' => $title]);
    $this->assertEquals($expected_title, $this->titleResolver->getTitle($request, $route));
  }

  public function providerTestStaticTitleWithParameter() {
    $translation_manager = $this->createMock('\Drupal\Core\StringTranslation\TranslationInterface');
    return [
      ['static title @test', new TranslatableMarkup('static title @test', ['@test' => 'value', '%test' => 'value', '@test2' => 'value2', '%test2' => 'value2'], [], $translation_manager)],
      ['static title %test', new TranslatableMarkup('static title %test', ['@test' => 'value', '%test' => 'value', '@test2' => 'value2', '%test2' => 'value2'], [], $translation_manager)],
    ];
  }

  /**
   * Tests a static title with a non-scalar value parameter.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithNullAndArrayValueParameter() {
    $raw_variables = new ParameterBag(['test1' => NULL, 'test2' => ['foo' => 'bar'], 'test3' => 'value']);
    $request = new Request();
    $request->attributes->set('_raw_variables', $raw_variables);

    $route = new Route('/test-route', ['_title' => 'static title %test1 @test1 %test2 @test2 %test3 @test3']);
    $translatable_markup = $this->titleResolver->getTitle($request, $route);
    $arguments = $translatable_markup->getArguments();
    $this->assertNotContains('@test1', $arguments);
    $this->assertNotContains('%test1', $arguments);
    $this->assertNotContains('@test2', $arguments);
    $this->assertNotContains('%test2', $arguments);
    $this->assertSame('value', $translatable_markup->getArguments()['@test3']);
    $this->assertSame('value', $translatable_markup->getArguments()['%test3']);
  }

  /**
   * Tests a dynamic title.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testDynamicTitle() {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => 'static title', '_title_callback' => 'Drupal\Tests\Core\Controller\TitleCallback::example']);

    $callable = [new TitleCallback(), 'example'];
    $this->controllerResolver->expects($this->once())
      ->method('getControllerFromDefinition')
      ->with('Drupal\Tests\Core\Controller\TitleCallback::example')
      ->willReturn($callable);
    $this->argumentResolver->expects($this->once())
      ->method('getArguments')
      ->with($request, $callable)
      ->willReturn(['example']);

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
