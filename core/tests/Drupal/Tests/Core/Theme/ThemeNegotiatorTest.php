<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Theme\ThemeNegotiator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Theme\ThemeNegotiator
 * @group Theme
 */
class ThemeNegotiatorTest extends UnitTestCase {

  /**
   * The mocked theme access checker.
   *
   * @var \Drupal\Core\Theme\ThemeAccessCheck|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeAccessCheck;

  /**
   * The container builder.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The actual tested theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiator
   */
  protected $themeNegotiator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeAccessCheck = $this->getMockBuilder('\Drupal\Core\Theme\ThemeAccessCheck')
      ->disableOriginalConstructor()
      ->getMock();
    $this->container = new ContainerBuilder();
  }

  /**
   * Tests determining the theme.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveTheme(): void {
    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->willReturn('example_test');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(TRUE);

    $this->container->set('test_negotiator', $negotiator);

    $negotiators = ['test_negotiator'];

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->willReturn(TRUE);

    $route_match = new RouteMatch('test_route', new Route('/test-route'), [], []);
    $theme = $this->createThemeNegotiator($negotiators)->determineActiveTheme($route_match);

    $this->assertEquals('example_test', $theme);
  }

  /**
   * Tests determining with two negotiators checking the priority.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveThemeWithPriority(): void {
    $negotiators = [];

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->willReturn('example_test');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(TRUE);

    $negotiators['test_negotiator_1'] = $negotiator;

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->never())
      ->method('determineActiveTheme');
    $negotiator->expects($this->never())
      ->method('applies');

    $negotiators['test_negotiator_2'] = $negotiator;

    foreach ($negotiators as $id => $negotiator) {
      $this->container->set($id, $negotiator);
    }

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->willReturn(TRUE);

    $route_match = new RouteMatch('test_route', new Route('/test-route'), [], []);
    $theme = $this->createThemeNegotiator(array_keys($negotiators))->determineActiveTheme($route_match);

    $this->assertEquals('example_test', $theme);
  }

  /**
   * Tests determining with two negotiators of which just one returns access.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveThemeWithAccessCheck(): void {
    $negotiators = [];

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->willReturn('example_test');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(TRUE);

    $negotiators['test_negotiator_1'] = $negotiator;

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->willReturn('example_test2');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(TRUE);

    $negotiators['test_negotiator_2'] = $negotiator;

    foreach ($negotiators as $id => $negotiator) {
      $this->container->set($id, $negotiator);
    }

    $this->themeAccessCheck->expects($this->exactly(2))
      ->method('checkAccess')
      ->willReturnMap([
        ['example_test', FALSE],
        ['example_test2', TRUE],
      ]);

    $route_match = new RouteMatch('test_route', new Route('/test-route'), [], []);
    $theme = $this->createThemeNegotiator(array_keys($negotiators))->determineActiveTheme($route_match);

    $this->assertEquals('example_test2', $theme);
  }

  /**
   * Tests determining with two negotiators of which one does not apply.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  public function testDetermineActiveThemeWithNotApplyingNegotiator(): void {
    $negotiators = [];

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->never())
      ->method('determineActiveTheme');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(FALSE);

    $negotiators['test_negotiator_1'] = $negotiator;

    $negotiator = $this->createMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->willReturn('example_test2');
    $negotiator->expects($this->once())
      ->method('applies')
      ->willReturn(TRUE);

    $negotiators['test_negotiator_2'] = $negotiator;

    foreach ($negotiators as $id => $negotiator) {
      $this->container->set($id, $negotiator);
    }

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->willReturn(TRUE);

    $route_match = new RouteMatch('test_route', new Route('/test-route'), [], []);
    $theme = $this->createThemeNegotiator(array_keys($negotiators))->determineActiveTheme($route_match);

    $this->assertEquals('example_test2', $theme);
  }

  /**
   * Creates a new theme negotiator instance.
   *
   * @param array $negotiators
   *   An array of negotiator IDs.
   *
   * @return \Drupal\Core\Theme\ThemeNegotiator
   */
  protected function createThemeNegotiator(array $negotiators) {
    $resolver = new ClassResolver($this->container);
    $theme_negotiator = new ThemeNegotiator($this->themeAccessCheck, $resolver, $negotiators);
    return $theme_negotiator;
  }

}
