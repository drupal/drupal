<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserMultipleCancelConfirm.
 */

namespace Drupal\user\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\user\TempStoreFactory;
use Drupal\user\UserStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class UserMultipleCancelConfirm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user storage controller.
   *
   * @var \Drupal\user\UserStorageControllerInterface
   */
  protected $userStorage;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserStorageControllerInterface $user_storage
   *   The user storage controller.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(TempStoreFactory $temp_store_factory, ConfigFactoryInterface $config_factory, UserStorageControllerInterface $user_storage, EntityManagerInterface $entity_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->configFactory = $config_factory;
    $this->userStorage = $user_storage;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.tempstore'),
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorageController('user'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_multiple_cancel_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel these user accounts?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel accounts');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Retrieve the accounts to be canceled from the temp store.
    $accounts = $this->tempStoreFactory
      ->get('user_user_operations_cancel')
      ->get($this->currentUser()->id());
    if (!$accounts) {
      return new RedirectResponse($this->urlGenerator()->generateFromPath('admin/people', array('absolute' => TRUE)));
    }

    $form['accounts'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
    foreach ($accounts as $uid => $account) {
      // Prevent user 1 from being canceled.
      if ($uid <= 1) {
        continue;
      }
      $form['accounts'][$uid] = array(
        '#type' => 'hidden',
        '#value' => $uid,
        '#prefix' => '<li>',
        '#suffix' => String::checkPlain($account->label()) . "</li>\n",
      );
    }

    // Output a notice that user 1 cannot be canceled.
    if (isset($accounts[1])) {
      $redirect = (count($accounts) == 1);
      $message = $this->t('The user account %name cannot be canceled.', array('%name' => $accounts[1]->label()));
      drupal_set_message($message, $redirect ? 'error' : 'warning');
      // If only user 1 was selected, redirect to the overview.
      if ($redirect) {
        return new RedirectResponse($this->urlGenerator()->generateFromPath('admin/people', array('absolute' => TRUE)));
      }
    }

    $form['operation'] = array('#type' => 'hidden', '#value' => 'cancel');

    $form['user_cancel_method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('When cancelling these accounts'),
    );

    $form['user_cancel_method'] += user_cancel_methods();

    // Allow to send the account cancellation confirmation mail.
    $form['user_cancel_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require e-mail confirmation to cancel account.'),
      '#default_value' => FALSE,
      '#description' => $this->t('When enabled, the user must confirm the account cancellation via e-mail.'),
    );
    // Also allow to send account canceled notification mail, if enabled.
    $form['user_cancel_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled.'),
      '#default_value' => FALSE,
      '#access' => $this->configFactory->get('user.settings')->get('notify.status_canceled'),
      '#description' => $this->t('When enabled, the user will receive an e-mail notification after the account has been canceled.'),
    );

    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after https://drupal.org/node/1938884.
    $form['actions']['cancel']['#href'] = 'admin/people';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('user_user_operations_cancel')->delete($current_user_id);
    if ($form_state['values']['confirm']) {
      foreach ($form_state['values']['accounts'] as $uid => $value) {
        // Prevent programmatic form submissions from cancelling user 1.
        if ($uid <= 1) {
          continue;
        }
        // Prevent user administrators from deleting themselves without confirmation.
        if ($uid == $current_user_id) {
          $admin_form_mock = array();
          $admin_form_state = $form_state;
          unset($admin_form_state['values']['user_cancel_confirm']);
          // The $user global is not a complete user entity, so load the full
          // entity.
          $account = $this->userStorage->load($uid);
          $admin_form = $this->entityManager->getFormController('user', 'cancel');
          $admin_form->setEntity($account);
          // Calling this directly required to init form object with $account.
          $admin_form->buildForm($admin_form_mock, $admin_form_state, $this->request);
          $admin_form->submit($admin_form_mock, $admin_form_state);
        }
        else {
          user_cancel($form_state['values'], $uid, $form_state['values']['user_cancel_method']);
        }
      }
    }
    $form_state['redirect_route']['route_name'] = 'user.admin_account';
  }

}
