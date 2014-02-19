<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceConfigDeleteForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for removing a field instance from a bundle.
 */
class FieldInstanceConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FieldInstanceConfigDeleteForm object.
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
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the field %field?', array('%field' => $this->entity->getLabel()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return FieldUI::getOverviewRouteInfo($this->entity->entity_type, $this->entity->bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $field = $this->entity->getField();
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$this->entity->entity_type][$this->entity->bundle]['label'];

    if ($field && !$field->locked) {
      $this->entity->delete();
      drupal_set_message($this->t('The field %field has been deleted from the %type content type.', array('%field' => $this->entity->label(), '%type' => $bundle_label)));
    }
    else {
      drupal_set_message($this->t('There was a problem removing the %field from the %type content type.', array('%field' => $this->entity->label(), '%type' => $bundle_label)), 'error');
    }

    $form_state['redirect_route'] = FieldUI::getOverviewRouteInfo($this->entity->entity_type, $this->entity->bundle);

    // Fields are purged on cron. However field module prevents disabling modules
    // when field types they provided are used in a field until it is fully
    // purged. In the case that a field has minimal or no content, a single call
    // to field_purge_batch() will remove it from the system. Call this with a
    // low batch limit to avoid administrators having to wait for cron runs when
    // removing instances that meet this criteria.
    field_purge_batch(10);
  }

}
