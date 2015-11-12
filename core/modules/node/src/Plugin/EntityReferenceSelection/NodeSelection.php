<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\EntityReferenceSelection\NodeSelection.
 */

namespace Drupal\node\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:node",
 *   label = @Translation("Node selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class NodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#title'] = $this->t('Content types');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
      $query->condition('status', NODE_PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $node = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable node, it needs to published.
    /** @var \Drupal\node\NodeInterface $node */
    $node->setPublished(TRUE);

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
      $entities = array_filter($entities, function ($node) {
        /** @var \Drupal\node\NodeInterface $node */
        return $node->isPublished();
      });
    }
    return $entities;
  }

}
