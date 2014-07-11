<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeNegotiatorTest.
 */

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Theme\ThemeNegotiator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Theme\ThemeNegotiator
 * @group Theme
 */
class ThemeNegotiatorTest extends UnitTestCase {

  /**
   * The mocked theme access checker.
   *
   * @var \Drupal\Core\Theme\ThemeAccessCheck|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeAccessCheck;

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

  protected function setUp() {
    $this->themeAccessCheck = $this->getMockBuilder('\Drupal\Core\Theme\ThemeAccessCheck')
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeNegotiator = new ThemeNegotiator($this->themeAccessCheck);
  }

  /**
   * Tests determining the theme.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveTheme() {
    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->will($this->returnValue('example_test'));
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $this->themeNegotiator->addNegotiator($negotiator, 0);

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->will($this->returnValue(TRUE));

    $route_match = new RouteMatch('test_route', new Route('/test-route'), array(), array());
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);

    $this->assertEquals('example_test', $theme);
  }

  /**
   * Tests determining with two negotiators checking the priority.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveThemeWithPriority() {
    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->will($this->returnValue('example_test'));
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $this->themeNegotiator->addNegotiator($negotiator, 10);

    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->never())
      ->method('determineActiveTheme');
    $negotiator->expects($this->never())
      ->method('applies');

    $this->themeNegotiator->addNegotiator($negotiator, 0);

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->will($this->returnValue(TRUE));

    $route_match = new RouteMatch('test_route', new Route('/test-route'), array(), array());
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);

    $this->assertEquals('example_test', $theme);
  }

  /**
   * Tests determining with two negotiators of which just one returns access.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme()
   */
  public function testDetermineActiveThemeWithAccessCheck() {
    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->will($this->returnValue('example_test'));
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $this->themeNegotiator->addNegotiator($negotiator, 10);

    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->will($this->returnValue('example_test2'));
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $this->themeNegotiator->addNegotiator($negotiator, 0);

    $this->themeAccessCheck->expects($this->at(0))
      ->method('checkAccess')
      ->with('example_test')
      ->will($this->returnValue(FALSE));

    $this->themeAccessCheck->expects($this->at(1))
      ->method('checkAccess')
      ->with('example_test2')
      ->will($this->returnValue(TRUE));

    $route_match = new RouteMatch('test_route', new Route('/test-route'), array(), array());
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);

    $this->assertEquals('example_test2', $theme);
  }

  /**
   * Tests determining with two negotiators of which one does not apply.
   *
   * @see \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  public function testDetermineActiveThemeWithNotApplyingNegotiator() {
    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->never())
      ->method('determineActiveTheme');
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(FALSE));

    $this->themeNegotiator->addNegotiator($negotiator, 10);

    $negotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    $negotiator->expects($this->once())
      ->method('determineActiveTheme')
      ->will($this->returnValue('example_test2'));
    $negotiator->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $this->themeNegotiator->addNegotiator($negotiator, 0);

    $this->themeAccessCheck->expects($this->any())
      ->method('checkAccess')
      ->will($this->returnValue(TRUE));

    $route_match = new RouteMatch('test_route', new Route('/test-route'), array(), array());
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);

    $this->assertEquals('example_test2', $theme);
  }

}
