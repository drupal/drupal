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
    $langcode_table = $this->getLangcodeTable($query, $relationship);
    if ($langcode_table) {
      /** @var \Drupal\views\Plugin\views\query\Sql $query */
      $table_alias = $query->ensureTable($langcode_table, $relationship);
      $langcode_key = $this->entityType->getKey('langcode');
      $this->langcodeAlias = $query->addField($table_alias, $langcode_key);
    }
  }

  /**
   * Returns the name of the table holding the "langcode" field.
   *
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query being executed.
   * @param string $relationship
   *   The relationship used by the entity type.
   *
   * @return string
   *   A table name.
   */
  protected function getLangcodeTable(QueryPluginBase $query, $relationship) {
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityType->id());
    $langcode_key = $this->entityType->getKey('langcode');
    $langcode_table = $storage->getTableMapping()->getFieldTableName($langcode_key);

    // If the entity type is revisionable, we need to take into account views of
    // entity revisions. Usually the view will use the entity data table as the
    // query base table, however, in case of an entity revision view, we need to
    // use the revision table or the revision data table, depending on which one
    // is being used as query base table.
    if ($this->entityType->isRevisionable()) {
      $query_base_table = $query->relationships[$relationship]['base'] ??
        $this->view->storage->get('base_table');
      $revision_table = $storage->getRevisionTable();
      $revision_data_table = $storage->getRevisionDataTable();
      if ($query_base_table === $revision_table) {
        $langcode_table = $revision_table;
      }
      elseif ($query_base_table === $revision_data_table) {
        $langcode_table = $revision_data_table;
      }
    }

    return $langcode_table;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderByRelationship(array $result, string $relationship): void {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($this->entityType->id());

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      if ($entity = $this->getEntity($row, $relationship)) {
        $entity->view = $this->view;
        $langcode = $this->getLangcodeByRelationship($row, $relationship);
        $this->build[$entity->id()][$langcode] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $langcode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renderByRelationship(ResultRow $row, string $relationship): array {
    if ($entity = $this->getEntity($row, $relationship)) {
      $entity_id = $entity->id();
      return $this->build[$entity_id][$this->getLangcodeByRelationship($row, $relationship)];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    return $row->{$this->langcodeAlias} ?? $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['languages:' . LanguageInterface::TYPE_CONTENT];
  }

}
