<?php

namespace Drupal\file;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines getter and setter methods for file entity base fields.
 *
 * @ingroup file
 */
interface FileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Returns the name of the file.
   *
   * This may differ from the basename of the URI if the file is renamed to
   * avoid overwriting an existing file.
   *
   * @return string
   *   Name of the file.
   */
  public function getFilename();

  /**
   * Sets the name of the file.
   *
   * @param string $filename
   *   The file name that corresponds to this file. May differ from the basename
   *   of the URI and changing the filename does not change the URI.
   */
  public function setFilename($filename);

  /**
   * Returns the URI of the file.
   *
   * @return string
   *   The URI of the file, e.g. public://directory/file.jpg.
   */
  public function getFileUri();

  /**
   * Sets the URI of the file.
   *
   * @param string $uri
   *   The URI of the file, e.g. public://directory/file.jpg. Does not change
   *   the location of the file.
   */
  public function setFileUri($uri);

  /**
   * Returns the MIME type of the file.
   *
   * @return string
   *   The MIME type of the file, e.g. image/jpeg or text/xml.
   */
  public function getMimeType();

  /**
   * Sets the MIME type of the file.
   *
   * @param string $mime
   *   The MIME type of the file, e.g. image/jpeg or text/xml.
   */
  public function setMimeType($mime);

  /**
   * Returns the size of the file.
   *
   * @return string
   *   The size of the file in bytes.
   */
  public function getSize();

  /**
   * Sets the size of the file.
   *
   * @param int $size
   *   The size of the file in bytes.
   */
  public function setSize($size);

  /**
   * Returns TRUE if the file is permanent.
   *
   * @return bool
   *   TRUE if the file status is permanent.
   */
  public function isPermanent();

  /**
   * Returns TRUE if the file is temporary.
   *
   * @return bool
   *   TRUE if the file status is temporary.
   */
  public function isTemporary();

  /**
   * Sets the file status to permanent.
   */
  public function setPermanent();

  /**
   * Sets the file status to temporary.
   */
  public function setTemporary();

  /**
   * Returns the file entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the file entity.
   */
  public function getCreatedTime();

}
