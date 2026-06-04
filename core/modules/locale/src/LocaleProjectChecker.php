<?php

declare(strict_types=1);

namespace Drupal\locale;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provide Locale Project Checker helper methods.
 */
class LocaleProjectChecker {

  use StringTranslationTrait;

  public function __construct(
    protected readonly LocaleSource $localeSource,
    protected readonly LocaleProjectRepository $localeProjectRepository,
    protected readonly LocaleFetch $localeFetch,
    protected readonly StateInterface $state,
    protected readonly TimeInterface $time,
    protected readonly MessengerInterface $messenger,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly TranslationInterface $translationManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Check for the latest release of project translations.
   *
   * @param array $projects
   *   Array of project names to check.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   */
  public function checkProjects(array $projects, array $langcodes = []): void {
    if (locale_translation_use_remote_source()) {
      // Retrieve the status of both remote and local translation sources by
      // using a batch process.
      $this->triggerBatch($projects, $langcodes);
    }
    else {
      // Retrieve and save the status of local translations only.
      $this->checkLocalProjects($projects, $langcodes);
      $this->localeSource->updateLastChecked();
    }
  }

  /**
   * Check and store the status and timestamp of local po files.
   *
   * Only po files in the local file system are checked. Any remote translation
   * files will be ignored.
   *
   * Projects may contain a server_pattern option containing a pattern of the
   * path to the po source files. If no server_pattern is defined, the default
   * translation directory is checked for the po file. When a server_pattern is
   * defined, the specified location is checked. The server_pattern can be set
   * in the module's .info.yml file or by using
   * hook_locale_translation_projects_alter().
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   */
  public function checkLocalProjects(array $projects, array $langcodes = []): void {
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    // For each project and each language we check if a local po file is
    // available. When found the source object is updated with the appropriate
    // type and timestamp of the po file.
    foreach ($projects as $project) {
      foreach ($langcodes as $langcode) {
        $source = $this->localeSource->loadSource($project, $langcode);
        $file = $this->localeSource->sourceCheckFile($source);
        if ($file) {
          $this->localeSource->saveSource($project, $langcode, LOCALE_TRANSLATION_LOCAL, $file);
        }
      }
    }
  }

  /**
   * Builds a batch to get the status of remote and local translation files.
   *
   * The batch process fetches the state of both local and (if configured)
   * remote translation files. The data of the most recent translation is
   * stored per project and per language. The data for this is maintained
   * by \Drupal\locale\LocaleSource.
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   */
  public function triggerBatch(array $projects, array $langcodes = []): void {
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());
    $options = LocaleDefaultOptions::updateOptions();

    $operations = $this->localeFetch->getStatusOperations($projects, $langcodes, $options);

    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Checking translations'))
      ->setErrorMessage($this->t('Error checking translation updates.'))
      ->setFinishCallback(self::class . ':batchFinished');

    foreach ($operations as $operation) {
      $batch_builder->addOperation(... $operation);
    }

    batch_set($batch_builder->toArray());
  }

  /**
   * Implements callback_batch_finished().
   *
   * Set result message.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   */
  public function batchFinished(bool $success, array $results): void {
    if ($success) {
      if (isset($results['failed_files'])) {
        if ($this->moduleHandler->moduleExists('dblog') && $this->currentUser->hasPermission('access site reports')) {
          $message = $this->translationManager->formatPlural(count($results['failed_files']), 'One translation file could not be checked. <a href=":url">See the log</a> for details.', '@count translation files could not be checked. <a href=":url">See the log</a> for details.', [':url' => Url::fromRoute('dblog.overview')->toString()]);
        }
        else {
          $message = $this->translationManager->formatPlural(count($results['failed_files']), 'One translation files could not be checked. See the log for details.', '@count translation files could not be checked. See the log for details.');
        }
        $this->messenger->addError($message);
      }
      if (isset($results['files'])) {
        $this->messenger->addStatus($this->translationManager->formatPlural(
          count($results['files']),
          'Checked available interface translation updates for one project.',
          'Checked available interface translation updates for @count projects.'
        ));
      }
      if (!isset($results['failed_files']) && !isset($results['files'])) {
        $this->messenger->addStatus($this->t('Nothing to check.'));
      }
      $this->localeSource->updateLastChecked();
    }
    else {
      $this->messenger->addError($this->t('An error occurred trying to check available interface translation updates.'));
    }
  }

}
