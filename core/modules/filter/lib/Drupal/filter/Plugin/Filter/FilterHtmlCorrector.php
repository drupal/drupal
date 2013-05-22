<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterHtmlCorrector.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to correct faulty and chopped off HTML.
 *
 * @Filter(
 *   id = "filter_htmlcorrector",
 *   module = "filter",
 *   title = @Translation("Correct faulty and chopped off HTML"),
 *   type = FILTER_TYPE_HTML_RESTRICTOR,
 *   weight = 10
 * )
 */
class FilterHtmlCorrector extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return _filter_htmlcorrector($text);
  }

}
