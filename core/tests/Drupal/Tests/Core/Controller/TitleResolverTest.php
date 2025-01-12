<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\TitleResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;
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
    parent::setUp();

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
  public function testStaticTitle(): void {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => 'static title']);
    $this->assertEquals(new TranslatableMarkup('static title', [], [], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title of '0'.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleZero(): void {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => '0', '_title_context' => '0']);
    $this->assertEquals(new TranslatableMarkup('0', [], ['context' => '0'], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with a context.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithContext(): void {
    $request = new Request();
    $route = new Route('/test-route', ['_title' => 'static title', '_title_context' => 'context']);
    $this->assertEquals(new TranslatableMarkup('static title', [], ['context' => 'context'], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with a parameter.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithParameter(): void {
    $raw_variables = new InputBag(['test' => 'value', 'test2' => 'value2']);
    $request = new Request();
    $request->attributes->set('_raw_variables', $raw_variables);

    $route = new Route('/test-route', ['_title' => 'static title @test']);
    $this->assertEquals(new TranslatableMarkup('static title @test', ['@test' => 'value', '%test' => 'value', '@test2' => 'value2', '%test2' => 'value2'], [], $this->translationManager), $this->titleResolver->getTitle($request, $route));

    $route = new Route('/test-route', ['_title' => 'static title %test']);
    $this->assertEquals(new TranslatableMarkup('static title %test', ['@test' => 'value', '%test' => 'value', '@test2' => 'value2', '%test2' => 'value2'], [], $this->translationManager), $this->titleResolver->getTitle($request, $route));
  }

  /**
   * Tests a static title with and without overridden default arguments.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithArguments(): void {
    // Set up the request with optional override variables.
    $request = new Request();
    $raw_variables = new InputBag(['test' => 'override value']);

    // Array of cases.
    $cases = [
      // Case 1: No override, uses default arguments.
      [
        'route_args' => ['_title' => 'static title @test', '_title_arguments' => ['@test' => 'value', '@test2' => 'value2']],
        'expected' => new TranslatableMarkup('static title @test', ['@test' => 'value', '@test2' => 'value2'], [], $this->translationManager),
        'override' => FALSE,
      ],
      [
        'route_args' => ['_title' => 'static title %test', '_title_arguments' => ['%test' => 'value', '%test2' => 'value2']],
        'expected' => new TranslatableMarkup('static title %test', ['%test' => 'value', '%test2' => 'value2'], [], $this->translationManager),
        'override' => FALSE,
      ],
      // Case 2: Override arguments.
      [
        'route_args' => ['_title' => 'static title @test @test2', '_title_arguments' => ['@test' => 'value', '@test2' => 'value2']],
        'expected' => new TranslatableMarkup('static title @test @test2', ['@test' => 'override value', '%test' => 'override value', '@test2' => 'value2'], [], $this->translationManager),
        'override' => TRUE,
      ],
      [
        'route_args' => ['_title' => 'static title %test %test2', '_title_arguments' => ['%test' => 'value', '%test2' => 'value2']],
        'expected' => new TranslatableMarkup('static title %test %test2', ['@test' => 'override value', '%test' => 'override value', '%test2' => 'value2'], [], $this->translationManager),
        'override' => TRUE,
      ],
    ];

    foreach ($cases as $case) {
      // Adjust the request based on whether we expect overrides.
      if ($case['override']) {
        $request->attributes->set('_raw_variables', $raw_variables);
      }
      $route = new Route('/test-route', $case['route_args']);
      $this->assertEquals($case['expected'], $this->titleResolver->getTitle($request, $route));
    }
  }

  /**
   * Tests a static title with a non-scalar value parameter.
   *
   * @see \Drupal\Core\Controller\TitleResolver::getTitle()
   */
  public function testStaticTitleWithNullAndArrayValueParameter(): void {
    $raw_variables = new InputBag(['test1' => NULL, 'test2' => ['foo' => 'bar'], 'test3' => 'value']);
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
  public function testDynamicTitle(): void {
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
