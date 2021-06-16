<?php

namespace Drupal\ckeditor_test\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * A filter that adds a test attribute to any configured HTML tags.
 *
 * @Filter(
 *   id = "test_attribute_filter",
 *   title = @Translation("Test Attribute Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "tags" = {},
 *   },
 *   weight = -10
 * )
 */
class TestAttributeFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $document = Html::load($text);
    foreach ($this->settings['tags'] as $tag) {
      $tag_elements = $document->getElementsByTagName($tag);
      foreach ($tag_elements as $tag_element) {
        $tag_element->setAttribute('test_attribute', 'test attribute value');
      }
    }
    return new FilterProcessResult(Html::serialize($document));
  }

}
