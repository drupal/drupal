<?php

namespace Drupal\Core\StringTranslation\Translator;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Gettext\PoMemoryWriter;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * File based string translation.
 *
 * Translates a string when some systems are not available.
 *
 * Used during the install process, when database, theme, and localization
 * system is possibly not yet available.
 */
class FileTranslation extends StaticTranslation {

  /**
   * Directory to find translation files in the file system.
   *
   * @var string
   */
  protected $directory;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a StaticTranslation object.
   *
   * @param string $directory
   *   The directory to retrieve file translations from.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct($directory, FileSystemInterface $file_system) {
    parent::__construct();
    $this->directory = $directory;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguage($langcode) {
    // If the given langcode was selected, there should be at least one .po
    // file with its name in the pattern drupal-$version.$langcode.po.
    // This might or might not be the entire filename. It is also possible
    // that multiple files end with the same suffix, even if unlikely.
    $files = $this->findTranslationFiles($langcode);

    if (!empty($files)) {
      return $this->filesToArray($langcode, $files);
    }
    else {
      return [];
    }
  }

  /**
   * Finds installer translations either for a specific or all languages.
   *
   * Filenames must match the pattern:
   *  - 'drupal-[version].[langcode].po (if langcode is provided)
   *  - 'drupal-[version].*.po (if no langcode is provided)
   *
   * @param string $langcode
   *   (optional) The language code corresponding to the language for which we
   *   want to find translation files. If omitted, information on all available
   *   files will be returned.
   *
   * @return array
   *   An associative array of file information objects keyed by file URIs as
   *   returned by FileSystemInterface::scanDirectory().
   *
   * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
   */
  public function findTranslationFiles($langcode = NULL) {
    $files = [];
    if (is_dir($this->directory)) {
      $files = $this->fileSystem->scanDirectory($this->directory, $this->getTranslationFilesPattern($langcode), ['recurse' => FALSE]);
    }
    return $files;
  }

  /**
   * Provides translation file name pattern.
   *
   * @param string $langcode
   *   (optional) The language code corresponding to the language for which we
   *   want to find translation files.
   *
   * @return string
   *   String file pattern.
   */
  protected function getTranslationFilesPattern($langcode = NULL) {
    // The file name matches: drupal-[release version].[language code].po
    // When provided the $langcode is use as language code. If not provided all
    // language codes will match.
    return '!drupal-[0-9]+\.[0-9]+(\.([0-9]+|x))?(-[a-z]+[0-9]*)?\.' . (!empty($langcode) ? preg_quote($langcode, '!') : LanguageInterface::VALID_LANGCODE_REGEX) . '\.po$!';
  }

  /**
   * Reads the given Gettext PO files into a data structure.
   *
   * @param string $langcode
   *   Language code string.
   * @param array $files
   *   List of file objects with URI properties pointing to read.
   *
   * @return array
   *   Structured array as produced by a PoMemoryWriter.
   *
   * @see \Drupal\Component\Gettext\PoMemoryWriter
   */
  public static function filesToArray($langcode, array $files) {
    $writer = new PoMemoryWriter();
    $writer->setLangcode($langcode);
    foreach ($files as $file) {
      $reader = new PoStreamReader();
      $reader->setURI($file->uri);
      $reader->setLangcode($langcode);
      $reader->open();
      $writer->writeItems($reader, -1);
    }
    return $writer->getData();
  }

}
