<?php

namespace Drupal\entity_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity_reference_custom_cache_tag' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_custom_cache_tag",
 *   label = @Translation("Custom cache tag"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityTestReferenceCustomCacheTagFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return ['#cache' => ['tags' => ['custom_cache_tag']]];
  }

}
