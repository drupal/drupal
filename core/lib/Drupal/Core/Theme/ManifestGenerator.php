<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides content and data for the manifest.json file.
 *
 * This data is based on site, on theme configuration and is language-specific.
 */
class ManifestGenerator implements ManifestGeneratorInterface {

  /**
   * The current language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $currentLanguage;

  /**
   * The theme config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $themeConfig;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ManifestGenerator constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(LanguageManagerInterface $languageManager, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler) {
    $this->currentLanguage = $languageManager->getCurrentLanguage();
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Generate contents for the manifest.json for a specified theme.
   *
   * @param string $themeName
   *   The theme name.
   *
   * @return \Drupal\Core\Theme\Manifest
   *   The manifest file contents, complete with cache metadata.
   */
  public function generateManifest($themeName) : Manifest {
    $this->themeConfig = $this->loadThemeConfig($themeName);
    return $this->doGenerateManifest();
  }

  /**
   * Generate the array of contents for the manifest.json file.
   *
   * @return \Drupal\Core\Theme\Manifest
   *   Data to generate the manifest file.
   */
  protected function doGenerateManifest() : Manifest {
    $site_configuration = $this->configFactory->get('system.site');

    // Icons: Omit blank/optional (key/value) pairs.
    $icons = (array) $this->themeConfig->get('manifest.icons') ?: [];
    foreach ($icons as $icon_index => $icon) {
      foreach ($icon as $key => $value) {
        if (empty($value)) {
          unset($icons[$icon_index][$key]);
        }
      }
      if (empty($icons[$icon_index])) {
        unset($icons[$icon_index]);
      }
    }

    $data = [
      'background_color' => $this->themeConfig->get('manifest.background_color'),
      'dir' => $this->currentLanguage->getDirection(),
      'display' => $site_configuration->get('manifest.display'),
      'lang' => $this->currentLanguage->getId(),
      'icons' => $icons,
      'orientation' => $this->themeConfig->get('manifest.orientation'),
      'short_name' => $site_configuration->get('manifest.short_name'),
      'start_url' => $site_configuration->get('manifest.start_url'),
      'theme_color' => $this->themeConfig->get('manifest.theme_color'),
    ];

    // Manifest: Omit blank/optional (key,value) pairs.
    foreach ($data as $key => $value) {
      if (empty($value)) {
        unset($data[$key]);
      }
    }

    // "manifest_version" and "name" are mandatory keys.
    $data['name'] = $site_configuration->get('manifest.name');
    $data['manifest_version'] = $site_configuration->get('manifest.manifest_version');

    $manifest_data = new Manifest($data);
    $manifest_data->addCacheableDependency($site_configuration);
    $manifest_data->addCacheableDependency($this->themeConfig);

    $this->moduleHandler->alter('manifest', $manifest_data);

    return $manifest_data;
  }

  /**
   * Load the theme configuration.
   *
   * @param string $themeName
   *   The theme name to generate data for.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration of the theme.
   */
  protected function loadThemeConfig($themeName) : ImmutableConfig {
    $theme_specific_config = $this->configFactory->get($themeName . '.settings');
    if (!empty($theme_specific_config->get('manifest'))) {
      return $theme_specific_config;
    }
    return $this->configFactory->get('system.theme.global');
  }

}
