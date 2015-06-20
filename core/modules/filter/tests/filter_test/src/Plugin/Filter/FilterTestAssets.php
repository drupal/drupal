<?php

/**
 * @file
 * Contains \Drupal\filter_test\Plugin\Filter\FilterTestAssets.
 */

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to attach assets
 *
 * @Filter(
 *   id = "filter_test_assets",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Does not change content; attaches assets."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTestAssets extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $result->addAttachments(array(
      'library' => array(
        'filter/caption',
      ),
    ));
    return $result;
  }

}
