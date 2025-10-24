<?php

declare(strict_types=1);

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageException;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeSettings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Default implementation of the theme settings provider service.
 */
class ThemeSettingsProvider {

  /**
   * An array of default theme features.
   *
   * @see \Drupal\Core\Extension\ThemeExtensionList::$defaults
   */
  public const array DEFAULT_THEME_FEATURES = [
    'favicon',
    'logo',
    'node_user_picture',
    'comment_user_picture',
    'comment_user_verification',
  ];

  /**
   * Builds a new service instance.
   */
  public function __construct(
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly ThemeInitializationInterface $themeInitialization,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    #[Autowire(service: 'cache.memory')]
    protected readonly CacheBackendInterface $memoryCache,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $setting_name, ?string $theme = NULL): mixed {
    // If no key is given, use the current theme if we can determine it.
    if (!isset($theme)) {
      $theme = $this->themeManager->getActiveTheme()->getName();
    }

    $cid = 'theme_settings:' . $theme;
    $cacheItem = $this->memoryCache->get($cid);
    if ($cacheItem) {
      /** @var \Drupal\Core\Theme\ThemeSettings $themeSettings */
      $themeSettings = $cacheItem->data;
    }
    else {
      $themeSettings = $this->buildThemeSettings($theme);
      $this->memoryCache->set($cid, $themeSettings, tags: [
        'config:core.extension',
        'config:system.theme.global',
        sprintf('config:%s.settings', $theme),
      ]);
    }
    return $themeSettings->get($setting_name);
  }

  /**
   * Build a ThemeSettings object for a given theme.
   */
  protected function buildThemeSettings(string $theme): ThemeSettings {
    // Create a theme settings object.
    $themeSettings = new ThemeSettings($theme);
    // Get the global settings from configuration.
    $themeSettings->setData($this->configFactory->get('system.theme.global')->get());

    // Get the values for the theme-specific settings from the .info.yml files
    // of the theme and all its base themes.
    $themes = $this->themeHandler->listInfo();
    if (isset($themes[$theme])) {
      $themeObject = $themes[$theme];

      // Retrieve configured theme-specific settings, if any.
      try {
        if ($themeConfigSettings = $this->configFactory->get($theme . '.settings')->get()) {
          $themeSettings->merge($themeConfigSettings);
        }
      }
      catch (StorageException) {
      }

      // If the theme does not support a particular feature, override the
      // global setting and set the value to NULL.
      if (!empty($themeObject->info['features'])) {
        foreach (self::DEFAULT_THEME_FEATURES as $feature) {
          if (!in_array($feature, $themeObject->info['features'])) {
            $themeSettings->set('features.' . $feature, NULL);
          }
        }
      }

      // Generate the path to the logo image.
      if ($themeSettings->get('logo.use_default')) {
        $logo = $this->themeInitialization->getActiveThemeByName($theme)->getLogo();
        $themeSettings->set('logo.url', $this->fileUrlGenerator->generateString($logo));
      }
      elseif ($logo_path = $themeSettings->get('logo.path')) {
        $themeSettings->set('logo.url', $this->fileUrlGenerator->generateString($logo_path));
      }

      // Generate the path to the favicon.
      if ($themeSettings->get('features.favicon')) {
        $faviconPath = $themeSettings->get('favicon.path');
        if ($themeSettings->get('favicon.use_default')) {
          if (file_exists($favicon = $themeObject->getPath() . '/favicon.ico')) {
            $themeSettings->set('favicon.url', $this->fileUrlGenerator->generateString($favicon));
          }
          else {
            $themeSettings->set('favicon.url', $this->fileUrlGenerator->generateString('core/misc/favicon.ico'));
          }
        }
        elseif ($faviconPath) {
          $themeSettings->set('favicon.url', $this->fileUrlGenerator->generateString($faviconPath));
        }
        else {
          $themeSettings->set('features.favicon', FALSE);
        }
      }
    }
    return $themeSettings;
  }

}
