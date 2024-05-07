<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Action\Exists;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @internal
 *   This API is experimental.
 */
final class EntityCreateDeriver extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // These derivatives apply to all entity types.
    $base_plugin_definition['entity_types'] = ['*'];

    $this->derivatives['ensure_exists'] = $base_plugin_definition + ['constructor_args' => ['exists' => Exists::ReturnEarlyIfExists]];
    $this->derivatives['ensure_exists']['admin_label'] = $this->t('Ensure entity exists');

    $this->derivatives['create'] = $base_plugin_definition + ['constructor_args' => ['exists' => Exists::ErrorIfExists]];
    $this->derivatives['create']['admin_label'] = $this->t('Entity create');

    return $this->derivatives;
  }

}
