<?php

namespace Drupal\locale;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the locale source services.
 */
class LocaleFetch {

  use StringTranslationTrait;

  public function __construct(
    protected readonly LocaleProjectStorageInterface $projectStorage,
    protected readonly ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Builds a batch to check, download and import project translations.
   *
   * @param array $projects
   *   Array of project names for which to update the translations. Defaults to
   *   all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   * @param array $options
   *   Array of import options. See locale_translate_batch_build().
   *
   * @return array
   *   Batch definition array.
   */
  public function batchUpdateBuild(array $projects = [], array $langcodes = [], array $options = []): array {
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
    $projects = $projects ?: array_keys($this->projectStorage->getProjects());
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());
    $status_options = $options;
    $status_options['finish_feedback'] = FALSE;

    $batch_builder = (new BatchBuilder())
      ->setFile($this->moduleExtensionList->getPath('locale') . '/locale.batch.inc')
      ->setTitle($this->t('Updating translations'))
      ->setErrorMessage($this->t('Error importing translation files'))
      ->setFinishCallback('locale_translation_batch_fetch_finished');
    // Check status of local and remote translation files.
    $operations = _locale_translation_batch_status_operations($projects, $langcodes, $status_options);
    // Download and import translations.
    $operations = array_merge($operations, $this->fetchOperations($projects, $langcodes, $options));
    array_walk($operations, function ($operation) use ($batch_builder) {
      call_user_func_array([$batch_builder, 'addOperation'], $operation);
    });

    return $batch_builder->toArray();
  }

  /**
   * Builds a batch to download and import project translations.
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   * @param array $options
   *   Array of import options. See locale_translate_batch_build().
   *
   * @return array
   *   Batch definition array.
   */
  public function batchFetchBuild(array $projects = [], array $langcodes = [], array $options = []): array {
    $projects = $projects ?: array_keys($this->projectStorage->getProjects());
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Updating translations.'))
      ->setErrorMessage($this->t('Error importing translation files'))
      ->setFile($this->moduleExtensionList->getPath('locale') . '/locale.batch.inc')
      ->setFinishCallback('locale_translation_batch_fetch_finished');
    $operations = $this->fetchOperations($projects, $langcodes, $options);
    array_walk($operations, function ($operation) use ($batch_builder) {
      call_user_func_array([$batch_builder, 'addOperation'], $operation);
    });
    return $batch_builder->toArray();
  }

  /**
   * Helper function to construct the batch operations to fetch translations.
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   * @param array $options
   *   Array of import options.
   *
   * @return array
   *   Array of batch operations.
   */
  protected function fetchOperations(array $projects, array $langcodes, array $options): array {
    $operations = [];

    foreach ($projects as $project) {
      foreach ($langcodes as $langcode) {
        if (locale_translation_use_remote_source()) {
          $operations[] = ['locale_translation_batch_fetch_download', [$project, $langcode]];
        }
        $operations[] = ['locale_translation_batch_fetch_import', [$project, $langcode, $options]];
      }
    }

    return $operations;
  }

}
