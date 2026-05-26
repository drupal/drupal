<?php

namespace Drupal\locale;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\locale\File\LocaleFile;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Provides the locale import batch services.
 */
class LocaleImportBatch {

  use StringTranslationTrait;

  public function __construct(
    protected readonly FileSystemInterface $fileSystem,
    protected readonly TimeInterface $time,
    protected readonly LocaleConfigManager $localeConfigManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly MessengerInterface $messenger,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly TranslationInterface $translation,
    protected readonly LocaleConfigBatch $localeConfigBatch,
    protected readonly CurrentImportStorage $currentImportStorage,
    /**
     * @var \Closure(): \Psr\Log\LoggerInterface
     */
    #[AutowireServiceClosure('logger.channel.locale')]
    protected readonly \Closure $logger,
  ) {}

  /**
   * Build a locale batch from an array of files.
   *
   * @param array $files
   *   Array of file objects to import.
   * @param array $options
   *   An array with options that can have the following elements:
   *   - 'langcode': The language code. Optional, defaults to NULL, which means
   *     that the language will be detected from the name of the files.
   *   - 'overwrite_options': Overwrite options array as defined in
   *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
   *   - 'customized': Flag indicating whether the strings imported from $file
   *     are customized translations or come from a community source. Use
   *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
   *     LOCALE_NOT_CUSTOMIZED.
   *   - 'finish_feedback': Whether or not to give feedback to the user when the
   *     batch is finished. Optional, defaults to TRUE.
   *
   * @return array|bool
   *   A batch structure or FALSE if $files was empty.
   */
  public function buildBatch(array $files, array $options): array|bool {
    $options += [
      'overwrite_options' => [],
      'customized' => LOCALE_NOT_CUSTOMIZED,
      'finish_feedback' => TRUE,
    ];
    if (count($files)) {
      $batch_builder = (new BatchBuilder())
        ->setTitle($this->t('Importing interface translations'))
        ->setErrorMessage($this->t('Error importing interface translations'));
      foreach ($files as $file) {
        // We call self::batchImport for every batch operation.
        $batch_builder->addOperation(self::class . ':batchImport', [$file, $options]);
      }
      // Save the translation status of all files.
      $batch_builder->addOperation(self::class . ':batchSave', []);

      // Add a final step to refresh JavaScript and configuration strings.
      $batch_builder->addOperation(self::class . ':batchRefresh', []);

      if ($options['finish_feedback']) {
        $batch_builder->setFinishCallback(self::class . ':batchFinished');
      }
      return $batch_builder->toArray();
    }
    return FALSE;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Perform interface translation import.
   *
   * @param \Drupal\locale\File\LocaleFile $file
   *   A LocaleFile of the gettext file to be imported. The LocaleFile must
   *   contain a language parameter (other than
   *   LanguageInterface::LANGCODE_NOT_SPECIFIED). This is used as the language
   *   of the import.
   * @param array $options
   *   An array with options that can have the following elements:
   *   - 'langcode': The language code.
   *   - 'overwrite_options': Overwrite options array as defined in
   *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
   *   - 'customized': Flag indicating whether the strings imported from $file
   *     are customized translations or come from a community source. Use
   *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
   *     LOCALE_NOT_CUSTOMIZED.
   *   - 'message': Alternative message to display during import. Note, this
   *     must be sanitized text.
   * @param array|\ArrayAccess $context
   *   Contains a list of files imported.
   */
  public function batchImport(LocaleFile $file, array $options, array|\ArrayAccess &$context): void {
    // Merge the default values in the $options array.
    $options += [
      'overwrite_options' => [],
      'customized' => LOCALE_NOT_CUSTOMIZED,
    ];

    if (isset($file->langcode) && $file->langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED) {

      try {
        if (empty($context['sandbox'])) {
          $context['sandbox']['parse_state'] = [
            'filesize' => filesize($this->fileSystem->realpath($file->uri)),
            'chunk_size' => 200,
            'seek' => 0,
          ];
        }
        // Update the seek and the number of items in the $options array.
        $options['seek'] = $context['sandbox']['parse_state']['seek'];
        $options['items'] = $context['sandbox']['parse_state']['chunk_size'];
        $report = Gettext::fileToDatabase($file, $options);
        // If not yet finished with reading, mark progress based on size and
        // position.
        if ($report['seek'] < filesize($file->uri)) {

          $context['sandbox']['parse_state']['seek'] = $report['seek'];
          // Maximize the progress bar at 95% before completion, the batch API
          // could trigger the end of the operation before file reading is done,
          // because of floating point inaccuracies. See
          // https://www.drupal.org/node/1089472.
          $context['finished'] = min(0.95, $report['seek'] / filesize($file->uri));
          if (isset($options['message'])) {
            $context['message'] = $this->t('@message (@percent%).', [
              '@message' => $options['message'],
              '@percent' => (int) ($context['finished'] * 100),
            ]);
          }
          else {
            $context['message'] = $this->t('Importing translation file: %filename (@percent%).', [
              '%filename' => $file->filename,
              '@percent' => (int) ($context['finished'] * 100),
            ]);
          }
        }
        else {
          // We are finished here.
          $context['finished'] = 1;

          // Store the file data for processing by the next batch operation.
          $file->timestamp = filemtime($file->uri);
          $context['results']['files'][$file->uri] = $file;
          $context['results']['languages'][$file->uri] = $file->langcode;
        }

        // Add the reported values to the statistics for this file.
        // Each import iteration reports statistics in an array. The results of
        // each iteration are added and merged here and stored per file.
        if (!isset($context['results']['stats']) || !isset($context['results']['stats'][$file->uri])) {
          $context['results']['stats'][$file->uri] = [];
        }
        foreach ($report as $key => $value) {
          if (is_numeric($report[$key])) {
            if (!isset($context['results']['stats'][$file->uri][$key])) {
              $context['results']['stats'][$file->uri][$key] = 0;
            }
            $context['results']['stats'][$file->uri][$key] += $report[$key];
          }
          elseif (is_array($value)) {
            $context['results']['stats'][$file->uri] += [$key => []];
            $context['results']['stats'][$file->uri][$key] = array_merge($context['results']['stats'][$file->uri][$key], $value);
          }
        }
      }
      catch (\Exception) {
        // Import failed. Store the data of the failing file.
        $context['results']['failed_files'][] = $file;
        ($this->logger)()->notice('Unable to import translations file: @file', ['@file' => $file->uri]);
      }
    }
  }

  /**
   * Implements callback_batch_operation().
   *
   * Save data of imported files.
   *
   * @param array|\ArrayAccess $context
   *   Contains a list of imported files.
   */
  public function batchSave(array|\ArrayAccess &$context): void {
    if (isset($context['results']['files'])) {
      $request_time = $this->time->getRequestTime();
      foreach ($context['results']['files'] as $file) {
        // Update the file history if both project and version are known. This
        // table is used by the automated translation update function which
        // tracks translation status of module and themes in the system. Other
        // translation files are not tracked and are therefore not stored in
        // this table.
        if ($file->project && $file->version) {
          $file->last_checked = $request_time;
          $currentImport = CurrentImport::createFromFile($file);
          $currentImport->last_checked = $request_time;
          $this->currentImportStorage->save($currentImport);
        }
      }
      $context['message'] = $this->t('Translations imported.');
    }
  }

  /**
   * Implements callback_batch_operation().
   *
   * Refreshes translations after importing strings.
   *
   * @param array|\ArrayAccess $context
   *   Contains a list of strings updated and information about the progress.
   */
  public function batchRefresh(array|\ArrayAccess &$context): void {
    if (!isset($context['sandbox']['refresh'])) {
      $strings = $langcodes = [];
      if (isset($context['results']['stats'])) {
        // Get list of unique string identifiers and language codes updated.
        $langcodes = array_unique(array_values($context['results']['languages']));
        foreach ($context['results']['stats'] as $report) {
          $strings[] = $report['strings'];
        }
        $strings = array_merge(...$strings);
      }
      if ($strings) {
        // Initialize multi-step string refresh.
        $context['message'] = $this->t('Updating translations for JavaScript and default configuration.');
        $context['sandbox']['refresh']['strings'] = array_unique($strings);
        $context['sandbox']['refresh']['languages'] = $langcodes;
        $context['sandbox']['refresh']['names'] = [];
        $context['results']['stats']['config'] = 0;
        $context['sandbox']['refresh']['count'] = count($strings);

        // We will update strings on later steps.
        $context['finished'] = 0;
      }
      else {
        $context['finished'] = 1;
      }
    }
    elseif ($name = array_shift($context['sandbox']['refresh']['names'])) {
      // Refresh all languages for one object at a time.
      $count = $this->localeConfigManager->updateConfigTranslations([$name], $context['sandbox']['refresh']['languages']);
      $context['results']['stats']['config'] += $count;
      // Inherit finished information from the "parent" string lookup step so
      // visual display of status will make sense.
      $context['finished'] = $context['sandbox']['refresh']['names_finished'];
      $context['message'] = $this->t('Updating default configuration (@percent%).', ['@percent' => (int) ($context['finished'] * 100)]);
    }
    elseif (!empty($context['sandbox']['refresh']['strings'])) {
      // Not perfect but will give some indication of progress.
      $context['finished'] = 1 - count($context['sandbox']['refresh']['strings']) / $context['sandbox']['refresh']['count'];
      // Pending strings, refresh 100 at a time, get next pack.
      $next = array_slice($context['sandbox']['refresh']['strings'], 0, 100);
      array_splice($context['sandbox']['refresh']['strings'], 0, count($next));
      // Clear cache and force refresh of JavaScript translations.
      _locale_refresh_translations($context['sandbox']['refresh']['languages'], $next);
      // Check whether we need to refresh configuration objects.
      if ($names = $this->localeConfigManager->getStringNames($next)) {
        $context['sandbox']['refresh']['names_finished'] = $context['finished'];
        $context['sandbox']['refresh']['names'] = $names;
      }
    }
    else {
      $context['message'] = $this->t('Updated default configuration.');
      $context['finished'] = 1;
    }
  }

  /**
   * Implements callback_batch_finished().
   *
   * Finished callback of system page locale import batch.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   */
  public function batchFinished(bool $success, array $results): void {
    $logger = ($this->logger)();
    if ($success) {
      $additions = $updates = $deletes = $skips = 0;
      if (isset($results['failed_files'])) {
        if ($this->moduleHandler->moduleExists('dblog') && $this->currentUser->hasPermission('access site reports')) {
          $message = $this->translation->formatPlural(count($results['failed_files']), 'One translation file could not be imported. <a href=":url">See the log</a> for details.', '@count translation files could not be imported. <a href=":url">See the log</a> for details.', [':url' => Url::fromRoute('dblog.overview')->toString()]);
        }
        else {
          $message = $this->translation->formatPlural(count($results['failed_files']), 'One translation file could not be imported. See the log for details.', '@count translation files could not be imported. See the log for details.');
        }
        $this->messenger->addError($message);
      }
      if (isset($results['files'])) {
        $skipped_files = [];
        // If there are no results and/or no stats (eg. coping with an empty .po
        // file), simply do nothing.
        if ($results && isset($results['stats'])) {
          foreach ($results['stats'] as $filepath => $report) {
            if ($filepath === 'config') {
              // Ignore the config entry. It is processed in
              // \Drupal\locale\LocaleConfigBatch::batchFinished() below.
              continue;
            }
            $additions += $report['additions'];
            $updates += $report['updates'];
            $deletes += $report['deletes'];
            $skips += $report['skips'];
            if ($report['skips'] > 0) {
              $skipped_files[] = $filepath;
            }
          }
        }
        $this->messenger->addStatus($this->translation->formatPlural(count($results['files']),
          'One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.',
          '@count translation files imported. %number translations were added, %update translations were updated and %delete translations were removed.',
          ['%number' => $additions, '%update' => $updates, '%delete' => $deletes]
        ));
        $logger->notice('Translations imported: %number added, %update updated, %delete removed.', [
          '%number' => $additions,
          '%update' => $updates,
          '%delete' => $deletes,
        ]);

        if ($skips) {
          if ($this->moduleHandler->moduleExists('dblog') && $this->currentUser->hasPermission('access site reports')) {
            $message = $this->translation->formatPlural($skips, 'One translation string was skipped because of disallowed or malformed HTML. <a href=":url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href=":url">See the log</a> for details.', [':url' => Url::fromRoute('dblog.overview')->toString()]);
          }
          else {
            $message = $this->translation->formatPlural($skips, 'One translation string was skipped because of disallowed or malformed HTML. See the log for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.');
          }
          $this->messenger->addWarning($message);
          $logger->warning('@count disallowed HTML string(s) in files: @files.', [
            '@count' => $skips,
            '@files' => implode(',', $skipped_files),
          ]);
        }
      }
    }
    // Add messages for configuration too.
    if (isset($results['stats']['config'])) {
      $this->localeConfigBatch->batchFinished($success, $results);
    }
  }

}
