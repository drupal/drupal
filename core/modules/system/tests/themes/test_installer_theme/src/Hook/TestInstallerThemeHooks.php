<?php

declare(strict_types=1);

namespace Drupal\test_installer_theme\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form alter hooks for installer tests.
 */
class TestInstallerThemeHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_install_select_language_form_alter')]
  public function formInstallSelectLanguageFormAlter(array &$form): void {
    $form['function_name']['#markup'] = 'Added by custom installer theme.';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * The site configuration form is the last installer step. The profile has
   * installed its own themes by then, so this marker shows that the install
   * theme still has its hook implementations registered.
   */
  #[Hook('form_install_configure_form_alter')]
  public function formInstallConfigureFormAlter(array &$form): void {
    $form['installer_theme_marker'] = [
      '#markup' => 'Added by custom installer theme on the last step.',
      '#weight' => -100,
    ];
  }

}
