<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityFile.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Every migration that uses this destination must have an optional
 * dependency on the d6_file migration to ensure it runs first.
 *
 * @MigrateDestination(
 *   id = "entity:file"
 * )
 */
class EntityFile extends EntityContentBase {

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system) {
    $configuration += array(
      'source_base_path' => '',
      'source_path_property' => 'filepath',
      'destination_path_property' => 'uri',
      'move' => FALSE,
      'urlencode' => FALSE,
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager);

    $this->streamWrapperManager = $stream_wrappers;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $file = $row->getSourceProperty($this->configuration['source_path_property']);
    $destination = $row->getDestinationProperty($this->configuration['destination_path_property']);
    $source = $this->configuration['source_base_path'] . $file;

    // Ensure the source file exists, if it's a local URI or path.
    if ($this->isLocalUri($source) && !file_exists($source)) {
      throw new MigrateException("File '$source' does not exist.");
    }

    // If the start and end file is exactly the same, there is nothing to do.
    if ($this->isLocationUnchanged($source, $destination)) {
      return parent::import($row, $old_destination_id_values);
    }

    $replace = $this->getOverwriteMode($row);
    $success = $this->writeFile($source, $destination, $replace);
    if (!$success) {
      $dir = $this->getDirectory($destination);
      if (file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
        $success = $this->writeFile($source, $destination, $replace);
      }
      else {
        throw new MigrateException("Could not create directory '$dir'");
      }
    }

    if ($success) {
      return parent::import($row, $old_destination_id_values);
    }
    else {
      throw new MigrateException("File $source could not be copied to $destination.");
    }
  }

  /**
   * Tries to move or copy a file.
   *
   * @param string $source
   *  The source path or URI.
   * @param string $destination
   *  The destination path or URI.
   * @param integer $replace
   *  FILE_EXISTS_REPLACE (default) or FILE_EXISTS_RENAME.
   *
   * @return bool
   *  TRUE on success, FALSE on failure.
   */
  protected function writeFile($source, $destination, $replace = FILE_EXISTS_REPLACE) {
    if ($this->configuration['move']) {
      return (boolean) file_unmanaged_move($source, $destination, $replace);
    }
    else {
      $destination = file_destination($destination, $replace);
      $source = $this->urlencode($source);
      return @copy($source, $destination);
    }
  }

  /**
   * Determines how to handle file conflicts.
   *
   * @param \Drupal\migrate\Row $row
   *
   * @return integer
   *  Either FILE_EXISTS_REPLACE (default) or FILE_EXISTS_RENAME, depending
   *  on the current configuration.
   */
  protected function getOverwriteMode(Row $row) {
    if (!empty($this->configuration['rename'])) {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      if ($entity_id && ($entity = $this->storage->load($entity_id))) {
        return FILE_EXISTS_RENAME;
      }
    }
    return FILE_EXISTS_REPLACE;
  }

  /**
   * Returns the directory component of a URI or path.
   *
   * For URIs like public://foo.txt, the full physical path of public://
   * will be returned, since a scheme by itself will trip up certain file
   * API functions (such as file_prepare_directory()).
   *
   * @param string $uri
   *  The URI or path.
   *
   * @return string|false
   *  The directory component of the path or URI, or FALSE if it could not
   *  be determined.
   */
  protected function getDirectory($uri) {
    $dir = $this->fileSystem->dirname($uri);
    if (substr($dir, -3) == '://') {
      return $this->fileSystem->realpath($dir);
    }
    else {
      return $dir;
    }
  }

  /**
   * Returns if the source and destination URIs represent identical paths.
   * If either URI is a remote stream, will return FALSE.
   *
   * @param string $source
   *  The source URI.
   * @param string $destination
   *  The destination URI.
   *
   * @return bool
   *  TRUE if the source and destination URIs refer to the same physical path,
   *  otherwise FALSE.
   */
  protected function isLocationUnchanged($source, $destination) {
    if ($this->isLocalUri($source) && $this->isLocalUri($destination)) {
      return $this->fileSystem->realpath($source) === $this->fileSystem->realpath($destination);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns if the given URI or path is considered local.
   *
   * A URI or path is considered local if it either has no scheme component,
   * or the scheme is implemented by a stream wrapper which extends
   * \Drupal\Core\StreamWrapper\LocalStream.
   *
   * @param string $uri
   *  The URI or path to test.
   *
   * @return bool
   */
  protected function isLocalUri($uri) {
    $scheme = $this->fileSystem->uriScheme($uri);
    return $scheme === FALSE || $this->streamWrapperManager->getViaScheme($scheme) instanceof LocalStream;
  }

  /**
   * Urlencode all the components of a remote filename.
   *
   * @param string $filename
   *   The filename of the file to be urlencoded.
   *
   * @return string
   *   The urlencoded filename.
   */
  protected function urlencode($filename) {
    // Only apply to a full URL
    if ($this->configuration['urlencode'] && strpos($filename, '://')) {
      $components = explode('/', $filename);
      foreach ($components as $key => $component) {
        $components[$key] = rawurlencode($component);
      }
      $filename = implode('/', $components);
      // Actually, we don't want certain characters encoded
      $filename = str_replace('%3A', ':', $filename);
      $filename = str_replace('%3F', '?', $filename);
      $filename = str_replace('%26', '&', $filename);
    }
    return $filename;
  }

}
