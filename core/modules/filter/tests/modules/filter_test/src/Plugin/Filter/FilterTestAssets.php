<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to attach assets.
 */
#[Filter(
  id: "filter_test_assets",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Does not change content; attaches assets."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE
)]
class FilterTestAssets extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $result->addAttachments([
      'library' => [
        'filter/caption',
      ],
    ]);
    return $result;
  }

}
