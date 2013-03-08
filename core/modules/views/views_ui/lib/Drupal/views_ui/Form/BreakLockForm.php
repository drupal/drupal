<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\BreakLockForm.
 */

namespace Drupal\views_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\views\ViewStorageInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\user\TempStoreFactory;

/**
 * Builds the form to break the lock of an edited view.
 */
class BreakLockForm implements FormInterface, ControllerInterface {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Stores the user tempstore.
   *
   * @var \Drupal\user\TempStore
   */
  protected $tempStore;

  /**
   * Constructs a \Drupal\views_ui\Form\BreakLockForm object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManager $entity_manager, TempStoreFactory $temp_store_factory) {
    $this->entityManager = $entity_manager;
    $this->tempStore = $temp_store_factory->get('views');
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('user.tempstore')
    );
  }

  /**
   * Creates a new instance of this form.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being acted upon.
   *
   * @return array
   *   The built form array.
   */
  public function getForm(ViewStorageInterface $view) {
    return drupal_get_form($this, $view);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_break_lock_confirm';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, ViewStorageInterface $view = NULL) {
    $form_state['view_id'] = $view->id();
    $locked = $this->tempStore->getMetadata($form_state['view_id']);

    if (!$locked) {
      $form['message']['#markup'] = t('There is no lock on view %name to break.', array('%name' => $form_state['view_id']));
      return $form;
    }

    $account = $this->entityManager->getStorageController('user')->load(array($locked->owner));
    return confirm_form($form,
      t('Do you want to break the lock on view %name?', array('%name' => $form_state['view_id'])),
      'admin/structure/views/view/' . $form_state['view_id'],
      t('By breaking this lock, any unsaved changes made by !user will be lost.', array('!user' => theme('username', array('account' => reset($account))))),
      t('Break lock'),
      t('Cancel')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->tempStore->delete($form_state['view_id']);
    $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view_id'];
    drupal_set_message(t('The lock has been broken and you may now edit this view.'));
  }

}
