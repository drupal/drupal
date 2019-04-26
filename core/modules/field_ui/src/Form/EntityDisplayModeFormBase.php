<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the generic base class for entity display mode forms.
 */
abstract class EntityDisplayModeFormBase extends EntityForm {

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entityType = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 100,
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#field_prefix' => $this->entity->isNew() ? $this->entity->getTargetType() . '.' : '',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
    ];

    return $form;
  }

  /**
   * Determines if the display mode already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the display mode exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    // Do not allow to add internal 'default' view mode.
    if ($entity_id == 'default') {
      return TRUE;
    }
    return (bool) $this->entityTypeManager
      ->getStorage($this->entity->getEntityTypeId())
      ->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Saved the %label @entity-type.', ['%label' => $this->entity->label(), '@entity-type' => $this->entityType->getLowercaseLabel()]));
    $this->entity->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
