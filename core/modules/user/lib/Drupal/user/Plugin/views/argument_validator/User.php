<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_validator\User.
 */

namespace Drupal\user\Plugin\views\argument_validator;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate whether an argument is a valid user.
 *
 * This supports either numeric arguments (UID) or strings (username) and
 * converts either one into the user's UID.  This validator also sets the
 * argument's title to the username.
 *
 * @ViewsArgumentValidator(
 *   id = "user",
 *   title = @Translation("User")
 * )
 */
class User extends ArgumentValidatorPluginBase {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = array('default' => 'uid');
    $options['restrict_roles'] = array('default' => FALSE, 'bool' => TRUE);
    $options['roles'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Type of user filter value to allow'),
      '#options' => array(
        'uid' => t('Only allow numeric UIDs'),
        'name' => t('Only allow string usernames'),
        'either' => t('Allow both numeric UIDs and string usernames'),
      ),
      '#default_value' => $this->options['type'],
    );

    $form['restrict_roles'] = array(
      '#type' => 'checkbox',
      '#title' => t('Restrict user based on role'),
      '#default_value' => $this->options['restrict_roles'],
    );

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Restrict to the selected roles'),
      '#options' => array_map('check_plain', user_role_names(TRUE)),
      '#default_value' => $this->options['roles'],
      '#description' => t('If no roles are selected, users from any role will be allowed.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[validate][options][user][restrict_roles]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['roles'] = array_filter($options['roles']);
  }

  public function validateArgument($argument) {
    $type = $this->options['type'];
    // is_numeric() can return false positives, so we ensure it's an integer.
    // However, is_integer() will always fail, since $argument is a string.
    if (is_numeric($argument) && $argument == (int)$argument) {
      if ($type == 'uid' || $type == 'either') {
        if ($argument == \Drupal::currentUser()->id()) {
          // If you assign an object to a variable in PHP, the variable
          // automatically acts as a reference, not a copy, so we use
          // clone to ensure that we don't actually mess with the
          // real current user object.
          $account = clone \Drupal::currentUser();
        }
        $condition = 'uid';
      }
    }
    else {
      if ($type == 'name' || $type == 'either') {
        $name = \Drupal::currentUser()->getUserName() ?: \Drupal::config('user.settings')->get('anonymous');
        if ($argument == $name) {
          $account = clone \Drupal::currentUser();
        }
        $condition = 'name';
      }
    }

    // If we don't have a WHERE clause, the argument is invalid.
    if (empty($condition)) {
      return FALSE;
    }

    if (!isset($account)) {
      $uid = $this->database->select('users', 'u')
        ->fields('u', array('uid'))
        ->condition($condition, $argument)
        ->execute()
        ->fetchField();

      if ($uid === FALSE) {
        // User not found.
        return FALSE;
      }
    }
    $account = user_load($uid);

    // See if we're filtering users based on roles.
    if (!empty($this->options['restrict_roles']) && !empty($this->options['roles'])) {
      $roles = $this->options['roles'];
      if (!(bool) array_intersect($account->getRoles(), $roles)) {
        return FALSE;
      }
    }

    $this->argument->argument = $account->id();
    $this->argument->validated_title = check_plain(user_format_name($account));
    return TRUE;
  }

  public function processSummaryArguments(&$args) {
    // If the validation says the input is an username, we should reverse the
    // argument so it works for example for generation summary urls.
    $uids_arg_keys = array_flip($args);
    if ($this->options['type'] == 'name') {
      $users = user_load_multiple($args);
      foreach ($users as $uid => $account) {
        $args[$uids_arg_keys[$uid]] = $account->label();
      }
    }
  }

}
