<?php

namespace Drupal\Core\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extend DrupalKernel to handle force some kernel behaviors.
 */
class InstallerKernel extends DrupalKernel {

  /**
   * The theme used for install, update and early maintenance pages.
   *
   * A distribution profile can request a different theme with the
   * distribution.install.theme key in its info file.
   */
  public const INSTALL_THEME = 'default_admin';

  /**
   * The extensions from the last container compilation.
   */
  protected array $compiledExtensions = [];

  /**
   * {@inheritdoc}
   */
  protected function initializeContainer() {
    // Ensure the InstallerKernel's container is not dumped.
    $this->allowDumping = FALSE;

    // During installation, initializeContainer() can be called multiple times
    // without any module changes in between. Since allowDumping is FALSE, no
    // cached container exists, so the parent will always recompile.
    $extensions = $this->getExtensions();
    if (isset($this->container)
      && count($extensions['module'] ?? []) === count($this->compiledExtensions['module'] ?? [])
      && count($extensions['theme'] ?? []) === count($this->compiledExtensions['theme'] ?? [])
      && !array_diff_key($extensions['module'] ?? [], $this->compiledExtensions['module'] ?? [])
      && !array_diff_key($extensions['theme'] ?? [], $this->compiledExtensions['theme'] ?? [])
    ) {
      $this->containerNeedsRebuild = FALSE;
      return $this->container;
    }
    else {
      $this->containerNeedsRebuild = TRUE;
      $this->compiledExtensions = $extensions;
      return parent::initializeContainer();
    }
  }

  /**
   * Reset the bootstrap config storage.
   *
   * Use this from a database driver runTasks() if the method overrides the
   * bootstrap config storage. Normally the bootstrap config storage is not
   * re-instantiated during a single install request. Most drivers will not
   * need this method.
   *
   * @see \Drupal\Core\Database\Install\Tasks::runTasks()
   */
  public function resetConfigStorage() {
    $this->configStorage = NULL;
  }

  /**
   * Returns the active configuration storage used during early install.
   *
   * This override changes the visibility so that the installer can access
   * config storage before the container is properly built.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getConfigStorage() {
    return parent::getConfigStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallProfile() {
    global $install_state;
    if ($install_state && empty($install_state['installation_finished'])) {
      // If the profile has been selected return it.
      if (isset($install_state['parameters']['profile'])) {
        $profile = $install_state['parameters']['profile'];
      }
      else {
        $profile = NULL;
      }
    }
    else {
      $profile = parent::getInstallProfile();
    }
    return $profile;
  }

  /**
   * Returns TRUE if a Drupal installation is currently being attempted.
   *
   * @return bool
   *   TRUE if the installation is currently being attempted.
   */
  public static function installationAttempted() {
    // This cannot rely on the MAINTENANCE_MODE constant, since that would
    // prevent tests from using the non-interactive installer, in which case
    // Drupal only happens to be installed within the same request, but
    // subsequently executed code does not involve the installer at all.
    // @see install_drupal()
    return isset($GLOBALS['install_state']) && empty($GLOBALS['install_state']['installation_finished']);
  }

  /**
   * {@inheritdoc}
   */
  protected function attachSynthetic(ContainerInterface $container): void {
    parent::attachSynthetic($container);

    // Reset any existing container in order to avoid holding on to old object
    // references, otherwise memory usage grows exponentially with each rebuild
    // when multiple modules are being installed.
    // @todo Move this to the parent class after https://www.drupal.org/i/2066993
    $this->container?->reset();
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensions(): array {
    $extensions = parent::getExtensions() ?: [];
    if (!static::installationAttempted()) {
      return $extensions;
    }

    // Ensure that the System module is always available to the installer.
    $extensions['module']['system'] ??= 0;
    $profile = $this->installGetProfile();
    if ($profile) {
      $extensions['profile'] = $profile->getName();
      if (!isset($extensions['module'][$profile->getName()])) {
        $extensions['module'][$profile->getName()] = 1000;
      }
    }
    // Ensure that the install theme is always available to the installer.
    foreach ($this->getInstallThemes($profile) as $theme) {
      $extensions['theme'][$theme] ??= 0;
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function updateThemes(array $register_themes = []): void {
    if (static::installationAttempted()) {
      // Installing a profile or a recipe replaces the theme list with the
      // themes that were just installed, which do not include the install
      // theme. Theme hook implementations are collected from the
      // container.themes parameter, so dropping the install theme from the
      // list removes its hooks while it is still rendering the remaining
      // installer pages. Keep it registered until the installation is done.
      // @see \Drupal\Core\Hook\ThemeHookCollectorPass
      // @see _drupal_maintenance_theme()
      foreach ($this->getInstallThemes($this->installGetProfile()) as $theme) {
        if (isset($register_themes[$theme])) {
          continue;
        }
        $extension = $this->themeExtensions($theme);
        if ($extension) {
          $register_themes[$theme] = $extension;
        }
      }
    }
    parent::updateThemes($register_themes);
  }

  /**
   * Gets the theme that renders installer pages, with its base themes.
   *
   * @param \Drupal\Core\Extension\Extension|false|null $profile
   *   The profile being installed, or NULL if none has been selected yet, or
   *   FALSE if the installation uses no profile.
   *
   * @return string[]
   *   The name of the install theme, followed by the names of its base themes.
   */
  private function getInstallThemes(null|false|Extension $profile): array {
    if (!$profile) {
      // Without a profile there is no distribution that could request another
      // theme.
      return [static::INSTALL_THEME];
    }
    $theme = $profile->info['distribution']['install']['theme'] ?? static::INSTALL_THEME;
    if ($theme === static::INSTALL_THEME) {
      // The default install theme declares "base theme: false", so scanning
      // the file system for base themes is not needed.
      return [$theme];
    }
    return array_merge([$theme], $this->getBaseThemes($profile, $theme));
  }

  /**
   * Gets the base themes for a given theme.
   *
   * @param \Drupal\Core\Extension\Extension $profile
   *   The profile being installed.
   * @param string $theme
   *   The theme for installation.
   *
   * @return string[]
   *   A list of base themes.
   */
  private function getBaseThemes(Extension $profile, string $theme): array {
    $base_themes = [];

    // Find all the available themes.
    $listing = new ExtensionDiscovery($this->root);
    $listing->setProfileDirectories([$profile->getName() => $profile->getPath()]);
    $themes = $listing->scan('theme');

    $info_parser = new InfoParser($this->root);
    $theme_info = $info_parser->parse($themes[$theme]->getPathname());
    $base_theme = $theme_info['base theme'] ?? FALSE;

    while ($base_theme) {
      $base_themes[] = $base_theme;
      $theme_info = $info_parser->parse($themes[$base_theme]->getPathname());
      $base_theme = $theme_info['base theme'] ?? FALSE;
    }

    return $base_themes;
  }

  /**
   * Gets the profile to be installed.
   *
   * @return string|null|\Drupal\Core\Extension\Extension
   *   Returns NULL if no profile was selected or FALSE if the site has no
   *   profile, or the profile extension object with the profile info added.
   *
   * @see _install_select_profile()
   */
  private function installGetProfile(): null|false|Extension {
    global $install_state;

    $profile = NULL;

    if (empty($install_state['profiles'])) {
      throw new \RuntimeException('No profiles found.');
    }

    // If there is only one profile available it will always be the one
    // selected.
    if (count($install_state['profiles']) == 1) {
      $profile = reset($install_state['profiles']);
    }
    // If a valid profile has already been selected, return the selection.
    if (array_key_exists('profile', $install_state['parameters'])) {
      $profile = $install_state['parameters']['profile'];
      if ($profile && isset($install_state['profiles'][$profile])) {
        $profile = $install_state['profiles'][$profile];
      }
    }

    // Not using a profile.
    if ($profile === FALSE) {
      return $profile;
    }

    $info_parser = new InfoParser($this->root);

    if ($profile instanceof Extension) {
      $profile->info = $info_parser->parse($profile->getPathname());
      return $profile;
    }

    $visible_profiles = [];
    // If any of the profiles are distribution profiles, select the first one.
    foreach ($install_state['profiles'] as $profile) {
      $profile->info = $info_parser->parse($profile->getPathname());
      if (!empty($profile->info['distribution'])) {
        return $profile;
      }
      if (!isset($profile->info['hidden']) || !$profile->info['hidden']) {
        $visible_profiles[] = $profile;
      }
    }
    // If there is only one visible profile, select it.
    if (count($visible_profiles) == 1) {
      return $visible_profiles[0];
    }

    return NULL;
  }

}
