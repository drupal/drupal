<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides helpers for enabling modules.
 *
 * @internal
 */
trait ModulesEnabledTrait {

  use StringTranslationTrait;

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  abstract protected function currentUser();

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
  protected function modulesEnabledConfirmationMessage(array $modules): PluralTranslatableMarkup {
    $machine_names = implode(',', array_keys($modules));
    $url = Url::fromRoute('user.admin_permissions.module', ['modules' => $machine_names]);
    $module_names = implode(', ', array_values($modules));
    $t_args = ['%name' => $module_names, '%names' => $module_names];

    if ($url->access($this->currentUser())) {
      return $this->formatPlural(
        count($modules),
        'Module %name has been enabled. Configure <a href=":link">related permissions</a>.',
        '@count modules have been enabled: %names. Configure <a href=":link">related permissions</a>.',
        $t_args + [':link' => $url->toString()]
      );
    }

    return $this->formatPlural(
      count($modules),
      'Module %name has been enabled.',
      '@count modules have been enabled: %names.',
      $t_args
    );
  }

  /**
   * Provides a fail message after attempt to install a module.
   *
   * @param string[] $modules
   *   Enabled module names, keyed by machine names.
   * @param \Drupal\Core\Config\PreExistingConfigException $exception
   *   Exception thrown if configuration with the same name already exists.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   A confirmation message. If any of the enabled modules have permissions
   *   that the current user can manage, then include a link to the permissions
   *   page for those modules.
   */
  protected function modulesFailToEnableMessage(array $modules, PreExistingConfigException $exception): PluralTranslatableMarkup {
    $config_objects = $exception->flattenConfigObjects($exception->getConfigObjects());
    return $this->formatPlural(
      count($config_objects),
      'Unable to install @extension, %config_names already exists in active configuration.',
      'Unable to install @extension, %config_names already exist in active configuration.',
      [
        '%config_names' => implode(', ', $config_objects),
        '@extension' => $modules['install'][$exception->getExtension()],
      ]);
  }

}
