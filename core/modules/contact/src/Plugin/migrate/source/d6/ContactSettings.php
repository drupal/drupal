<?php

/**
 * @file
 * Contains \Drupal\contact\Plugin\migrate\source\d6\ContactSettings.
 */

namespace Drupal\contact\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * @MigrateSource(
 *   id = "d6_contact_settings",
 *   source_provider = "contact"
 * )
 */
class ContactSettings extends Variable {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $default_category = $this->select('contact', 'c')
      ->fields('c', array('cid'))
      ->condition('selected', 1)
      ->execute()
      ->fetchField();
    return new \ArrayIterator(array($this->values() + array('default_category' => $default_category)));
  }

}
