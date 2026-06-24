<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\locale\File\LocaleFileManager;
use Drupal\locale\LocaleConfigBatch;
use Drupal\locale\LocaleDefaultOptions;
use Drupal\locale\LocaleFetch;
use Drupal\locale\LocaleProjectRepository;
use Drupal\locale\LocaleSource;

/**
 * Extension hook implementations for locale.
 */
class LocaleExtensionHooks {

  public function __construct(
    protected readonly LocaleConfigBatch $localeConfigBatch,
    protected readonly LocaleFetch $localeFetch,
    protected readonly LocaleProjectRepository $localeProjectRepository,
    protected readonly LocaleFileManager $localeFileManager,
    protected readonly LocaleSource $localeSource,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_modules_installed().
   *
   * Implements hook_themes_installed().
   */
  #[Hook('modules_installed')]
  #[Hook('themes_installed')]
  public function extensionsInstalled(array $extensions): void {
    // Skip running the translation imports if in the installer,
    // because it would break out of the installer flow. We have
    // built-in support for translation imports in the installer.
    if (!InstallerKernel::installationAttempted() && locale_translatable_language_list()) {
      if ($this->configFactory->get('locale.settings')->get('translation.import_enabled')) {

        // Update the list of translatable projects and start the import batch.
        // Only when new projects are added the update batch will be triggered.
        // Not each enabled module will introduce a new project. E.g. sub
        // modules.
        $projects = array_keys($this->localeProjectRepository->buildProjects());
        if ($extensions = array_intersect($extensions, $projects)) {
          // Get translation status of the projects, download and update
          // translations.
          $options = LocaleDefaultOptions::updateOptions();
          $batch = $this->localeFetch->buildUpdateBatch($extensions, [], $options);
          batch_set($batch);
        }
      }

      // Construct a batch to update configuration for all components.
      // Installing this component may have installed configuration from any
      // number of other components. Do this even if import is not enabled
      // because parsing new configuration may expose new source strings.
      if ($batch = $this->localeConfigBatch->buildBatch([], [], [], TRUE)) {
        batch_set($batch);
      }
    }
  }

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall(string $module): void {
    $this->deleteTranslationHistory([$module]);
  }

  /**
   * Implements hook_themes_uninstalled().
   */
  #[Hook('themes_uninstalled')]
  public function themesUninstalled(array $themes): void {
    $this->deleteTranslationHistory($themes);
  }

  /**
   * Delete translation history of modules and themes.
   *
   * Only the translation history is removed, not the source strings or
   * translations. This is not possible because strings are shared between
   * modules and we have no record of which string is used by which module.
   *
   * @param array $extensions
   *   An array of arrays of component (theme and/or module) names to import
   *   translations for, indexed by type.
   */
  protected function deleteTranslationHistory(array $extensions): void {

    if (locale_translatable_language_list()) {
      // Only when projects are removed, the translation files and records will
      // be deleted. Not every uninstalled module will remove a project, e.g.,
      // sub modules.
      $projects = array_keys($this->localeProjectRepository->getAll());
      if ($extensions = array_intersect($extensions, $projects)) {
        // Remove translation files.
        $this->localeFileManager->deleteTranslationFiles($extensions, []);

        // Remove translatable projects.
        $this->localeProjectRepository->deleteMultiple($extensions);

        // Clear the translation status.
        $this->localeSource->deleteSources($extensions);
      }
    }
  }

}
