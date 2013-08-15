<?php

/**
 * @file
 * Definition of Drupal\locale\TranslationStream.
 */

namespace Drupal\locale;

use Drupal\Core\StreamWrapper\LocalStream;

/**
 * Defines a Drupal translations (translations://) stream wrapper class.
 *
 * Provides support for storing translation files.
 */
class TranslationsStream extends LocalStream {

  /**
   * Implements Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath()
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
