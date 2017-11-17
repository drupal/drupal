<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

/**
 * Defines getter methods for FileMediaFormatterBase.
 *
 * This interface is used on the FileMediaFormatterBase class to ensure that
 * each file media formatter will be based on a media type.
 *
 * Abstract classes are not able to implement abstract static methods,
 * this interface will work around that.
 *
 * @see \Drupal\file\Plugin\Field\FieldFormatter\FileMediaFormatterBase
 */
interface FileMediaFormatterInterface {

  /**
   * Gets the applicable media type for a formatter.
   *
   * @return string
   *   The media type of this formatter.
   */
  public static function getMediaType();

}
