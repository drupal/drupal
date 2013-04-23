<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\SetDelete.
 */

namespace Drupal\shortcut\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\shortcut\Plugin\Core\Entity\Shortcut;

/**
 * Builds the shortcut set deletion form.
 */
class SetDelete extends ConfirmFormBase implements ControllerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The shortcut set being deleted.
   *
   * @var \Drupal\shortcut\Plugin\Core\Entity\Shortcut
   */
  protected $shortcut;

  /**
   * Constructs a SetDelete object.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {

    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'shortcut_set_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the shortcut set %title?', array('%title' => $this->shortcut->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/config/user-interface/shortcut/manage/' . $this->shortcut->id();
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
  public function buildForm(array $form, array &$form_state, Shortcut $shortcut = NULL) {
    $this->shortcut = $shortcut;

    // Find out how many users are directly assigned to this shortcut set, and
    // make a message.
    $number = $this->database->query('SELECT COUNT(*) FROM {shortcut_set_users} WHERE set_name = :name', array(':name' => $this->shortcut->id()))->fetchField();
    $info = '';
    if ($number) {
      $info .= '<p>' . format_plural($number,
        '1 user has chosen or been assigned to this shortcut set.',
        '@count users have chosen or been assigned to this shortcut set.') . '</p>';
    }

    // Also, if a module implements hook_shortcut_default_set(), it's possible
    // that this set is being used as a default set. Add a message about that too.
    if ($this->moduleHandler->getImplementations('shortcut_default_set')) {
      $info .= '<p>' . t('If you have chosen this shortcut set as the default for some or all users, they may also be affected by deleting it.') . '</p>';
    }

    $form['info'] = array(
      '#markup' => $info,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->shortcut->delete();
    $form_state['redirect'] = 'admin/config/user-interface/shortcut';
    drupal_set_message(t('The shortcut set %title has been deleted.', array('%title' => $this->shortcut->label())));
  }

}
