<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\SwitchShortcutSet.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut\ShortcutSetStorageInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the shortcut set switch form.
 */
class SwitchShortcutSet extends FormBase {

  /**
   * The account the shortcut set is for.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The shortcut set storage.
   *
   * @var \Drupal\shortcut\ShortcutSetStorageInterface
   */
  protected $shortcutSetStorage;

  /**
   * Constructs a SwitchShortcutSet object.
   *
   * @param \Drupal\shortcut\ShortcutSetStorageInterface $shortcut_set_storage
   *   The shortcut set storage.
   */
  public function __construct(ShortcutSetStorageInterface $shortcut_set_storage) {
    $this->shortcutSetStorage = $shortcut_set_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('shortcut_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcut_set_switch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $account = $this->currentUser();

    $this->user = $user;

    // Prepare the list of shortcut sets.
    $options = array_map(function (ShortcutSet $set) {
      return $set->label();
    }, $this->shortcutSetStorage->loadMultiple());

    $current_set = shortcut_current_displayed_set($this->user);

    // Only administrators can add shortcut sets.
    $add_access = $account->hasPermission('administer shortcuts');
    if ($add_access) {
      $options['new'] = $this->t('New set');
    }

    $account_is_user = $this->user->id() == $account->id();
    if (count($options) > 1) {
      $form['set'] = array(
        '#type' => 'radios',
        '#title' => $account_is_user ? $this->t('Choose a set of shortcuts to use') : $this->t('Choose a set of shortcuts for this user'),
        '#options' => $options,
        '#default_value' => $current_set->id(),
      );

      $form['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#description' => $this->t('The new set is created by copying items from your default shortcut set.'),
        '#access' => $add_access,
        '#states' => array(
          'visible' => array(
            ':input[name="set"]' => array('value' => 'new'),
          ),
          'required' => array(
            ':input[name="set"]' => array('value' => 'new'),
          ),
        ),
      );
      $form['id'] = array(
        '#type' => 'machine_name',
        '#machine_name' => array(
          'exists' => array($this, 'exists'),
          'replace_pattern' => '[^a-z0-9-]+',
          'replace' => '-',
        ),
        // This ID could be used for menu name.
        '#maxlength' => 23,
        '#states' => array(
          'required' => array(
            ':input[name="set"]' => array('value' => 'new'),
          ),
        ),
        '#required' => FALSE,
      );

      if (!$account_is_user) {
        $default_set = $this->shortcutSetStorage->getDefaultSet($this->user);
        $form['new']['#description'] = $this->t('The new set is created by copying items from the %default set.', array('%default' => $default_set->label()));
      }

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Change set'),
      );
    }
    else {
      // There is only 1 option, so output a message in the $form array.
      $form['info'] = array(
        '#markup' => '<p>' . $this->t('You are currently using the %set-name shortcut set.', array('%set-name' => $current_set->label())) . '</p>',
      );
    }

    return $form;
  }

  /**
   * Determines if a shortcut set exists already.
   *
   * @param string $id
   *   The set ID to check.
   *
   * @return bool
   *   TRUE if the shortcut set exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) $this->shortcutSetStorage->getQuery()
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('set') == 'new') {
      // Check to prevent creating a shortcut set with an empty title.
      if (trim($form_state->getValue('label')) == '') {
        $form_state->setErrorByName('label', $this->t('The new set label is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser();

    $account_is_user = $this->user->id() == $account->id();
    if ($form_state->getValue('set') == 'new') {
      // Save a new shortcut set with links copied from the user's default set.
      /* @var \Drupal\shortcut\Entity\ShortcutSet $set */
      $set = $this->shortcutSetStorage->create(array(
        'id' => $form_state->getValue('id'),
        'label' => $form_state->getValue('label'),
      ));
      $set->save();
      $replacements = array(
        '%user' => $this->user->label(),
        '%set_name' => $set->label(),
        ':switch-url' => $this->url('<current>'),
      );
      if ($account_is_user) {
        // Only administrators can create new shortcut sets, so we know they have
        // access to switch back.
        drupal_set_message($this->t('You are now using the new %set_name shortcut set. You can edit it from this page or <a href=":switch-url">switch back to a different one.</a>', $replacements));
      }
      else {
        drupal_set_message($this->t('%user is now using a new shortcut set called %set_name. You can edit it from this page.', $replacements));
      }
      $form_state->setRedirect(
        'entity.shortcut_set.customize_form',
        array('shortcut_set' => $set->id())
      );
    }
    else {
      // Switch to a different shortcut set.
      /* @var \Drupal\shortcut\Entity\ShortcutSet $set */
      $set = $this->shortcutSetStorage->load($form_state->getValue('set'));
      $replacements = array(
        '%user' => $this->user->getDisplayName(),
        '%set_name' => $set->label(),
      );
      drupal_set_message($account_is_user ? $this->t('You are now using the %set_name shortcut set.', $replacements) : $this->t('%user is now using the %set_name shortcut set.', $replacements));
    }

    // Assign the shortcut set to the provided user account.
    $this->shortcutSetStorage->assignUser($set, $this->user);
  }

  /**
   * Checks access for the shortcut set switch form.
   *
   * @param \Drupal\user\UserInterface $user
   *   (optional) The owner of the shortcut set.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(UserInterface $user = NULL) {
    return shortcut_set_switch_access($user);
  }

}
