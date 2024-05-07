<?php

namespace Drupal\config_action_duplicate_test\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[ConfigAction(
  id: 'config_action_duplicate_test:config_test.dynamic:setProtectedProperty',
  admin_label: new TranslatableMarkup('A duplicate config action'),
  entity_types: ['config_test'],
)]
final class DuplicateConfigAction implements ConfigActionPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    // This method should never be called.
    throw new \BadMethodCallException();
  }

}
