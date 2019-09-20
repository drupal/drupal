<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a workspace.
 *
 * @internal
 */
class WorkspaceDeleteForm extends ContentEntityDeleteForm implements WorkspaceFormInterface {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $source_rev_diff = $this->entityTypeManager->getStorage('workspace_association')->getTrackedEntities($this->entity->id());
    $items = [];
    foreach ($source_rev_diff as $entity_type_id => $revision_ids) {
      $label = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
      $items[] = $this->formatPlural(count($revision_ids), '1 @label revision.', '@count @label revisions.', ['@label' => $label]);
    }
    $form['revisions'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('The following will also be deleted:'),
      '#items' => $items,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone, and will also delete all content created in this workspace.');
  }

}
