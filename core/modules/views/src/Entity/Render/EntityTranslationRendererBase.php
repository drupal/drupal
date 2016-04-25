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
   *
   * @return string
   *   A language code.
   */
  abstract public function getLangcode(ResultRow $row);

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      // @todo Take relationships into account.
      //   See https://www.drupal.org/node/2457999.
      $entity = $row->_entity;
      $entity->view = $this->view;
      $this->build[$entity->id()] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $this->getLangcode($row));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();
    return $this->build[$entity_id];
  }

}
