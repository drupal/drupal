<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
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
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->entity->getTargetEntityTypeId());
    $bundle_label = $bundles[$this->entity->getTargetBundle()]['label'];

    if ($field_storage && !$field_storage->isLocked()) {
      $this->entity->delete();
      $this->messenger()->addStatus($this->t('The field %field has been deleted from the %type content type.', ['%field' => $this->entity->label(), '%type' => $bundle_label]));
    }
    else {
      $this->messenger()->addError($this->t('There was a problem removing the %field from the %type content type.', ['%field' => $this->entity->label(), '%type' => $bundle_label]));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());

    // Fields are purged on cron. However field module prevents disabling modules
    // when field types they provided are used in a field until it is fully
    // purged. In the case that a field has minimal or no content, a single call
    // to field_purge_batch() will remove it from the system. Call this with a
    // low batch limit to avoid administrators having to wait for cron runs when
    // removing fields that meet this criteria.
    field_purge_batch(10);
  }

}
