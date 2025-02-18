<?php

namespace Drupal\phpass\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for phpass.
 */
class PhpassHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.phpass':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Password Compatibility module provides the password checking algorithm for user accounts created with Drupal prior to version 10.1.0. For more information, see the <a href=":phpass">online documentation for the Password Compatibility module</a>.', [
          ':phpass' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/password-compatibility-module',
        ]) . '</p>';
        $output .= '<p>' . $this->t('Drupal 10.1.0 and later use a different algorithm to compute the hashed password. This provides better security against brute-force attacks. The hashed passwords are different from the ones computed with Drupal versions before 10.1.0.') . '</p>';
        $output .= '<p>' . $this->t('When the Password Compatibility module is installed, a user can log in with a username and password created before Drupal 10.1.0. The first time these credentials are used, a new hash is computed and saved. From then on, the user will be able to log in with the same username and password whether or not this module is installed.') . '</p>';
        $output .= '<p>' . $this->t('Passwords created before Drupal 10.1.0 <strong>will not work</strong> unless they are used at least once while this module is installed. Make sure that you can log in before uninstalling this module.') . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_modules_uninstall_confirm_form.
   */
  #[Hook('form_system_modules_uninstall_confirm_form_alter')]
  public function formSystemModulesUninstallConfirmFormAlter(array &$form, FormStateInterface $form_state) : void {
    $modules = \Drupal::keyValueExpirable('modules_uninstall')->get(\Drupal::currentUser()->id());
    if (!in_array('phpass', $modules)) {
      return;
    }
    \Drupal::messenger()->addWarning($this->t('Make sure that you can log in before uninstalling the Password Compatibility module. For more information, see the <a href=":phpass">online documentation for the Password Compatibility module</a>.', [
      ':phpass' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/password-compatibility-module',
    ]));
  }

}
