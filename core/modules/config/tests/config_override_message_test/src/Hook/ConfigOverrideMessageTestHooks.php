<?php

declare(strict_types=1);

namespace Drupal\config_override_message_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_override_message_test.
 */
class ConfigOverrideMessageTestHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_system_site_information_settings_alter')]
  public function formSystemSiteInformationSettingsAlter(array &$form, FormStateInterface $form_state, string $form_id) : void {
    // Set a weight to a negative amount to ensure the config overrides message
    // is above it.
    $form['site_information']['#weight'] = -5;
  }

}
