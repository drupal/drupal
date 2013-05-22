<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterHtmlImageSecure.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to restrict images to site.
 *
 * @Filter(
 *   id = "filter_html_image_secure",
 *   module = "filter",
 *   title = @Translation("Restrict images to this site"),
 *   description = @Translation("Disallows usage of &lt;img&gt; tag sources that are not hosted on this site by replacing them with a placeholder image."),
 *   type = FILTER_TYPE_HTML_RESTRICTOR,
 *   weight = 9
 * )
 */
class FilterHtmlImageSecure extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return _filter_html_image_secure_process($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return t('Only images hosted on this site may be used in &lt;img&gt; tags.');
  }

}
