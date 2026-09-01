<?php

namespace Drupal\locale;

use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the locale javascript related methods.
 *
 * @internal
 */
class LocaleJs {

  public function __construct(
    protected readonly StateInterface $state,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly StringStorageInterface $localeStorage,
    protected readonly LocaleLanguages $localeLanguages,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly PluralFormulaInterface $pluralFormula,
    protected readonly FileSystemInterface $fileSystem,
    /**
     * @var \Closure(): \Psr\Log\LoggerInterface
     */
    #[AutowireServiceClosure('logger.channel.locale')]
    protected readonly \Closure $logger,
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Returns a list of translation files given a list of JavaScript files.
   *
   * This function checks all JavaScript files passed and invokes parsing if
   * they have not yet been parsed for Drupal.t() and Drupal.formatPlural()
   * calls. Also refreshes the JavaScript translation files if necessary, and
   * returns the filepath to the translation file (if any).
   *
   * @param array $files
   *   An array of local file paths.
   * @param \Drupal\Core\Language\LanguageInterface $languageInterface
   *   The interface language the files should be translated into.
   *
   * @return string|null
   *   The filepath to the translation file or NULL if no translation is
   *   applicable.
   */
  public function jsTranslate(array $files, LanguageInterface $languageInterface): string|null {
    $dir = 'assets://' . $this->configFactory->get('locale.settings')->get('javascript.directory');
    $parsed = $this->state->get('system.javascript_parsed', []);
    $newFiles = FALSE;

    foreach ($files as $filepath) {
      if (!in_array($filepath, $parsed)) {
        // Don't parse our own translations files.
        if (!str_starts_with($filepath, $dir)) {
          $this->parseJsFile($filepath);
          $parsed[] = $filepath;
          $newFiles = TRUE;
        }
      }
    }

    // If there are any new source files we parsed, invalidate existing
    // JavaScript translation files for all languages, adding the refresh
    // flags into the existing array.
    if ($newFiles) {
      $parsed += $this->invalidate();
    }

    // If necessary, rebuild the translation file for the current language.
    if (!empty($parsed['refresh:' . $languageInterface->getId()])) {
      // Don't clear the refresh flag on failure, so that another try will
      // be performed later.
      if ($this->rebuild()) {
        unset($parsed['refresh:' . $languageInterface->getId()]);
      }
      // Store any changes after refresh was attempted.
      $this->state->set('system.javascript_parsed', $parsed);
    }
    // If no refresh was attempted, but we have new source files, we need
    // to store them too. This occurs if current page is in English.
    elseif ($newFiles) {
      $this->state->set('system.javascript_parsed', $parsed);
    }

    // Add the translation JavaScript file to the page.
    $localeJavascripts = $this->state->get('locale.translation.javascript', []);
    $translationFile = NULL;
    if (!empty($files) && !empty($localeJavascripts[$languageInterface->getId()])) {
      // Add the translation JavaScript file to the page.
      $translationFile = $dir . '/' . $languageInterface->getId() . '_' . $localeJavascripts[$languageInterface->getId()] . '.js';
    }
    return $translationFile;
  }

  /**
   * Refresh related information after string translations have been updated.
   *
   * The information that will be refreshed includes:
   * - JavaScript translations.
   * - Locale cache.
   * - Render cache.
   *
   * @param array $langcodes
   *   Language codes for updated translations.
   * @param array $lids
   *   (optional) List of string identifiers that have been updated / created.
   *   If not provided, all caches for the affected languages are cleared.
   */
  public function refreshTranslations(array $langcodes, array $lids = []): void {
    if (!empty($langcodes)) {
      // Update javascript translations if any of the strings has a javascript
      // location, or if no string ids were provided, update all languages.
      if (empty($lids) || !empty($this->localeStorage->getStrings(['lid' => $lids, 'type' => 'javascript']))) {
        foreach ($langcodes as $langcode) {
          $this->invalidate($langcode);
        }
      }
    }

    // Throw locale.save_translation event.
    $this->eventDispatcher->dispatch(new LocaleEvent($langcodes, $lids), LocaleEvents::SAVE_TRANSLATION);
  }

  /**
   * Parses a JavaScript file, extracts translatable strings, and saves them.
   *
   * Strings are extracted from both Drupal.t() and Drupal.formatPlural().
   *
   * @param string $filepath
   *   File name to parse.
   *
   * @throws \Exception
   *   If a non-local file is attempted to be parsed.
   *
   * @internal
   */
  public function parseJsFile(string $filepath): void {
    // Regular expression pattern used to localize JavaScript strings.
    $jsStringRegex = '(?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+';

    // Regular expression pattern used to match simple JS object literal.
    // This pattern matches a basic JS object, but will fail on an object with
    // nested objects. Used in JS file parsing for string arg processing.
    $jsObjectRegex = '\{.*?\}';

    // Regular expression to match an object containing a key 'context'.
    // Pattern to match a JS object containing a 'context key' with a string
    // value, which is captured. Will fail if there are nested objects.
    $jsObjectContextRegex = '
      \{              # match object literal start
      .*?             # match anything, non-greedy
      (?:             # match a form of "context"
        \'context\'
        |
        "context"
        |
        context
      )
      \s*:\s*         # match key-value separator ":"
      (' . $jsStringRegex . ')  # match context string
      .*?             # match anything, non-greedy
      \}              # match end of object literal
    ';

    // The file path might contain a query string, so make sure we only use the
    // actual file.
    $parsedUrl = UrlHelper::parse($filepath);
    $filepath = $parsedUrl['path'];

    // If there is still a protocol component in the path, reject that.
    if (strpos($filepath, ':')) {
      throw new \Exception('Only local files should be passed to LocaleJs::parseJsFile().');
    }

    // Load the JavaScript file.
    $file = file_get_contents($filepath);

    // Match all calls to Drupal.t() in an array.
    // Note: \s also matches newlines with the 's' modifier.
    preg_match_all('~
      [^\w]Drupal\s*\.\s*t\s*                       # match "Drupal.t" with whitespace
      \(\s*                                         # match "(" argument list start
      (' . $jsStringRegex . ')\s*                 # capture string argument
      (?:,\s*' . $jsObjectRegex . '\s*            # optionally capture str args
        (?:,\s*' . $jsObjectContextRegex . '\s*) # optionally capture context
      ?)?                                           # close optional args
      [,\)]                                         # match ")" or "," to finish
      ~sx', $file, $tMatches);

    // Match all Drupal.formatPlural() calls in another array.
    preg_match_all('~
      [^\w]Drupal\s*\.\s*formatPlural\s*  # match "Drupal.formatPlural" with whitespace
      \(                                  # match "(" argument list start
      \s*.+?\s*,\s*                       # match count argument
      (' . $jsStringRegex . ')\s*,\s*   # match singular string argument
      (                             # capture plural string argument
        (?:                         # non-capturing group to repeat string pieces
          (?:
            \'(?:\\\\\'|[^\'])*\'   # match single-quoted string with any character except unescaped single-quote
            |
            "(?:\\\\"|[^"])*"       # match double-quoted string with any character except unescaped double-quote
          )
          (?:\s*\+\s*)?             # match "+" with possible whitespace, for str concat
        )+                          # match multiple because we supports concatenating strs
      )\s*                          # end capturing of plural string argument
      (?:,\s*' . $jsObjectRegex . '\s*          # optionally capture string args
        (?:,\s*' . $jsObjectContextRegex . '\s*)?  # optionally capture context
      )?
      [,\)]
      ~sx', $file, $pluralMatches);

    $matches = [];

    // Add strings from Drupal.t().
    foreach ($tMatches[1] as $key => $string) {
      $matches[] = [
        'source'  => $this->stripQuotes($string),
        'context' => $this->stripQuotes($tMatches[2][$key]),
      ];
    }

    // Add string from Drupal.formatPlural().
    foreach ($pluralMatches[1] as $key => $string) {
      $matches[] = [
        'source'  => $this->stripQuotes($string) . PoItem::DELIMITER . $this->stripQuotes($pluralMatches[2][$key]),
        'context' => $this->stripQuotes($pluralMatches[3][$key]),
      ];
    }

    // Loop through all matches and process them.
    foreach ($matches as $match) {
      $source = $this->localeStorage->findString($match);

      if (!$source) {
        // We don't have the source string yet, thus we insert it into the
        // database.
        $source = $this->localeStorage->createString($match);
      }

      // Besides adding the location this will tag it for current version.
      $source->addLocation('javascript', $filepath);
      $source->save();
    }
  }

  /**
   * Force the JavaScript translation file(s) to be refreshed.
   *
   * This function sets a refresh flag for a specified language, or all
   * languages except English, if none specified. JavaScript translation
   * files are rebuilt (with locale_update_js_files()) the next time a
   * request is served in that language.
   *
   * @param string|null $langcode
   *   (optional) The language code for which the file needs to be refreshed, or
   *   NULL to refresh all languages. Defaults to NULL.
   *
   * @return array
   *   New content of the 'system.javascript_parsed' variable.
   */
  public function invalidate(?string $langcode = NULL): array {
    $parsed = $this->state->get('system.javascript_parsed', []);

    if (empty($langcode)) {
      // Invalidate all languages.
      $languages = $this->localeLanguages->getTranslatableLanguages();
      foreach ($languages as $languageCode => $data) {
        $parsed['refresh:' . $languageCode] = 'waiting';
      }
    }
    else {
      // Invalidate single language.
      $parsed['refresh:' . $langcode] = 'waiting';
    }

    $this->state->set('system.javascript_parsed', $parsed);
    return $parsed;
  }

  /**
   * Creates or recreates the JavaScript translation file for a language.
   *
   * @param string|null $langcode
   *   (optional) The language that the translation file should be (re)created
   *   for, or NULL for the current language. Defaults to NULL.
   *
   * @return bool
   *   TRUE if translation file exists, FALSE otherwise.
   *
   * @internal
   */
  public function rebuild(?string $langcode = NULL): bool {
    if (!isset($langcode)) {
      $language = $this->languageManager->getCurrentLanguage();
    }
    else {
      // Get information about the locale.
      $languages = $this->languageManager->getLanguages();
      $language = $languages[$langcode];
    }

    // Construct the array for JavaScript translations.
    // Only add strings with a translation to the translations array.
    $conditions = [
      'type' => 'javascript',
      'language' => $language->getId(),
      'translated' => TRUE,
    ];
    $translations = [];
    foreach ($this->localeStorage->getTranslations($conditions) as $data) {
      $translations[$data->context][$data->source] = $data->translation;
    }

    // Include custom string overrides.
    $customStrings = Settings::get('locale_custom_strings_' . $language->getId(), []);
    foreach ($customStrings as $context => $strings) {
      foreach ($strings as $source => $translation) {
        $translations[$context][$source] = $translation;
      }
    }

    // Construct the JavaScript file, if there are translations.
    $dataHash = NULL;
    $data = $status = '';
    if (!empty($translations)) {
      $data = [
        'strings' => $translations,
      ];

      $localePlurals = $this->pluralFormula->getFormula($language->getId());
      if ($localePlurals) {
        $data['pluralFormula'] = $localePlurals;
      }

      $data = 'window.drupalTranslations = ' . Json::encode($data) . ';';
      $dataHash = Crypt::hashBase64($data);
    }

    // Construct the filepath where JS translation files are stored.
    // There is (on purpose) no front end to edit that variable.
    $dir = 'assets://' . $this->configFactory->get('locale.settings')->get('javascript.directory');

    // Delete old file, if we have no translations anymore, or a different file
    // to be saved.
    $localeJavascripts = $this->state->get('locale.translation.javascript', []);
    $changedHash = !isset($localeJavascripts[$language->getId()]) || ($localeJavascripts[$language->getId()] != $dataHash);

    if (!empty($localeJavascripts[$language->getId()]) && (!$data || $changedHash)) {
      try {
        $this->fileSystem->delete($dir . '/' . $language->getId() . '_' . $localeJavascripts[$language->getId()] . '.js');
      }
      catch (FileException) {
        // Ignore.
      }
      $localeJavascripts[$language->getId()] = '';
      $status = 'deleted';
    }

    // Only create a new file if the content has changed or the original file
    // got lost.
    $dest = $dir . '/' . $language->getId() . '_' . $dataHash . '.js';
    if ($data && ($changedHash || !file_exists($dest))) {
      // Ensure that the directory exists and is writable, if possible.
      $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);

      // Save the file.
      try {
        if ($this->fileSystem->saveData($data, $dest)) {
          $localeJavascripts[$language->getId()] = $dataHash;
          // If we deleted a previous version of the file and we replace it
          // with a new one we have an update.
          if ($status == 'deleted') {
            $status = 'updated';
          }
          // If the file did not exist previously and the data has changed we
          // have a fresh creation.
          elseif ($changedHash) {
            $status = 'created';
          }
          // If the data hash is unchanged the translation was lost and has to
          // be rebuilt.
          else {
            $status = 'rebuilt';
          }
        }
        else {
          $localeJavascripts[$language->getId()] = '';
          $status = 'error';
        }
      }
      catch (FileException) {
        // Do nothing.
      }
    }

    // Save the new JavaScript hash (or an empty value if the file just got
    // deleted). Act only if some operation was executed that changed the hash
    // code.
    if ($status && $changedHash) {
      $this->state->set('locale.translation.javascript', $localeJavascripts);
    }

    switch ($status) {
      case 'updated':
        ($this->logger)()->notice('Updated JavaScript translation file for the language %language.', ['%language' => $language->getName()]);
        return TRUE;

      case 'rebuilt':
        ($this->logger)()->warning('JavaScript translation file %file.js was lost.', ['%file' => $localeJavascripts[$language->getId()]]);
        // Proceed to the 'created' case as the JavaScript translation file has
        // been created again.

      case 'created':
        ($this->logger)()->notice('Created JavaScript translation file for the language %language.', ['%language' => $language->getName()]);
        return TRUE;

      case 'deleted':
        ($this->logger)()->notice('Removed JavaScript translation file for the language %language because no translations currently exist for that language.', ['%language' => $language->getName()]);
        return TRUE;

      case 'error':
        ($this->logger)()->error('An error occurred during creation of the JavaScript translation file for the language %language.', ['%language' => $language->getName()]);
        return FALSE;

      default:
        // No operation needed.
        return TRUE;
    }
  }

  /**
   * Removes the quotes and string concatenations from the string.
   *
   * @param string $string
   *   Single or double quoted strings, optionally concatenated by plus (+)
   *   sign.
   *
   * @return string
   *   String with leading and trailing quotes removed.
   */
  protected function stripQuotes(string $string): string {
    return implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($string, 1, -1)));
  }

}
