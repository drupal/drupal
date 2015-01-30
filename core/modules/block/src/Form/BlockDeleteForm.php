<?php

/**
 * @file
 * Contains \Drupal\block\Form\BlockDeleteForm.
 */

namespace Drupal\block\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 */
class BlockDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('block.admin_display');
  }

}
