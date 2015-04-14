<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\TranslationLanguageRenderer.
 */

namespace Drupal\views\Entity\Render;

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders entity translations in their active language.
 */
class TranslationLanguageRenderer extends RendererBase {

  /**
   * Stores the field alias of the langcode column.
   *
   * @var string
   */
  protected $langcodeAlias;

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
    // There is no point in getting the language, in case the site is not
    // multilingual.
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    // If the data table is defined, we use the translation language as render
    // language, otherwise we fall back to the default entity language, which is
    // stored in the revision table for revisionable entity types.
    $langcode_key = $this->entityType->getKey('langcode');
    foreach (array('data_table', 'revision_table', 'base_table') as $key) {
      if ($table = $this->entityType->get($key)) {
        $table_alias = $query->ensureTable($table, $relationship);
        $this->langcodeAlias = $query->addField($table_alias, $langcode_key);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      $entity = $row->_entity;
      $entity->view = $this->view;
      $langcode = $this->getLangcode($row);
      $this->build[$entity->id()][$langcode] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $this->getLangcode($row));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();
    $langcode = $this->getLangcode($row);
    return $this->build[$entity_id][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    return isset($row->{$this->langcodeAlias}) ? $row->{$this->langcodeAlias} : $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['languages:' . LanguageInterface::TYPE_CONTENT];
  }
}
