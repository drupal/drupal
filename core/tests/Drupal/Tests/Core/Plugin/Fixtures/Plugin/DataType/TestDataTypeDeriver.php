<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Fixtures\Plugin\DataType;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides a deriver that returns a plugin for the bare ID and one variant.
 */
class TestDataTypeDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (['', 'a_variant'] as $item) {
      $this->derivatives[$item] = $base_plugin_definition;
      $this->derivatives[$item]['provider'] = 'core';
    }
    return $this->derivatives;
  }

}
