<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\AssignOwnerNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assigns ownership of a node to a user.
 *
 * @Action(
 *   id = "node_assign_owner_action",
 *   label = @Translation("Change the author of content"),
 *   type = "node"
 * )
 */
class AssignOwnerNode extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new AssignOwnerNode action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->uid = $this->configuration['owner_uid'];
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'owner_uid' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $description = t('The username of the user to which you would like to assign ownership.');
    $count = $this->connection->query("SELECT COUNT(*) FROM {users}")->fetchField();
    $owner_name = '';
    if (is_numeric($this->configuration['owner_uid'])) {
      $owner_name = $this->connection->query("SELECT name FROM {users} WHERE uid = :uid", array(':uid' => $this->configuration['owner_uid']))->fetchField();
    }

    // Use dropdown for fewer than 200 users; textbox for more than that.
    if (intval($count) < 200) {
      $options = array();
      $result = $this->connection->query("SELECT uid, name FROM {users} WHERE uid > 0 ORDER BY name");
      foreach ($result as $data) {
        $options[$data->name] = $data->name;
      }
      $form['owner_name'] = array(
        '#type' => 'select',
        '#title' => t('Username'),
        '#default_value' => $owner_name,
        '#options' => $options,
        '#description' => $description,
      );
    }
    else {
      $form['owner_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Username'),
        '#default_value' => $owner_name,
        '#autocomplete_route_name' => 'user.autocomplete',
        '#size' => '6',
        '#maxlength' => '60',
        '#description' => $description,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    $exists = (bool) $this->connection->queryRange('SELECT 1 FROM {users} WHERE name = :name', 0, 1, array(':name' => $form_state['values']['owner_name']))->fetchField();
    if (!$exists) {
      form_set_error('owner_name', $form_state, t('Enter a valid username.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['owner_uid'] = $this->connection->query('SELECT uid from {users} WHERE name = :name', array(':name' => $form_state['values']['owner_name']))->fetchField();
  }

}
