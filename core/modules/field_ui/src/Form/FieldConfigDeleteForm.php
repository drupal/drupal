<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for removing a field from a bundle.
 *
 * @internal
 */
class FieldConfigDeleteForm extends EntityDeleteForm {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new FieldConfigDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, ?EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    if (!$entity_type_manager) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_type_manager argument is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3396525', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::service('entity_type.manager');
    }
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
    );
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

    // Fields are purged on cron. However field module prevents disabling
    // modules when field types they provided are used in a field until it is
    // fully purged. In the case that a field has minimal or no content, a
    // single call to field_purge_batch() will remove it from the system. Call
    // this with a low batch limit to avoid administrators having to wait for
    // cron runs when removing fields that meet this criteria.
    field_purge_batch(10);
  }

}
