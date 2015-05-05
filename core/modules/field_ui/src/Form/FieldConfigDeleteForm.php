<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldConfigDeleteForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for removing a field from a bundle.
 */
class FieldConfigDeleteForm extends EntityDeleteForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FieldConfigDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
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
    $bundles = $this->entityManager->getBundleInfo($this->entity->getTargetEntityTypeId());
    $bundle_label = $bundles[$this->entity->getTargetBundle()]['label'];

    if ($field_storage && !$field_storage->isLocked()) {
      $this->entity->delete();
      drupal_set_message($this->t('The field %field has been deleted from the %type content type.', array('%field' => $this->entity->label(), '%type' => $bundle_label)));
    }
    else {
      drupal_set_message($this->t('There was a problem removing the %field from the %type content type.', array('%field' => $this->entity->label(), '%type' => $bundle_label)), 'error');
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
