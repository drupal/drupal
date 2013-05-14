<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldDeleteForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for removing a field instance from a bundle.
 */
class FieldDeleteForm extends ConfirmFormBase implements ControllerInterface {

  /**
   * The field instance being deleted.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  protected $instance;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new FieldDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the field %field?', array('%field' => $this->instance->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return $this->entityManager->getAdminPath($this->instance->entity_type, $this->instance->bundle) . '/fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstance $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');

    $field = $this->instance->getField();
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$this->instance->entity_type][$this->instance->bundle]['label'];

    if ($field && !$field['locked']) {
      $this->instance->delete();
      drupal_set_message(t('The field %field has been deleted from the %type content type.', array('%field' => $this->instance->label(), '%type' => $bundle_label)));
    }
    else {
      drupal_set_message(t('There was a problem removing the %field from the %type content type.', array('%field' => $this->instance->label(), '%type' => $bundle_label)), 'error');
    }

    $admin_path = $this->entityManager->getAdminPath($this->instance->entity_type, $this->instance->bundle);
    $form_state['redirect'] = field_ui_get_destinations(array($admin_path . '/fields'));

    // Fields are purged on cron. However field module prevents disabling modules
    // when field types they provided are used in a field until it is fully
    // purged. In the case that a field has minimal or no content, a single call
    // to field_purge_batch() will remove it from the system. Call this with a
    // low batch limit to avoid administrators having to wait for cron runs when
    // removing instances that meet this criteria.
    field_purge_batch(10);
  }

}
