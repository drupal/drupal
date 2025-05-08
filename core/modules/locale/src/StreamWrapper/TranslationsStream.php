<?php

namespace Drupal\locale\StreamWrapper;

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
    return \Drupal::config('locale.settings')->get('translation.path');
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

}
