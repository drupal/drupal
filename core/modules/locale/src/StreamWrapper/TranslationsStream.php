<?php

namespace Drupal\locale\StreamWrapper;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a Drupal translations (translations://) stream wrapper class.
 *
 * Provides support for storing translation files.
 */
class TranslationsStream extends LocalStream {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Translation files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Translation files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * phpcs:ignore Drupal.Files.LineLength
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   *
   * @throws \LogicException
   *   PO files URL should not be public.
   */
  public function getExternalUrl() {
    throw new \LogicException('PO files URL should not be public.');
  }

  /**
   * Returns the base path for translations://.
   *
   * @return string|null
   *   The base path for translations://.
   */
  public static function basePath() {
    if ($config_path = \Drupal::config('locale.settings')->get('translation.path')) {
      return $config_path;
    }
    $file_system = \Drupal::service(FileSystemInterface::class);
    $path = Settings::get('locale_translation_path', 'public://translations');
    // Stream wrappers such as public:// must be resolved explicitly, fall back
    // to the original path where realpath() fails.
    if ($realpath = $file_system->realpath($path)) {
      return $realpath;
    }
    return $path;
  }

}
