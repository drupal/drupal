<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to restrict images to site.
 */
#[Filter(
  id: "filter_html_image_secure",
  title: new TranslatableMarkup("Restrict images to this site"),
  description: new TranslatableMarkup("Disallows usage of &lt;img&gt; tag sources that are not hosted on this site by replacing them with a placeholder image."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: 9
)]
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
