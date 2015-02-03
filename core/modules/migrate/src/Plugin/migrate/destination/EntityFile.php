<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityFile.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager) {
    $configuration += array(
      'source_base_path' => '',
      'source_path_property' => 'filepath',
      'destination_path_property' => 'uri',
      'move' => FALSE,
      'urlencode' => FALSE,
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $file = $row->getSourceProperty($this->configuration['source_path_property']);
    $destination = $row->getDestinationProperty($this->configuration['destination_path_property']);

    // We check the destination to see if this is a temporary file. If it is
    // then we do not prepend the source_base_path because temporary files are
    // already absolute.
    $source = $this->isTempFile($destination) ? $file : $this->configuration['source_base_path'] . $file;

    $dirname = drupal_dirname($destination);
    if (!file_prepare_directory($dirname, FILE_CREATE_DIRECTORY)) {
      throw new MigrateException(t('Could not create directory %dirname', array('%dirname' => $dirname)));
    }

    // If the start and end file is exactly the same, there is nothing to do.
    if (drupal_realpath($source) === drupal_realpath($destination)) {
      return parent::import($row, $old_destination_id_values);
    }

    $replace = FILE_EXISTS_REPLACE;
    if (!empty($this->configuration['rename'])) {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      if (!empty($entity_id) && ($entity = $this->storage->load($entity_id))) {
        $replace = FILE_EXISTS_RENAME;
      }
    }

    if ($this->configuration['move']) {
      $copied = file_unmanaged_move($source, $destination, $replace);
    }
    else {
      // Determine whether we can perform this operation based on overwrite rules.
      $original_destination = $destination;
      $destination = file_destination($destination, $replace);
      if ($destination === FALSE) {
        throw new MigrateException(t('File %file could not be copied because a file by that name already exists in the destination directory (%destination)', array('%file' => $source, '%destination' => $original_destination)));
      }
      $source = $this->urlencode($source);
      $copied = @copy($source, $destination);
    }
    if ($copied) {
      return parent::import($row, $old_destination_id_values);
    }
    else {
      throw new MigrateException(t('File %source could not be copied to %destination.', array('%source' => $source, '%destination' => $destination)));
    }
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

  /**
   * Check if a file is a temp file.
   *
   * @param string $file
   *   The destination file path.
   *
   * @return bool
   *   TRUE if the file is temporary otherwise FALSE.
   */
  protected function isTempFile($file) {
    $tmp = 'temporary://';
    return substr($file, 0, strlen($tmp)) === $tmp;
  }

}
