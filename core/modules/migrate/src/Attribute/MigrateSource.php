<?php

declare(strict_types=1);

namespace Drupal\migrate\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a MigrateSource attribute.
 *
 * Plugin Namespace: Plugin\migrate\source
 *
 * For a working example, see
 * \Drupal\migrate\Plugin\migrate\source\EmptySource
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\migrate\Attribute\MigrateDestination
 * @see \Drupal\migrate\Attribute\MigrateProcess
 * @see plugin_api
 *
 * @ingroup migration
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MigrateSource extends Plugin implements MultipleProviderAttributeInterface {

  /**
   * The providers of the source plugin.
   */
  protected array $providers = [];

  /**
   * Constructs a migrate source plugin attribute object.
   *
   * @param string $id
   *   A unique identifier for the source plugin.
   * @param bool $requirements_met
   *   (optional) Whether requirements are met. Defaults to true. The source
   *   plugin itself determines how the value is used. For example, Migrate
   *   Drupal's source plugins expect source_module to be the name of a module
   *   that must be installed and enabled in the source database.
   * @param mixed $minimum_version
   *   (optional) Specifies the minimum version of the source provider. This can
   *   be any type, and the source plugin itself determines how it is used. For
   *   example, Migrate Drupal's source plugins expect this to be an integer
   *   representing the minimum installed database schema version of the module
   *   specified by source_module.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   *
   * @see \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase::checkRequirements
   */
  public function __construct(
    public readonly string $id,
    public bool $requirements_met = TRUE,
    public readonly mixed $minimum_version = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function setProvider(string $provider): void {
    $this->setProviders([$provider]);
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders(): array {
    return $this->providers;
  }

  /**
   * {@inheritdoc}
   */
  public function setProviders(array $providers): void {
    if ($providers) {
      parent::setProvider(reset($providers));
    }
    else {
      $this->provider = NULL;
    }
    $this->providers = $providers;
  }

}
