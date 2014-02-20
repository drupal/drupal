<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\DefaultLanguageRenderer.
 */

namespace Drupal\views\Entity\Render;

use Drupal\views\ResultRow;

/**
 * Renders entities in their default language.
 */
class DefaultLanguageRenderer extends RendererBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = array();
    $langcodes = array();

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      $entity = $row->_entity;
      $entity->view = $this->view;
      $langcodes[] = $langcode = $this->getLangcode($row);
      $entities[$entity->id()][$langcode] = $entity;
    }
    $count_langcodes = count(array_unique($langcodes));

    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    if ($count_langcodes > 1) {
      // Render each entity separate if we do have more than one translation.
      // @todo It should be possible to use viewMultiple even if you get
      //   more than one language. See https://drupal.org/node/2073217.
      foreach ($entities as $entity_translation) {
        foreach ($entity_translation as $langcode => $entity) {
          $entity = $entity->getTranslation($langcode);
          $this->build[$entity->id()][$langcode] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $langcode);
        }
      }
    }
    else {
      $langcode = reset($langcodes);
      $entity_translations = array();
      foreach ($entities as $entity_translation) {
        $entity = $entity_translation[$langcode];
        $entity_translations[$entity->id()] = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity;
      }
      $this->build = $view_builder->viewMultiple($entity_translations, $this->view->rowPlugin->options['view_mode'], $langcode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();
    $langcode = $this->getLangcode($row);
    if (isset($this->build[$entity_id][$langcode])) {
      return $this->build[$entity_id][$langcode];
    }
    else {
      return $this->build[$entity_id];
    }
  }

  /**
   * Returns the language code associated to the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   A language code.
   */
  protected function getLangcode(ResultRow $row) {
    return $row->_entity->getUntranslated()->language()->id;
  }

}
