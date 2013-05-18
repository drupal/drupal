<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparerManifest.
 */

namespace Drupal\Core\Config;

/**
 * Defines a config storage comparer that uses config entity manifests.
 *
 * Config entities maintain 'manifest' files that list the objects they are
 * currently handling. Each file is a simple indexed array of config object
 * names. In order to generate a list of objects that have been created or
 * deleted we need to open these files in both the source and target storage,
 * generate an array of the objects, and compare them.
 */
class StorageComparerManifest extends StorageComparer {

  /**
   * List of config entities managed by manifests in the source storage.
   *
   * @see \Drupal\Core\Config\StorageComparerManifest::getSourceManifestData()
   *
   * @var array
   */
  protected $sourceManifestData = array();

  /**
   * List of config entities managed by manifests in the target storage.
   *
   * @see \Drupal\Core\Config\StorageComparerManifest::getTargetManifestData()
   *
   * @var array
   */
  protected $targetManifestData = array();

  /**
   * {@inheritdoc}
   */
  public function addChangelistDelete() {
    foreach (array_diff_key($this->getTargetManifestData(), $this->getSourceManifestData()) as $value) {
      $this->addChangeList('delete', array($value['name']));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addChangelistCreate() {
    foreach (array_diff_key($this->getSourceManifestData(), $this->getTargetManifestData()) as $value) {
      $this->addChangeList('create', array($value['name']));
    }
    return $this;
  }

  /**
   * Gets the list of config entities from the source storage's manifest files.
   *
   * @return array
   *   The list of config entities in the source storage whose entity type has a
   *   manifest in the source storage.
   */
  protected function getSourceManifestData() {
    if (empty($this->sourceManifestData)) {
      foreach ($this->getSourceStorage()->listAll('manifest') as $name) {
        if ($source_manifest_data = $this->getSourceStorage()->read($name)) {
          $this->sourceManifestData = array_merge($this->sourceManifestData, $source_manifest_data);
        }
      }
    }
    return $this->sourceManifestData;
  }

  /**
   * Gets the list of config entities from the target storage's manifest files.
   *
   * @see \Drupal\Core\Config\ConfigImporter::getSourceManifestData()
   *
   * @return array
   *   The list of config entities in the target storage whose entity type has a
   *   manifest in the source storage.
   */
  protected function getTargetManifestData() {
    if (empty($this->targetManifestData)) {
      foreach ($this->getSourceStorage()->listAll('manifest') as $name) {
        if ($target_manifest_data = $this->targetStorage->read($name)) {
          $this->targetManifestData = array_merge($this->targetManifestData, $target_manifest_data);
        }
      }
    }
    return $this->targetManifestData;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->sourceManifestData = $this->targetManifestData = array();
    return parent::reset();
  }

}
