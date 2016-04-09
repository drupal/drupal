<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to restrict images to site.
 *
 * @Filter(
 *   id = "filter_html_image_secure",
 *   title = @Translation("Restrict images to this site"),
 *   description = @Translation("Disallows usage of &lt;img&gt; tag sources that are not hosted on this site by replacing them with a placeholder image."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 9
 * )
 */
class FilterHtmlImageSecure extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_html_image_secure_process($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Only images hosted on this site may be used in &lt;img&gt; tags.');
  }

}
