<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldPurger;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\FieldUI;

/**
 * Provides a form for removing a field from a bundle.
 *
 * @internal
 */
class FieldConfigDeleteForm extends EntityDeleteForm {

  /**
   * The field purger service.
   */
  protected FieldPurger $fieldPurger;

  public function __construct(
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityTypeManagerInterface $entityTypeManager,
    ?FieldPurger $fieldPurger = NULL,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    if (!$fieldPurger instanceof FieldPurger) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $fieldPurger argument is deprecated in drupal:11.4.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3494023', E_USER_DEPRECATED);
      $this->fieldPurger = \Drupal::service(FieldPurger::class);
    }
    else {
      $this->fieldPurger = $fieldPurger;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // If we are adding the field storage as a dependency to delete, then that
    // will list the field as a dependency. That is confusing, so remove it.
    // Also remove the entity type and the whole entity deletions details
    // element if nothing else is in there.
    if (isset($form['entity_deletes']['field_config']['#items']) && isset($form['entity_deletes']['field_config']['#items'][$this->entity->id()])) {
      unset($form['entity_deletes']['field_config']['#items'][$this->entity->id()]);
      if (empty($form['entity_deletes']['field_config']['#items'])) {
        unset($form['entity_deletes']['field_config']);
        if (!Element::children($form['entity_deletes'])) {
          $form['entity_deletes']['#access'] = FALSE;
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigNamesToDelete(ConfigEntityInterface $entity) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity->getFieldStorageDefinition();
    $config_names = [$entity->getConfigDependencyName()];

    // If there is only one bundle left for this field storage, it will be
    // deleted too, notify the user about dependencies.
    if (count($field_storage->getBundles()) <= 1) {
      $config_names[] = $field_storage->getConfigDependencyName();
    }
    return $config_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field_storage = $this->entity->getFieldStorageDefinition();
    $target_entity_type_id = $this->entity->getTargetEntityTypeId();
    $target_bundle = $this->entity->getTargetBundle();
    $target_entity_definition = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $target_entity_bundle_entity_type_id = $target_entity_definition->getBundleEntityType();
    if (empty($target_entity_bundle_entity_type_id)) {
      $source_label = $this->t('entity type');
    }
    else {
      $target_entity_bundle_entity_type_definition = $this->entityTypeManager->getDefinition($target_entity_bundle_entity_type_id);
      $source_label = strtolower($target_entity_bundle_entity_type_definition->getLabel());
    }
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($target_entity_type_id);
    $bundle_label = $bundles[$target_bundle]['label'];

    if ($field_storage && !$field_storage->isLocked()) {
      $this->entity->delete();
      $this->messenger()->addStatus($this->t('The field %field has been deleted from the %type %source_label.', [
        '%field' => $this->entity->label(),
        '%type' => $bundle_label,
        '%source_label' => $source_label,
      ]));
    }
    else {
      $this->messenger()->addError($this->t('There was a problem removing the %field from the %type %source_label.', [
        '%field' => $this->entity->label(),
        '%type' => $bundle_label,
        '%source_label' => $source_label,
      ]));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());

    // Fields are purged on cron. However, the field module prevents disabling
    // modules when field types they provided are used in a field until it is
    // fully purged. In the case that a field has minimal or no content, a
    // single call to \Drupal\Core\Field\FieldPurger::purgeBatch() will remove
    // it from the system. Call this with a low batch limit to  avoid
    // administrators having to wait for cron runs when removing fields that
    // meet this criteria.
    $this->fieldPurger->purgeBatch(10);
  }

}
