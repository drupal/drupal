<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit6;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\file\FileInterface;

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait FileFieldTestBaseTrait {

  /**
   * Asserts that a file exists physically on disk.
   *
   * Overrides PHPUnit\Framework\Assert::assertFileExists() to also work with
   * file entities.
   *
   * @param \Drupal\File\FileInterface|string $file
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @see https://www.drupal.org/node/3057326
   */
  public static function assertFileExists($file, $message = NULL) {
    if ($file instanceof FileInterface) {
      @trigger_error('Passing a File entity as $file argument to FileFieldTestBase::assertFileExists is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Instead, pass the File entity URI via File::getFileUri(). See https://www.drupal.org/node/3057326', E_USER_DEPRECATED);
      $file = $file->getFileUri();
    }
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists on the disk.', ['%file' => $file]);
    parent::assertFileExists($file, $message);
  }

  /**
   * Asserts that a file does not exist on disk.
   *
   * Overrides PHPUnit\Framework\Assert::assertFileNotExists() to also work
   * with file entities.
   *
   * @param \Drupal\File\FileInterface|string $file
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @see https://www.drupal.org/node/3057326
   */
  public static function assertFileNotExists($file, $message = NULL) {
    if ($file instanceof FileInterface) {
      @trigger_error('Passing a File entity as $file argument to FileFieldTestBase::assertFileNotExists is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Instead, pass the File entity URI via File::getFileUri(). See https://www.drupal.org/node/3057326', E_USER_DEPRECATED);
      $file = $file->getFileUri();
    }
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists on the disk.', ['%file' => $file]);
    parent::assertFileNotExists($file, $message);
  }

}
