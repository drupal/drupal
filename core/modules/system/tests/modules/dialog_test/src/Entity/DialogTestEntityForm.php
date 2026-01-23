<?php

declare(strict_types=1);

namespace Drupal\dialog_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\dialog_test\DialogTestEntityEditForm;

/**
 * Define the dialog test entity form entity.
 */
#[ConfigEntityType(
  id: 'dialog_test_entity_form',
  label: new TranslatableMarkup('dialog test entity type'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'form' => [
      'add' => DialogTestEntityEditForm::class,
    ],
  ],
  config_export: [
    'id',
    'label',
  ],
)]
class DialogTestEntityForm extends ConfigEntityBundleBase {
}
