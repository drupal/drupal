<?php

namespace Drupal\locale\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a Drupal translations (translations://) stream wrapper class.
 *
 * Provides support for storing translation files.
 */
class TranslationsStream extends LocalStream {

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
    return t('Translation files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Translation files');
  }

  /**
   * {@inheritdoc}
   */
  function getDirectoryPath() {
    return \Drupal::config('locale.settings')->get('translation.path');
  }

  /**
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   * @throws \LogicException PO files URL should not be public.
   */
  function getExternalUrl() {
    throw new \LogicException('PO files URL should not be public.');
  }
}
