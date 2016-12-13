<?php

namespace Drupal\content_moderation\Plugin\WorkflowType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ContentModerationState;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Attaches workflows to content entity types and their bundles.
 *
 * @WorkflowType(
 *   id = "content_moderation",
 *   label = @Translation("Content moderation"),
 * )
 */
class ContentModeration extends WorkflowTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function checkWorkflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view content moderation');
    }
    return parent::checkWorkflowAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function decorateState(StateInterface $state) {
    if (isset($this->configuration['states'][$state->id()])) {
      $state = new ContentModerationState($state, $this->configuration['states'][$state->id()]['published'], $this->configuration['states'][$state->id()]['default_revision']);
    }
    else {
      $state = new ContentModerationState($state);
    }
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildStateConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, StateInterface $state = NULL) {
    /** @var \Drupal\content_moderation\ContentModerationState $state */
    $form = [];
    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published.'),
      '#default_value' => isset($state) ? $state->isPublishedState() : FALSE,
    ];

    $form['default_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default revision'),
      '#description' => $this->t('When content reaches this state it should be made the default revision; this is implied for published states.'),
      '#default_value' => isset($state) ? $state->isDefaultRevisionState() : FALSE,
      // @todo Add form #state to force "make default" on when "published" is
      // on for a state.
      // @see https://www.drupal.org/node/2645614
    ];
    return $form;
  }

  /**
   * Gets the entity types the workflow is applied to.
   *
   * @return string[]
   *   The entity types the workflow is applied to.
   */
  public function getEntityTypes() {
    return array_keys($this->configuration['entity_types']);
  }

  /**
   * Gets the bundles of the entity type the workflow is applied to.
   *
   * @param string $entity_type_id
   *   The entity type ID to get the bundles for.
   *
   * @return string[]
   *   The bundles of the entity type the workflow is applied to.
   */
  public function getBundlesForEntityType($entity_type_id) {
    return $this->configuration['entity_types'][$entity_type_id];
  }

  /**
   * Checks if the workflow applies to the supplied entity type and bundle.
   *
   * @return bool
   *   TRUE if the workflow applies to the supplied entity type and bundle.
   *   FALSE if not.
   */
  public function appliesToEntityTypeAndBundle($entity_type_id, $bundle_id) {
    if (isset($this->configuration['entity_types'][$entity_type_id])) {
      return in_array($bundle_id, $this->configuration['entity_types'][$entity_type_id], TRUE);
    }
    return FALSE;
  }

  /**
   * Removes an entity type ID / bundle ID from the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to remove.
   * @param string $bundle_id
   *   The bundle ID to remove.
   */
  public function removeEntityTypeAndBundle($entity_type_id, $bundle_id) {
    $key = array_search($bundle_id, $this->configuration['entity_types'][$entity_type_id], TRUE);
    if ($key !== FALSE) {
      unset($this->configuration['entity_types'][$entity_type_id][$key]);
      if (empty($this->configuration['entity_types'][$entity_type_id])) {
        unset($this->configuration['entity_types'][$entity_type_id]);
      }
      else {
        $this->configuration['entity_types'][$entity_type_id] = array_values($this->configuration['entity_types'][$entity_type_id]);
      }
    }
  }

  /**
   * Add an entity type ID / bundle ID to the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to add.
   * @param string $bundle_id
   *   The bundle ID to add.
   */
  public function addEntityTypeAndBundle($entity_type_id, $bundle_id) {
    if (!$this->appliesToEntityTypeAndBundle($entity_type_id, $bundle_id)) {
      $this->configuration['entity_types'][$entity_type_id][] = $bundle_id;
      natsort($this->configuration['entity_types'][$entity_type_id]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    // This plugin does not store anything per transition.
    return [
      'states' => [],
      'entity_types' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function calculateDependencies() {
    // @todo : Implement calculateDependencies() method.
    return [];
  }

}
