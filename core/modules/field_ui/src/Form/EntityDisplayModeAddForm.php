<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 *
 * @internal
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase
{

  /**
   * The entity type for which the display mode is being created.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL)
  {
    $this->targetEntityTypeId = $entity_type_id;
    $form = parent::buildForm($form, $form_state);

    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info_service->getAllBundleInfo();

    // Change replace_pattern to avoid undesired dots.
    $form['id']['#machine_name']['replace_pattern'] = '[^a-z0-9_]+';
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    $form['#title'] = $this->t('Add new %label for @entity-type', ['@entity-type' => $definition->getLabel(), '%label' => $this->entityType->getSingularLabel()]);

    $bundles_by_entity = [];
    foreach (array_keys($bundles[$definition->id()]) as $bundle) {
      $bundles_by_entity[$bundle] = $bundles[$definition->id()][$bundle]['label'];
    }
    $form['data']['bundles_by_entity'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enable view mode for the following bundles:',
      '#options' => $bundles_by_entity,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $form_state->setValueForElement($form['id'], $this->targetEntityTypeId . '.' . $form_state->getValue('id'));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity()
  {
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->get('field_ui_base_route') || !$definition->hasViewBuilderClass()) {
      throw new NotFoundHttpException();
    }

    $this->entity->setTargetType($this->targetEntityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($entity_type_id, $bundle, $mode)
  {
    $entity_display_repository = \Drupal::service('entity_display.repository');
    return $entity_display_repository->getViewDisplay($entity_type_id, $bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverviewUrl($mode, $bundle)
  {
    $entity_type = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    return Url::fromRoute('entity.entity_view_display.' . $this->targetEntityTypeId . '.view_mode', [
        'view_mode_name' => $mode,
      ] + FieldUI::getRouteBundleParameter($entity_type, $bundle));
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state)
  {
    parent::save($form, $form_state);
    [, $view_mode_name] = explode('.', $form_state->getValue('id'));
    $target_entity_id = $this->targetEntityTypeId;

    foreach ($form_state->getValue('bundles_by_entity') as $bundle => $value) {
      if (!empty($value)) {
        if (!$this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->load($target_entity_id . '.' . $value . '.' . $view_mode_name)) {
          $display = $this->getEntityDisplay($target_entity_id, $bundle, 'default')->createCopy($view_mode_name);
          $display->save();
        }
        $url = $this->getOverviewUrl($view_mode_name, $value);

        $bundle_info_service = \Drupal::service('entity_type.bundle.info');
        $bundles = $bundle_info_service->getAllBundleInfo();
        $bundle_label = $bundles[$target_entity_id][$bundle]['label'];
        $view_mode_label = $form_state->getValue('label');

        $this->messenger()->addStatus($this->t('<a href=":url">Configure the %display_mode view mode for %bundle_label</a>.', ['%display_mode' => $view_mode_label, '%bundle_label' => $bundle_label, ':url' => $url->toString()]));

      }

    }
  }
}
