<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Search\UserSearch.
 */

namespace Drupal\user\Plugin\Search;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\search\Annotation\SearchPlugin;
use Drupal\search\Plugin\SearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Executes a keyword search for users against the {users} database table.
 *
 * @SearchPlugin(
 *   id = "user_search",
 *   title = @Translation("Users"),
 *   path = "user"
 * )
 */
class UserSearch extends SearchPluginBase implements AccessibleInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('request'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a UserSearch object.
   *
   * @param Connection $database
   *   The database connection.
   * @param EntityManager $entity_manager
   *   The entity manager.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Connection $database, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, Request $request, array $configuration, $plugin_id, array $plugin_definition) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->request = $request;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    return !empty($account) && $account->hasPermission('access user profiles');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->keywords;
    // Replace wildcards with MySQL/PostgreSQL wildcards.
    $keys = preg_replace('!\*+!', '%', $keys);
    $query = $this->database
      ->select('users')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('users', array('uid'));
    $user_account = $this->request->attributes->get('_account');
    if ($user_account->hasPermission('administer users')) {
      // Administrators can also search in the otherwise private email field, and
      // they don't need to be restricted to only active users.
      $query->fields('users', array('mail'));
      $query->condition($query->orConditionGroup()
        ->condition('name', '%' . $this->database->escapeLike($keys) . '%', 'LIKE')
        ->condition('mail', '%' . $this->database->escapeLike($keys) . '%', 'LIKE')
      );
    }
    else {
      // Regular users can only search via usernames, and we do not show them
      // blocked accounts.
      $query->condition('name', '%' . $this->database->escapeLike($keys) . '%', 'LIKE')
        ->condition('status', 1);
    }
    $uids = $query
      ->limit(15)
      ->execute()
      ->fetchCol();
    $accounts = $this->entityManager->getStorageController('user')->loadMultiple($uids);

    foreach ($accounts as $account) {
      $result = array(
        'title' => $account->getUsername(),
        'link' => url('user/' . $account->id(), array('absolute' => TRUE)),
      );
      if ($user_account->hasPermission('administer users')) {
        $result['title'] .= ' (' . $account->getEmail() . ')';
      }
      $results[] = $result;
    }

    return $results;
  }

}
