<?php

namespace Drupal\views\Entity\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Defines a base class for entity renderers.
 */
abstract class RendererBase implements CacheableDependencyInterface {

  /**
   * Contains an array of render arrays, one for each rendered entity.
   *
   * @var array
   */
  protected $build;

  /**
   * Constructs a renderer object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The entity row being rendered.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   */
  public function __construct(
    public ViewExecutable $view,
    protected LanguageManagerInterface $languageManager,
    protected EntityTypeInterface $entityType,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * Alters the query if needed.
   *
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query to alter.
   * @param string $relationship
   *   (optional) The relationship, used by a field.
   */
  abstract public function query(QueryPluginBase $query, $relationship = NULL);

  /**
   * Runs before each entity is rendered.
   *
   * @param \Drupal\views\ResultRow[] $result
   *   The full array of results from the query.
   */
  public function preRender(array $result) {
  }

  /**
   * Renders entity data.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   *
   * @return array
   *   A renderable array for the entity data contained in the result row.
   */
  abstract public function render(ResultRow $row);

}
