<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityType.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityType;

/**
 * Provides an implementation of a config entity type and its metadata.
 */
class ConfigEntityType extends EntityType {

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @var string
   */
  protected $config_prefix;

  /**
   * {@inheritdoc}
   */
  public function getControllerClasses() {
    return parent::getControllerClasses() + array(
      'storage' => 'Drupal\Core\Config\Entity\ConfigStorageController',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return isset($this->config_prefix) ? $this->config_prefix : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDataTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return FALSE;
  }

}
