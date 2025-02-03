<?php

namespace Drupal\views\Entity\Render;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a base class for entity translation renderers.
 */
abstract class EntityTranslationRendererBase extends RendererBase {

  /**
   * Returns the language code associated with the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   A language code.
   */
  abstract public function getLangcode(ResultRow $row);

  /**
   * Returns the language code associated with the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $relationship
   *   The relationship to be used.
   *
   * @return string
   *   A language code.
   */
  public function getLangcodeByRelationship(ResultRow $row, string $relationship): string {
    // This method needs to be overridden if the relationship is needed in the
    // implementation of getLangcode().
    return $this->getLangcode($row);
  }

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $this->preRenderByRelationship($result, 'none');
  }

  /**
   * Runs before each entity is rendered if a relationship is needed.
   *
   * @param \Drupal\views\ResultRow[] $result
   *   The full array of results from the query.
   * @param string $relationship
   *   The relationship to be used.
   */
  public function preRenderByRelationship(array $result, string $relationship): void {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($this->entityType->id());

    foreach ($result as $row) {
      if ($entity = $this->getEntity($row, $relationship)) {
        $entity->view = $this->view;
        $this->build[$entity->id()] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $this->getLangcodeByRelationship($row, $relationship));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    return $this->renderByRelationship($row, 'none');
  }

  /**
   * Renders entity data.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   * @param string $relationship
   *   The relationship to be used.
   *
   * @return array
   *   A renderable array for the entity data contained in the result row.
   */
  public function renderByRelationship(ResultRow $row, string $relationship): array {
    if ($entity = $this->getEntity($row, $relationship)) {
      $entity_id = $entity->id();
      return $this->build[$entity_id];
    }
    return [];
  }

  /**
   * Gets the entity associated with a row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $relationship
   *   (optional) The relationship.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity might be optional, because the relationship entity might not
   *   always exist.
   */
  protected function getEntity(ResultRow $row, string $relationship = 'none'): ?EntityInterface {
    if ($relationship === 'none') {
      return $row->_entity;
    }
    elseif (isset($row->_relationship_entities[$relationship])) {
      return $row->_relationship_entities[$relationship];
    }
    return NULL;
  }

}
