<?php

namespace Drupal\Tests\Core\Render;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;

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

    $original_placeholder_markup = (string) $build['#markup'];
    $processed_placeholder_markup = Html::serialize(Html::load($build['#markup']));

    $this->assertEquals($original_placeholder_markup, $processed_placeholder_markup);
  }

  /**
   * Create an element with #lazy_builder callback. Between two renders, cache
   * contexts nor tags sort change. Placeholder should generate same hash to not
   * be rendered twice.
   *
   * @covers ::createPlaceholder
   */
  public function testRenderPlaceholdersDifferentSortedContextsTags() {
    $contexts_1 = ['user', 'foo'];
    $contexts_2 = ['foo', 'user'];
    $tags_1 = ['current-temperature', 'foo'];
    $tags_2 = ['foo', 'current-temperature'];
    $test_element = [
        '#cache' => [
          'max-age' => Cache::PERMANENT,
        ],
        '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', ['foo' => TRUE]],
    ];

    $test_element['#cache']['contexts'] = $contexts_1;
    $test_element['#cache']['tags'] = $tags_1;
    $placeholder_element1 = $this->placeholderGenerator->createPlaceholder($test_element);

    $test_element['#cache']['contexts'] = $contexts_2;
    $test_element['#cache']['tags'] = $tags_1;
    $placeholder_element2 = $this->placeholderGenerator->createPlaceholder($test_element);

    $test_element['#cache']['contexts'] = $contexts_1;
    $test_element['#cache']['tags'] = $tags_2;
    $placeholder_element3 = $this->placeholderGenerator->createPlaceholder($test_element);

    // Verify placeholder and specially hash are same with different contexts
    // order.
    $this->assertSame((string) $placeholder_element1['#markup'], (string) $placeholder_element2['#markup']);

    // Verify placeholder and specially hash are same with different tags order.
    $this->assertSame((string) $placeholder_element1['#markup'], (string) $placeholder_element3['#markup']);
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
