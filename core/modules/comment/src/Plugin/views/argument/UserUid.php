<?php

namespace Drupal\comment\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The views user ID argument handler.
 *
 * Accepts a user ID to check for nodes that the user posted or commented on.
 *
 * @ingroup views_argument_handlers
  */
#[ViewsArgument(
  id: 'argument_comment_user_uid',
)]
class UserUid extends ArgumentPluginBase {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a \Drupal\comment\Plugin\views\argument\UserUid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!$this->argument) {
      $title = \Drupal::config('user.settings')->get('anonymous');
    }
    else {
      $title = $this->database->query('SELECT [name] FROM {users_field_data} WHERE [uid] = :uid AND [default_langcode] = 1', [':uid' => $this->argument])->fetchField();
    }
    if (empty($title)) {
      return $this->t('No user');
    }

    return $title;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultActions($which = NULL) {
    // Disallow summary views on this argument.
    if (!$which) {
      $actions = parent::defaultActions();
      unset($actions['summary asc']);
      unset($actions['summary desc']);
      return $actions;
    }

    if ($which != 'summary asc' && $which != 'summary desc') {
      return parent::defaultActions($which);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Use the table definition to correctly add this user ID condition.
    if ($this->table != 'comment_field_data') {
      $subselect = $this->database->select('comment_field_data', 'c');
      $subselect->addField('c', 'cid');
      $subselect->condition('c.uid', $this->argument);

      $entity_id = $this->definition['entity_id'];
      $entity_type = $this->definition['entity_type'];
      $subselect->where("[c].[entity_id] = [$this->tableAlias].[$entity_id]");
      $subselect->condition('c.entity_type', $entity_type);

      $condition = ($this->view->query->getConnection()->condition('OR'))
        ->condition("$this->tableAlias.uid", $this->argument, '=')
        ->exists($subselect);

      $this->query->addWhere(0, $condition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Numerical', [], ['context' => 'Sort order']);
  }

}
