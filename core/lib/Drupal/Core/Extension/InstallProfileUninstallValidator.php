<?php

declare(strict_types=1);

namespace Drupal\Core\Extension;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Ensures install profile can only be uninstalled if the modules are available.
 */
class InstallProfileUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Extension discovery that scans all folders except profiles.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected ExtensionDiscovery $noProfileExtensionDiscovery;

  public function __construct(
    TranslationInterface $string_translation,
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeExtensionList $themeExtensionList,
    protected string|false|null $installProfile,
    protected string $root,
    protected string $sitePath,
  ) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];

    // When there are modules installed that only exist in the install profile's
    // directory an install profile can not be uninstalled.
    if ($module === $this->installProfile) {
      $profile_name = $this->moduleExtensionList->get($module)->info['name'];

      $profile_only_modules = array_diff_key($this->moduleExtensionList->getAllInstalledInfo(), $this->getExtensionDiscovery()->scan('module'));
      // Remove the install profile as we're uninstalling it.
      unset($profile_only_modules[$module]);
      if (!empty($profile_only_modules)) {
        $reasons[] = $this->t("The install profile '@profile_name' is providing the following module(s): @profile_modules",
          ['@profile_name' => $profile_name, '@profile_modules' => implode(', ', array_keys($profile_only_modules))]);
      }

      $profile_only_themes = array_diff_key($this->themeExtensionList->getAllInstalledInfo(), $this->getExtensionDiscovery()->scan('theme'));
      if (!empty($profile_only_themes)) {
        $reasons[] = $this->t("The install profile '@profile_name' is providing the following theme(s): @profile_themes",
          ['@profile_name' => $profile_name, '@profile_themes' => implode(', ', array_keys($profile_only_themes))]);
      }
    }
    elseif (!empty($this->installProfile)) {
      $extension = $this->moduleExtensionList->get($module);
      // Ensure that the install profile does not depend on the module being
      // uninstalled.
      if (isset($extension->required_by[$this->installProfile])) {
        $profile_name = $this->moduleExtensionList->get($this->installProfile)->info['name'];
        $reasons[] = $this->t("The '@profile_name' install profile requires '@module_name'",
          ['@profile_name' => $profile_name, '@module_name' => $extension->info['name']]);
      }
    }

    return $reasons;
  }

  /**
   * Gets an extension discovery object that ignores the install profile.
   *
   * @return \Drupal\Core\Extension\ExtensionDiscovery
   *   An extension discovery object to look for extensions not in a profile
   *   directory.
   */
  protected function getExtensionDiscovery(): ExtensionDiscovery {
    if (!isset($this->noProfileExtensionDiscovery)) {
      // cspell:ignore CNKDSIUSYFUISEFCB
      $this->noProfileExtensionDiscovery = new ExtensionDiscovery($this->root, TRUE, ['_does_not_exist_profile_CNKDSIUSYFUISEFCB'], $this->sitePath);
    }
    return $this->noProfileExtensionDiscovery;
  }

}
