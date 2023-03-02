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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Saved the %label @entity-type.', ['%label' => $this->entity->label(), '@entity-type' => $this->entityType->getSingularLabel()]));
//    $this->entity->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
//    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    $form_values = $form_state->getValues();
    xdebug_break();

    // Handle the 'display modes' checkboxes if present.
//    if ($this->entity->getMode() == 'default' && !empty($form_values['display_modes_custom'])) {
//      $display_modes = $this->getDisplayModes();
//      $current_statuses = $this->getDisplayStatuses();
//
//      $statuses = [];
//      foreach ($form_values['display_modes_custom'] as $mode => $value) {
//        if (!empty($value) && empty($current_statuses[$mode])) {
//          // If no display exists for the newly enabled view mode, initialize
//          // it with those from the 'default' view mode, which were used so
//          // far.
//          if (!$this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->load($this->entity->getTargetEntityTypeId() . '.' . $this->entity->getTargetBundle() . '.' . $mode)) {
//            $display = $this->getEntityDisplay($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle(), 'default')->createCopy($mode);
//            $display->save();
//          }
//
//          $display_mode_label = $display_modes[$mode]['label'];
//          $url = $this->getOverviewUrl($mode);
//          $this->messenger()->addStatus($this->t('The %display_mode mode now uses custom display settings. You might want to <a href=":url">configure them</a>.', ['%display_mode' => $display_mode_label, ':url' => $url->toString()]));
//        }
//        $statuses[$mode] = !empty($value);
//      }

//      $this->saveDisplayStatuses($statuses);
    }

//    $this->messenger()->addStatus($this->t('Foo Your settings have been saved.'));



}
