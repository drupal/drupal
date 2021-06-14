<?php

namespace Drupal\block_content\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a confirmation form for deleting a custom block entity.
 *
 * @internal
 */
class BlockContentDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $instances = $this->entity->getInstances();
    if (!empty($instances)) {
      return $this->formatPlural(count($instances), 'This will also remove 1 placed block instance. This action cannot be undone.', 'This will also remove @count placed block instances. This action cannot be undone.');
    }
    return parent::getDescription();
  }

}
