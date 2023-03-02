<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 *
 * @internal
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase {

  /**
   * The entity type for which the display mode is being created.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->targetEntityTypeId = $entity_type_id;
    $form = parent::buildForm($form, $form_state);

    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info_service->getAllBundleInfo();

    // Change replace_pattern to avoid undesired dots.
    $form['id']['#machine_name']['replace_pattern'] = '[^a-z0-9_]+';
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    $form['#title'] = $this->t('Add new %label for @entity-type', ['@entity-type' => $definition->getLabel(), '%label' => $this->entityType->getSingularLabel()]);
    $form['data']['template'] = [
      '#type' => 'inline_template',
      '#template' => 'Enable view mode for the following bundles:'
    ];

    $form['data']['bundles'] = [];
    $bundles_by_entity = $bundles[$definition->id()];
    foreach ($bundles_by_entity as $bundle) {
      $form['data']['bundles'][] = [
        '#type' => 'checkbox',
        '#title' => $bundle['label'],
      ];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValueForElement($form['id'], $this->targetEntityTypeId . '.' . $form_state->getValue('id'));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->get('field_ui_base_route') || !$definition->hasViewBuilderClass()) {
      throw new NotFoundHttpException();
    }

    $this->entity->setTargetType($this->targetEntityTypeId);
  }

}
