<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeNegotiatorTest.
 */

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Theme\ThemeNegotiator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the theme negotiator.
 *
 * @group Drupal
 *
 * @see \Drupal\Core\Theme\ThemeNegotiator
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

  public static function getInfo() {
    return array(
      'name' => 'Theme negotiator',
      'description' => 'Tests the theme negotiator.',
      'group' => 'Theme',
    );
  }

  protected function setUp() {
    $this->themeAccessCheck = $this->getMockBuilder('\Drupal\Core\Theme\ThemeAccessCheck')
      ->disableOriginalConstructor()
      ->getMock();
    $this->requestStack = new RequestStack();
    $this->themeNegotiator = new ThemeNegotiator($this->themeAccessCheck, $this->requestStack);
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

    $request = Request::create('/test-route');
    $theme = $this->themeNegotiator->determineActiveTheme($request);

    $this->assertEquals('example_test', $theme);
    $this->assertEquals('example_test', $request->attributes->get('_theme_active'));
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

    $request = Request::create('/test-route');
    $theme = $this->themeNegotiator->determineActiveTheme($request);

    $this->assertEquals('example_test', $theme);
    $this->assertEquals('example_test', $request->attributes->get('_theme_active'));
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

    $request = Request::create('/test-route');
    $theme = $this->themeNegotiator->determineActiveTheme($request);

    $this->assertEquals('example_test2', $theme);
    $this->assertEquals('example_test2', $request->attributes->get('_theme_active'));
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

    $request = Request::create('/test-route');
    $theme = $this->themeNegotiator->determineActiveTheme($request);

    $this->assertEquals('example_test2', $theme);
    $this->assertEquals('example_test2', $request->attributes->get('_theme_active'));
  }

}
