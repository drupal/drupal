<?php

namespace Drupal\filter\Plugin\migrate\process;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "filter_id"
 * )
 */
class FilterID extends StaticMap implements ContainerFactoryPluginInterface {

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\Component\Plugin\FallbackPluginManagerInterface
   */
  protected $filterManager;

  /**
   * FilterID constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $filter_manager
   *   The filter plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   (optional) The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $filter_manager, TranslationInterface $translator = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filterManager = $filter_manager;
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.filter'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $plugin_id = parent::transform($value, $migrate_executable, $row, $destination_property);

    // If the static map is bypassed on failure, the returned plugin ID will be
    // an array if $value was. Plugin IDs cannot be arrays, so flatten it before
    // passing it into the filter manager.
    if (is_array($plugin_id)) {
      $plugin_id = implode(':', $plugin_id);
    }

    if ($this->filterManager->hasDefinition($plugin_id)) {
      return $plugin_id;
    }
    else {
      $fallback = $this->filterManager->getFallbackPluginId($plugin_id);

      $message = $this->t('Filter @plugin_id could not be mapped to an existing filter plugin; defaulting to @fallback.', [
        '@plugin_id' => $plugin_id,
        '@fallback' => $fallback,
      ]);
      $migrate_executable->saveMessage((string) $message, MigrationInterface::MESSAGE_WARNING);

      return $fallback;
    }
  }

}
