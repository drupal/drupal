<?php

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration source plugin annotation object.
 *
 * Plugin Namespace: Plugin\migrate\source
 *
 * For a working example, check
 * \Drupal\migrate\Plugin\migrate\source\EmptySource
 * \Drupal\migrate_drupal\Plugin\migrate\source\UrlAlias
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\migrate\Annotation\MigrateProcessPlugin
 * @see \Drupal\migrate\Annotation\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 *
 * @Annotation
 */
class MigrateSource extends Plugin implements MultipleProviderAnnotationInterface {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Whether requirements are met.
   *
   * @var bool
   */
  public $requirements_met = TRUE;

  /**
   * Identifies the system providing the data the source plugin will read.
   *
   * The source plugin itself determines how the value is used. For example,
   * Migrate Drupal's source plugins expect source_module to be the name of a
   * module that must be installed and enabled in the source database.
   *
   * @var string
   *
   * @see \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase::checkRequirements
   */
  public $source_module;

  /**
   * Specifies the minimum version of the source provider.
   *
   * This can be any type, and the source plugin itself determines how it is
   * used. For example, Migrate Drupal's source plugins expect this to be an
   * integer representing the minimum installed database schema version of the
   * module specified by source_module.
   *
   * @var mixed
   */
  public $minimum_version;

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    if (isset($this->definition['provider'])) {
      return is_array($this->definition['provider']) ? reset($this->definition['provider']) : $this->definition['provider'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders() {
    if (isset($this->definition['provider'])) {
      // Ensure that we return an array even if
      // \Drupal\Component\Annotation\AnnotationInterface::setProvider() has
      // been called.
      return (array) $this->definition['provider'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setProviders(array $providers) {
    $this->definition['provider'] = $providers;
  }

}
