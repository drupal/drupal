<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Config translation hook implementations for node.
 */
class NodeConfigTranslationHooks {

  /**
   * Implements hook_config_translation_info_alter().
   */
  #[Hook('config_translation_info_alter')]
  public function configTranslationInfoAlter(&$info): void {
    $info['node_type']['class'] = 'Drupal\node\ConfigTranslation\NodeTypeMapper';
  }

}
