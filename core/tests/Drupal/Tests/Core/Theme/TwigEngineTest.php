<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Theme\TwigEngineTest.
 */

namespace Drupal\Tests\Core\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Test coverage for the file core/themes/engines/twig/twig.engine.
 *
 * @group Theme
 */
class TwigEngineTest extends UnitTestCase {

  /**
   * The mocked Twig environment.
   *
   * @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $twigEnvironment;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure that twig.engine is loaded, it is needed to access
    // twig_drupal_escape_filter().
    require_once $this->root . '/core/themes/engines/twig/twig.engine';

    $this->twigEnvironment = $this->getMock('\Twig_Environment');
  }

  /**
   * Tests output of integer and double 0 values of twig_render_var().
   *
   * @see https://www.drupal.org/node/2417733
   */
  public function testsRenderZeroValue() {
    $this->assertSame(twig_render_var(0), 0, 'twig_render_var() renders zero correctly when provided as an integer.');
    $this->assertSame(twig_render_var(0.0), 0, 'twig_render_var() renders zero correctly when provided as a double.');
  }

  /**
   * Tests output of integer and double 0 values of twig_drupal_escape_filter().
   *
   * @see https://www.drupal.org/node/2417733
   */
  public function testsRenderEscapedZeroValue() {
    $this->assertSame(twig_drupal_escape_filter($this->twigEnvironment, 0), 0, 'twig_escape_filter() returns zero correctly when provided as an integer.');
    $this->assertSame(twig_drupal_escape_filter($this->twigEnvironment, 0.0), 0, 'twig_escape_filter() returns zero correctly when provided as a double.');
  }

}
