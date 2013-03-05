<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Derivative\LanguageBlock.
 */

namespace Drupal\language\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides language switcher block plugin definitions for all languages.
 */
class LanguageBlock implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    include_once DRUPAL_ROOT . '/core/includes/language.inc';
    $info = language_types_info();
    foreach (language_types_get_configurable(FALSE) as $type) {
      $this->derivatives[$type] = $base_plugin_definition;
      $this->derivatives[$type]['admin_label'] = t('Language switcher (!type)', array('!type' => $info[$type]['name']));
      $this->derivatives[$type]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }

}
