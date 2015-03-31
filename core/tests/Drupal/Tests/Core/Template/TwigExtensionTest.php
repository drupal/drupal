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
    $twig->addExtension((new TwigExtension($renderer))->setGenerators($this->getMock('Drupal\Core\Routing\UrlGeneratorInterface')));

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

}
