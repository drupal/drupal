<?php

namespace Drupal\link\Plugin\migrate\process\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_field_link"
 * )
 */
class FieldLink extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * Turn a Drupal 6 URI into a Drupal 8-compatible format.
   *
   * @param string $uri
   *   The 'url' value from Drupal 6.
   *
   * @return string
   *   The Drupal 8-compatible URI.
   *
   * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
   */
  protected function canonicalizeUri($uri) {
    // If we already have a scheme, we're fine.
    if (empty($uri) || !is_null(parse_url($uri, PHP_URL_SCHEME))) {
      return $uri;
    }

    // Remove the <front> component of the URL.
    if (strpos($uri, '<front>') === 0) {
      $uri = substr($uri, strlen('<front>'));
    }

    // Add the internal: scheme and ensure a leading slash.
    return 'internal:/' . ltrim($uri, '/');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $attributes = unserialize($value['attributes']);
    // Drupal 6 link attributes might be double serialized.
    if (!is_array($attributes)) {
      $attributes = unserialize($attributes);
    }

    if (!$attributes) {
      $attributes = [];
    }

    // Massage the values into the correct form for the link.
    $route['uri'] = $this->canonicalizeUri($value['url']);
    $route['options']['attributes'] = $attributes;
    $route['title'] = $value['title'];
    return $route;
  }

}
