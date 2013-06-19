<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorageController extends DatabaseStorageControllerNG implements TermStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildPropertyQuery().
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if (isset($values['name'])) {
      $entity_query->condition('name', $values['name'], 'LIKE');
      unset($values['name']);
    }
    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    drupal_static_reset('taxonomy_get_tree');
    drupal_static_reset('taxonomy_get_tree:parents');
    drupal_static_reset('taxonomy_get_tree:terms');
    drupal_static_reset('taxonomy_term_load_parents');
    drupal_static_reset('taxonomy_term_load_parents_all');
    drupal_static_reset('taxonomy_term_load_children');
    parent::resetCache($ids);
  }

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageControllerNG::baseFieldDefintions().
   */
  public function baseFieldDefinitions() {
    $properties['tid'] = array(
      'label' => t('Term ID'),
      'description' => t('The term ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The term UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['vid'] = array(
      'label' => t('Vocabulary ID'),
      'description' => t('The ID of the vocabulary to which the term is assigned.'),
      'type' => 'string_field',
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The term language code.'),
      'type' => 'language_field',
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('The term name.'),
      'type' => 'string_field',
    );
    $properties['description'] = array(
      'label' => t('Description'),
      'description' => t('A description of the term'),
      'type' => 'string_field',
    );
    // @todo Combine with description.
    $properties['format'] = array(
      'label' => t('Description format'),
      'description' => t('The filter format ID of the description.'),
      'type' => 'string_field',
    );
    $properties['weight'] = array(
      'label' => t('Weight'),
      'description' => t('The weight of this term in relation to other terms.'),
      'type' => 'integer_field',
      'settings' => array('default_value' => 0),
    );
    $properties['parent'] = array(
      'label' => t('Term Parents'),
      'description' => t('The parents of this term.'),
      'type' => 'integer_field',
      // Save new terms with no parents by default.
      'settings' => array('default_value' => 0),
      'computed' => TRUE,
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {
    $this->database->delete('taxonomy_term_hierarchy')
      ->condition('tid', $tids)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {
    $query = $this->database->insert('taxonomy_term_hierarchy')
      ->fields(array('tid', 'parent'));

    foreach ($term->parent as $parent) {
      $query->values(array(
        'tid' => $term->id(),
        'parent' => (int) $parent->value,
      ));
    }
    $query->execute();
  }

}
