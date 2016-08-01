<?php

namespace Drupal\views\Entity\Render;

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
   * @param string $relationship
   *   The relationship to be used, or 'none' by default.
   *
   * @return string
   *   A language code.
   */
  abstract public function getLangcode(ResultRow $row, $relationship = 'none');

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
  }

  /**
   * Runs before each entity is rendered.
   *
   * @param \Drupal\views\ResultRow[] $result
   *   The full array of results from the query.
   * @param string $relationship
   *   The relationship to be used, or 'none' by default.
   */
  public function preRender(array $result, $relationship = 'none') {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    foreach ($result as $row) {
      if ($entity = $this->getEntity($row, $relationship)) {
        $entity->view = $this->view;
        $this->build[$entity->id()] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $this->getLangcode($row, $relationship));
      }
    }
  }

  /**
   * Renders entity data.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   * @param string $relationship
   *   The relationship to be used, or 'none' by default.
   *
   * @return array
   *   A renderable array for the entity data contained in the result row.
   */
  public function render(ResultRow $row, $relationship = 'none') {
    if ($entity = $this->getEntity($row, $relationship)) {
      $entity_id = $entity->id();
      return $this->build[$entity_id];
    }
  }

  /**
   * Gets the entity assosiated with a row.
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
  protected function getEntity($row, $relationship = 'none') {
    if ($relationship === 'none') {
      return $row->_entity;
    }
    elseif (isset($row->_relationship_entities[$relationship])) {
      return $row->_relationship_entities[$relationship];
    }
    return NULL;
  }

}
