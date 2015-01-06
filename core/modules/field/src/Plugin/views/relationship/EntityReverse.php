<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\views\relationship\EntityReverse.
 */

namespace Drupal\field\Plugin\views\relationship;

use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handlers which reverse entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_reverse")
 */
class EntityReverse extends RelationshipPluginBase  {

  /**
   * Constructs an EntityReverse object.
   *
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $this->ensureMyTable();
    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = array(
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => $this->definition['field table'],
      'field' => $this->definition['field field'],
      'adjusted' => TRUE
    );
    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['join_extra'])) {
      $first['extra'] = $this->definition['join_extra'];
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $first_join = $this->joinManager->createInstance($id, $first);


    $this->first_alias = $this->query->addTable($this->definition['field table'], $this->relationship, $first_join);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = array(
      'left_table' => $this->first_alias,
      'left_field' => 'entity_id',
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
      'adjusted' => TRUE
    );

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $second_join = $this->joinManager->createInstance($id, $second);
    $second_join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $this->definition['field_name'] . '_' . $this->table;

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

}
