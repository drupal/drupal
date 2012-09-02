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
    return variable_get('locale_translate_file_directory',
      conf_path() . '/files/translations');
  }

  /**
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   * @throws \LogicException PO files URL should not be public.
   */
  function getExternalUrl() {
    throw new \LogicException('PO files URL should not be public.');
  }
}
