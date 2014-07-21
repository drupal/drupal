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
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return \Drupal::config('locale.settings')->get('translation.path');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException PO files URL should not be public.
   */
  public function getExternalUrl() {
    throw new \LogicException('PO files URL should not be public.');
  }
}
