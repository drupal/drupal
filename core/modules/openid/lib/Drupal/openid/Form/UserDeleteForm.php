<?php

/**
 * @file
 * Contains \Drupal\openid\Form\UserDeleteForm.
 */

namespace Drupal\openid\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to delete a user's Open ID identity.
 */
class UserDeleteForm extends ConfirmFormBase implements ControllerInterface {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The account identity ID.
   *
   * @var int
   */
  protected $aid;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new UserDeleteForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *  The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    $identifier = $this->database->query("SELECT identifier FROM {openid_identities} WHERE uid = :uid AND aid = :aid", array(
      ':uid' => $this->account->id(),
      ':aid' => $this->aid,
    ))->fetchField();
    return t('Are you sure you want to delete the OpenID %identifier for %user?', array('%identifier' => $identifier, '%user' => $this->account->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'user/' . $this->account->id() . '/openid';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'openid_user_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, UserInterface $account = NULL, $aid = NULL) {
    $this->aid = $aid;
    $this->account = $account;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $query = $this->database->delete('openid_identities')
      ->condition('uid', $this->account->id())
      ->condition('aid', $this->aid)
      ->execute();
    if ($query) {
      drupal_set_message(t('OpenID deleted.'));
    }
    $form_state['redirect'] = 'user/' . $this->account->id() . '/openid';
  }

}
