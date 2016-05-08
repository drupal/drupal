<?php

namespace Drupal\views\Entity\Render;

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders entity translations in their row language.
 */
class TranslationLanguageRenderer extends EntityTranslationRendererBase {

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
    // In order to render in the translation language of the entity, we need
    // to add the language code of the entity to the query. Skip if the site
    // is not multilingual or the entity is not translatable.
    if (!$this->languageManager->isMultilingual() || !$this->entityType->hasKey('langcode')) {
      return;
    }
    $langcode_key = $this->entityType->getKey('langcode');
    $storage = \Drupal::entityManager()->getStorage($this->entityType->id());

    if ($table = $storage->getTableMapping()->getFieldTableName($langcode_key)) {
      $table_alias = $query->ensureTable($table, $relationship);
      $this->langcodeAlias = $query->addField($table_alias, $langcode_key);
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
