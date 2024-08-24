<?php

declare(strict_types=1);

namespace Drupal\entity_test;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides the entity_test delete form.
 *
 * @internal
 */
class EntityTestDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

}
