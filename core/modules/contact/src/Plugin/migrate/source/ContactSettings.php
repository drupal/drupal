<?php

namespace Drupal\contact\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Drupal 6/7 contact settings source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "contact_settings",
 *   source_module = "contact"
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
