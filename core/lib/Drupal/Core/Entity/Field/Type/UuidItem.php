<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\UuidItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Component\Uuid\Uuid;

/**
 * Defines the 'uuid_field' entity field item.
 *
 * The field uses a newly generated UUID as default value.
 */
class UuidItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to one field item with a generated UUID.
    $uuid = new Uuid();
    $this->setValue(array('value' => $uuid->generate()), $notify);
    return $this;
  }
}
