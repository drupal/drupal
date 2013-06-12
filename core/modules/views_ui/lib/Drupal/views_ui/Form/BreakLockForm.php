<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\BreakLockForm.
 */

namespace Drupal\views_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\views\ViewStorageInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\user\TempStoreFactory;

/**
 * Builds the form to break the lock of an edited view.
 */
class BreakLockForm extends ConfirmFormBase implements ControllerInterface {

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
   * The view being deleted.
   *
   * @var \Drupal\views\ViewStorageInterface
   */
  protected $view;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('user.tempstore')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_break_lock_confirm';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Do you want to break the lock on view %name?', array('%name' => $this->view->id()));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getDescription().
   */
  protected function getDescription() {
    $locked = $this->tempStore->getMetadata($this->view->id());
    $accounts = $this->entityManager->getStorageController('user')->load(array($locked->owner));
    return t('By breaking this lock, any unsaved changes made by !user will be lost.', array('!user' => theme('username', array('account' => reset($accounts)))));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/structure/views/view/' . $this->view->id();
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  protected function getConfirmText() {
    return t('Break lock');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, ViewStorageInterface $view = NULL) {
    $this->view = $view;
    if (!$this->tempStore->getMetadata($this->view->id())) {
      $form['message']['#markup'] = t('There is no lock on view %name to break.', array('%name' => $this->view->id()));
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->tempStore->delete($this->view->id());
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->view->id();
    drupal_set_message(t('The lock has been broken and you may now edit this view.'));
  }

}
