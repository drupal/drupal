<?php

namespace Drupal\system\Form;

use Drupal\Core\Url;

/**
 * Provides helpers for enabling modules.
 *
 * @internal
 */
trait ModulesEnabledTrait {

  /**
   * Provides a confirmation message after modules have been enabled.
   *
   * @param string[] $modules
   *   Enabled module names, keyed by machine names.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   A confirmation message. If any of the enabled modules have permissions
   *   that the current user can manage, then include a link to the permissions
   *   page for those modules.
   */
  protected function modulesEnabledConfirmationMessage(array $modules) {
    $machine_names = implode(',', array_keys($modules));
    $url = Url::fromRoute('user.admin_permissions.module', ['modules' => $machine_names]);
    $module_names = implode(', ', array_values($modules));
    $t_args = ['%name' => $module_names, '%names' => $module_names];

    $message = $url->access($this->currentUser())
      ? $this->formatPlural(
        count($modules),
        'Module %name has been enabled. Configure <a href=":link">related permissions</a>.',
        '@count modules have been enabled: %names. Configure <a href=":link">related permissions</a>.',
        $t_args + [':link' => $url->toString()]
      )
      : $this->formatPlural(
        count($modules),
        'Module %name has been enabled.',
        '@count modules have been enabled: %names.',
        $t_args
      );

    return $message;
  }

}
