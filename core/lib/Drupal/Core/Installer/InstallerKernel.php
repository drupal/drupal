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
   * {@inheritdoc}
   */
  protected function initializeContainer() {
    // Always force a container rebuild.
    $this->containerNeedsRebuild = TRUE;
    // Ensure the InstallerKernel's container is not dumped.
    $this->allowDumping = FALSE;
    $container = parent::initializeContainer();
    return $container;
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
    if ($profile = $this->installGetProfile()) {
      $extensions['profile'] = $profile->getName();
      if (!isset($extensions['module'][$profile->getName()])) {
        $extensions['module'][$profile->getName()] = 1000;
      }
      $theme = $profile->info['distribution']['install']['theme'] ?? 'claro';
      $extensions['theme'][$theme] ??= 0;

      if ($theme !== 'claro') {
        // Need to check for base themes.
        foreach ($this->getBaseThemes($profile, $theme) as $base_theme) {
          $extensions['theme'][$base_theme] ??= 0;
        }
      }
    }
    // Ensure that the default theme is always available to the installer.
    else {
      $extensions['theme']['claro'] ??= 0;
    }
    return $extensions;
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
