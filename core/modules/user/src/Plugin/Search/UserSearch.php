<?php

namespace Drupal\user\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search\Attribute\Search;
use Drupal\search\Plugin\SearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search for users against the {users} database table.
 */
#[Search(
  id: 'user_search',
  title: new TranslatableMarkup('Users'),
)]
class UserSearch extends SearchPluginBase implements AccessibleInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a UserSearch object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, array $configuration, $plugin_id, $plugin_definition) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addCacheTags(['user_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf(!empty($account) && $account->hasPermission('access user profiles'))->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = [];
    if (!$this->isSearchExecutable()) {
      return $results;
    }

    // Process the keywords.
    $keys = $this->keywords;
    // Escape for LIKE matching.
    $keys = $this->database->escapeLike($keys);
    // Replace wildcards with MySQL/PostgreSQL wildcards.
    $keys = preg_replace('!\*+!', '%', $keys);

    // Run the query to find matching users.
    $query = $this->database
      ->select('users_field_data', 'users')
      ->extend(PagerSelectExtender::class);
    $query->fields('users', ['uid']);
    $query->condition('default_langcode', 1);
    if ($this->currentUser->hasPermission('administer users')) {
      // Administrators can also search in the otherwise private email field,
      // and they don't need to be restricted to only active users.
      $query->fields('users', ['mail']);
      $query->condition($query->orConditionGroup()
        ->condition('name', '%' . $keys . '%', 'LIKE')
        ->condition('mail', '%' . $keys . '%', 'LIKE')
      );
    }
    else {
      // Regular users can only search via usernames, and we do not show them
      // blocked accounts.
      $query->condition('name', '%' . $keys . '%', 'LIKE')
        ->condition('status', 1);
    }
    $uids = $query
      ->limit(15)
      ->execute()
      ->fetchCol();
    $accounts = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    foreach ($accounts as $account) {
      $result = [
        'title' => $account->getDisplayName(),
        'link' => $account->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ];
      if ($this->currentUser->hasPermission('administer users')) {
        $result['title'] .= ' (' . $account->getEmail() . ')';
      }
      $this->addCacheableDependency($account);
      $results[] = $result;
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    $help = [
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('User search looks for user names and partial user names. Example: mar would match usernames mar, delmar, and maryjane.'),
          $this->t('You can use * as a wildcard within your keyword. Example: m*r would match user names mar, delmar, and elementary.'),
        ],
      ],
    ];

    return $help;
  }

}
