<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Template\TwigExtensionTest.
 */

namespace Drupal\Tests\Core\Template;

use Drupal\Core\Template\TwigExtension;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the twig extension.
 *
 * @group Template
 *
 * @coversDefaultClass \Drupal\Core\Template\TwigExtension
 */
class TwigExtensionTest extends UnitTestCase {

  /**
   * Tests the escaping
   *
   * @dataProvider providerTestEscaping
   */
  public function testEscaping($template, $expected) {
    $renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $twig = new \Twig_Environment(NULL, array(
      'debug' => TRUE,
      'cache' => FALSE,
      'autoescape' => TRUE,
      'optimizations' => 0
    ));
    $twig->addExtension((new TwigExtension($renderer))->setUrlGenerator($this->getMock('Drupal\Core\Routing\UrlGeneratorInterface')));

    $nodes = $twig->parse($twig->tokenize($template));

    $this->assertSame($expected, $nodes->getNode('body')
        ->getNode(0)
        ->getNode('expr') instanceof \Twig_Node_Expression_Filter);
  }

  /**
   * Provides tests data for testEscaping
   *
   * @return array
   *   An array of test data each containing of a twig template string and
   *   a boolean expecting whether the path will be safe.
   */
  public function providerTestEscaping() {
    return array(
      array('{{ path("foo") }}', FALSE),
      array('{{ path("foo", {}) }}', FALSE),
      array('{{ path("foo", { foo: "foo" }) }}', FALSE),
      array('{{ path("foo", foo) }}', TRUE),
      array('{{ path("foo", { foo: foo }) }}', TRUE),
      array('{{ path("foo", { foo: ["foo", "bar"] }) }}', TRUE),
      array('{{ path("foo", { foo: "foo", bar: "bar" }) }}', TRUE),
      array('{{ path(name = "foo", parameters = {}) }}', FALSE),
      array('{{ path(name = "foo", parameters = { foo: "foo" }) }}', FALSE),
      array('{{ path(name = "foo", parameters = foo) }}', TRUE),
      array(
        '{{ path(name = "foo", parameters = { foo: ["foo", "bar"] }) }}',
        TRUE
      ),
      array('{{ path(name = "foo", parameters = { foo: foo }) }}', TRUE),
      array(
        '{{ path(name = "foo", parameters = { foo: "foo", bar: "bar" }) }}',
        TRUE
      ),
    );
  }

  /**
   * Tests the active_theme function.
   */
  public function testActiveTheme() {
    $renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $extension = new TwigExtension($renderer);
    $theme_manager = $this->getMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $active_theme = $this->getMockBuilder('\Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $active_theme
      ->expects($this->once())
      ->method('getName')
      ->willReturn('test_theme');
    $theme_manager
      ->expects($this->once())
      ->method('getActiveTheme')
      ->willReturn($active_theme);
    $extension->setThemeManager($theme_manager);

    $loader = new \Twig_Loader_String();
    $twig = new \Twig_Environment($loader);
    $twig->addExtension($extension);
    $result = $twig->render('{{ active_theme() }}');
    $this->assertEquals('test_theme', $result);
  }

}
