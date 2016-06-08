<?php

namespace Drupal\contact\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * @MigrateSource(
 *   id = "contact_settings",
 *   source_provider = "contact"
 * )
 */
class ContactSettings extends Variable {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $default_category = $this->select('contact', 'c')
      ->fields('c', ['cid'])
      ->condition('c.selected', 1)
      ->execute()
      ->fetchField();
    return new \ArrayIterator([$this->values() + ['default_category' => $default_category]]);
  }

}
