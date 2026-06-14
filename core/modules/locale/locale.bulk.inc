<?php

/**
 * @file
 */

use Drupal\Core\Batch\BatchBuilder;
use Drupal\file\FileInterface;
use Drupal\locale\File\LocaleFile;
use Drupal\locale\File\LocaleFileManager;
use Drupal\locale\LocaleConfigBatch;
use Drupal\locale\LocaleImportBatch;
use Drupal\locale\LocaleSource;

/**
 * Get interface translation files present in the translations directory.
 *
 * @param array $projects
 *   (optional) Project names from which to get the translation files and
 *   history. Defaults to all projects.
 * @param array $langcodes
 *   (optional) Language codes from which to get the translation files and
 *   history. Defaults to all languages.
 *
 * @return array
 *   An array of interface translation files keyed by their URI.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFileManager::class)
 *   ->getInterfaceTranslationFiles($projects, $langcodes) instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translate_get_interface_translation_files(array $projects = [], array $langcodes = []): array {
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFileManager::class)->getInterfaceTranslationFiles($projects, $langcodes) instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  return \Drupal::service(LocaleFileManager::class)->getInterfaceTranslationFiles($projects, $langcodes);
}

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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleImportBatch::class)->buildBatch()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translate_batch_build(array $files, array $options) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleImportBatch::class)->buildBatch(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  return \Drupal::service(LocaleImportBatch::class)->buildBatch($files, $options);
}

/**
 * Implements callback_batch_operation().
 *
 * Perform interface translation import.
 *
 * @param object $file
 *   A file object of the gettext file to be imported. The file object must
 *   contain a language parameter (other than
 *   LanguageInterface::LANGCODE_NOT_SPECIFIED). This is used as the language of
 *   the import.
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'message': Alternative message to display during import. Note, this must
 *     be sanitized text.
 * @param array|\ArrayAccess $context
 *   Contains a list of files imported.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleImportBatch::class)->batchImport()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translate_batch_import($file, array $options, &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleImportBatch::class)->batchImport(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  if (!($file instanceof LocaleFile)) {
    $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $file->uri);
    $file = new LocaleFile($file->filename, $file->uri, $hash);
  }
  \Drupal::service(LocaleImportBatch::class)->batchImport($file, $options, $context);
}

/**
 * Implements callback_batch_operation().
 *
 * Save data of imported files.
 *
 * @param array|\ArrayAccess $context
 *   Contains a list of imported files.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleImportBatch::class)->batchSave()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translate_batch_import_save($context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleImportBatch::class)->batchSave(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleImportBatch::class)->batchSave($context);
}

/**
 * Implements callback_batch_operation().
 *
 * Refreshes translations after importing strings.
 *
 * @param array|\ArrayAccess $context
 *   Contains a list of strings updated and information about the progress.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleImportBatch::class)->batchRefresh()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translate_batch_refresh(&$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleImportBatch::class)->batchRefresh(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleImportBatch::class)->batchRefresh($context);
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleImportBatch::class)->batchFinished()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translate_batch_finished($success, array $results): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleImportBatch::class)->batchFinished(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleImportBatch::class)->batchFinished($success, $results);
}

/**
 * Creates a file object and populates the timestamp and hash properties.
 *
 * @param string $filepath
 *   The filepath of a file to import.
 *
 * @return object
 *   An object representing the file.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   LocaleFile instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translate_file_create($filepath) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use LocaleFile instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $filepath);
  return new LocaleFile($filepath, basename($filepath), $hash, filemtime($filepath));
}

/**
 * Generates file properties from filename and options.
 *
 * An attempt is made to determine the translation language, project name and
 * project version from the file name. Supported file name patterns are:
 * {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or {langcode}.po.
 * Alternatively the translation language can be set using the $options.
 *
 * @param object $file
 *   A file object of the gettext file to be imported.
 * @param array $options
 *   An array with options:
 *   - 'langcode': The language code. Overrides the file language.
 *
 * @return object
 *   Modified file object.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   LocaleFile::createFromPath($filename, $uri, $langCodeOverride) instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translate_file_attach_properties($file, array $options = []) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use LocaleFile::createFromPath($filename, $uri, $langCodeOverride) instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  if ($file instanceof FileInterface) {
    $uri = $file->getFileUri();
    $filename = $file->getFilename();
  }
  else {
    $uri = $file->uri;
    $filename = $file->filename;
  }
  return LocaleFile::createFromPath($filename, $uri, $options['langcode'] ?? NULL);
}

/**
 * Deletes interface translation files and translation history records.
 *
 * @param array $projects
 *   (optional) Project names from which to delete the translation files and
 *   history. Defaults to all projects.
 * @param array $langcodes
 *   (optional) Language codes from which to delete the translation files and
 *   history. Defaults to all languages.
 *
 * @return bool
 *   TRUE if files are removed successfully. FALSE if one or more files could
 *   not be deleted.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFileManager::class)
 *   ->deleteTranslationFiles($projects, $langcodes) instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translate_delete_translation_files(array $projects = [], array $langcodes = []) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFileManager::class)->deleteTranslationFiles($projects, $langcodes) instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  return !\Drupal::service(LocaleFileManager::class)->deleteTranslationFiles($projects, $langcodes);
}

/**
 * Builds a locale batch to refresh configuration.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'finish_feedback': (optional) Whether or not to give feedback to the user
 *     when the batch is finished. Defaults to TRUE.
 * @param array $langcodes
 *   (optional) Array of language codes. Defaults to all translatable languages.
 * @param array $components
 *   (optional) Array of component lists indexed by type. If not present or it
 *   is an empty array, it will update all components.
 * @param bool $update_default_config_langcodes
 *   Determines whether default configuration langcodes should be updated. This
 *   Should only happen during site and extension install.
 *
 * @return array
 *   The batch definition.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleConfigBatch::class)->buildBatch()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_config_batch_update_components(array $options, array $langcodes = [], array $components = [], bool $update_default_config_langcodes = FALSE) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleConfigBatch::class)->buildBatch(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  return \Drupal::service(LocaleConfigBatch::class)->buildBatch($options, $langcodes, $components, $update_default_config_langcodes);
}

/**
 * Creates a locale batch to refresh specific configuration.
 *
 * @param array $names
 *   List of configuration object names (which are strings) to update.
 * @param array $langcodes
 *   List of language codes to refresh.
 * @param array $options
 *   (optional) An array with options that can have the following elements:
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Defaults to TRUE.
 * @param bool $update_default_config_langcodes
 *   Determines whether default configuration langcodes should be updated. This
 *   Should only happen during site and extension install.
 *
 * @return array
 *   The batch definition.
 *
 * @see \Drupal\locale\LocaleConfigBatch::batchUpdateConfigTranslations()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleConfigBatch::class)->buildBatch()
 *   instead the arguments have changed.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_config_batch_build(array $names, array $langcodes, array $options = [], bool $update_default_config_langcodes = FALSE) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleConfigBatch::class)->buildBatch() instead, the arguments have changed. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  $options += ['finish_feedback' => TRUE];
  $batch_builder = (new BatchBuilder())
    ->setTitle(t('Updating configuration translations'))
    ->setInitMessage(t('Starting configuration update'))
    ->setErrorMessage(t('Error updating configuration translations'));

  if ($update_default_config_langcodes && \Drupal::languageManager()->getDefaultLanguage()->getId() !== 'en') {
    $batch_builder->addOperation(LocaleConfigBatch::class . ':batchUpdateDefaultConfigLangcodes');
  }

  // Chunking the array of names into batches of 20 for better performance.
  $name_chunks = array_chunk($names, 20);

  foreach ($name_chunks as $chunk) {
    // During installation the caching of configuration objects is disabled so
    // it is very expensive to initialize the \Drupal::config() object on each
    // request. We batch a small number of configuration object upgrades
    // together to improve the overall performance of the process.
    $batch_builder->addOperation(LocaleConfigBatch::class . ':batchUpdateConfigTranslations', [$chunk, $langcodes]);
  }

  if (!empty($options['finish_feedback'])) {
    $batch_builder->setFinishCallback(LocaleConfigBatch::class . ':batchFinished');
  }
  return $batch_builder->toArray();
}

/**
 * Implements callback_batch_operation().
 *
 * Updates default configuration when new modules or themes are installed.
 *
 * @param array|\ArrayAccess $context
 *   The batch context.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleConfigBatch::class)->batchUpdateDefaultConfigLangcodes()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_config_batch_update_default_config_langcodes(&$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleConfigBatch::class)->batchUpdateDefaultConfigLangcodes(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleConfigBatch::class)->batchUpdateDefaultConfigLangcodes($context);
}

/**
 * Implements callback_batch_operation().
 *
 * Updates default configuration when new modules or themes are installed.
 *
 * @param array|\ArrayAccess $context
 *   The batch context.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
 * locale_config_batch_update_default_config_langcodes(&$context) instead.
 *
 * @see https://www.drupal.org/node/3475054
 */
function locale_config_batch_set_config_langcodes(&$context): void {
  @trigger_error(__METHOD__ . '() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use locale_config_batch_update_default_config_langcodes() instead. See https://www.drupal.org/node/3475054', E_USER_DEPRECATED);
  locale_config_batch_update_default_config_langcodes($context);
}

/**
 * Implements callback_batch_operation().
 *
 * Performs configuration translation refresh.
 *
 * @param array $names
 *   An array of names of configuration objects to update.
 * @param array $langcodes
 *   (optional) Array of language codes to update. Defaults to all languages.
 * @param array|\ArrayAccess $context
 *   Contains a list of files imported.
 *
 * @see \Drupal\locale\LocaleConfigBatch::buildBatch()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleConfigBatch::class)->batchUpdateConfigTranslations()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_config_batch_update_config_translations(array $names, array $langcodes, &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleConfigBatch::class)->batchUpdateConfigTranslations(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleConfigBatch::class)->batchUpdateConfigTranslations($names, $langcodes, $context);
}

/**
 * Implements callback_batch_operation().
 *
 * Performs configuration translation refresh.
 *
 * @param array $names
 *   An array of names of configuration objects to update.
 * @param array $langcodes
 *   (optional) Array of language codes to update. Defaults to all languages.
 * @param array|\ArrayAccess $context
 *   Contains a list of files imported.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
 * locale_config_batch_update_config_translations($names, $langcodes, $context)
 * instead.
 *
 * @see https://www.drupal.org/node/3475054
 */
function locale_config_batch_refresh_name(array $names, array $langcodes, &$context): void {
  @trigger_error(__METHOD__ . '() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use locale_config_batch_update_config_translations() instead. See https://www.drupal.org/node/3475054', E_USER_DEPRECATED);
  locale_config_batch_update_config_translations($names, $langcodes, $context);
}

/**
 * Implements callback_batch_finished().
 *
 * Finishes callback of system page locale import batch.
 *
 * @param bool $success
 *   Information about the success of the batch import.
 * @param array $results
 *   Information about the results of the batch import.
 *
 * @see \Drupal\locale\LocaleConfigBatch::buildBatch()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleConfigBatch::class)->batchFinished()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_config_batch_finished($success, array $results): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleConfigBatch::class)->batchFinished(). See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleConfigBatch::class)->batchFinished($success, $results);
}
