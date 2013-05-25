<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\Core\Entity\File.
 */

namespace Drupal\file\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\file\FileInterface;

/**
 * Defines the file entity class.
 *
 * @EntityType(
 *   id = "file",
 *   label = @Translation("File"),
 *   module = "file",
 *   controllers = {
 *     "storage" = "Drupal\file\FileStorageController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController"
 *   },
 *   base_table = "file_managed",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "filename",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class File extends Entity implements FileInterface {

  /**
   * The file ID.
   *
   * @var integer
   */
  public $fid;

  /**
   * The file UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The file language code.
   *
   * @var string
   */
  public $langcode = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * The uid of the user who is associated with the file.
   *
   * @var integer
   */
  public $uid;

  /**
   * Name of the file with no path components.
   *
   * This may differ from the basename of the URI if the file is renamed to
   * avoid overwriting an existing file.
   *
   * @var string
   */
  public $filename;

  /**
   * The URI to access the file (either local or remote).
   *
   * @var string
   */
  public $uri;

  /**
   * The file's MIME type.
   *
   * @var string
   */
  public $filemime;

  /**
   * The size of the file in bytes.
   *
   * @var integer
   */
  public $filesize;

  /**
   * A field indicating the status of the file.
   *
   * Two status are defined in core: temporary (0) and permanent (1).
   * Temporary files older than DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed
   * during a cron run.
   *
   * @var integer
   */
  public $status;

  /**
   * UNIX timestamp for when the file was last saved.
   *
   * @var integer
   */
  public $timestamp;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->fid;
  }

}
