<?php

declare(strict_types=1);

namespace Drupal\dialog_test;

use Drupal\Core\Entity\EntityForm;

/**
 * Base form for dialog test entity form edit form.
 */
class DialogTestEntityEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['dialog_test.settings'];
  }

}
