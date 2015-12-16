<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\PlaceholderGeneratorTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Render\PlaceholderGenerator
 * @group Render
 */
class PlaceholderGeneratorTest extends RendererTestBase {

  /**
   * The tested placeholder generator.
   *
   * @var \Drupal\Core\Render\PlaceholderGenerator
   */
  protected $placeholderGenerator;

  /**
   * @covers ::createPlaceholder
   * @dataProvider providerCreatePlaceholderGeneratesValidHtmlMarkup
   *
   * Ensure that the generated placeholder markup is valid. If it is not, then
   * simply using DOMDocument on HTML that contains placeholders may modify the
   * placeholders' markup, which would make it impossible to replace the
   * placeholders: the placeholder markup in #attached versus that in the HTML
   * processed by DOMDocument would no longer match.
   */
  public function testCreatePlaceholderGeneratesValidHtmlMarkup(array $element) {
    $build = $this->placeholderGenerator->createPlaceholder($element);

    $original_placeholder_markup = (string)$build['#markup'];
    $processed_placeholder_markup = Html::serialize(Html::load($build['#markup']));

    $this->assertEquals($original_placeholder_markup, $processed_placeholder_markup);
  }

  /**
   * @return array
   */
  public function providerCreatePlaceholderGeneratesValidHtmlMarkup() {
    return [
      'multiple-arguments' => [['#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo', 'bar']]]],
      'special-character-&' => [['#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo&bar']]]],
      'special-character-"' => [['#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo"bar']]]],
      'special-character-<' => [['#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo<bar']]]],
      'special-character->' => [['#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo>bar']]]],
    ];

  }

}
