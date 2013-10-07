<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserAdmin.
 */

namespace Drupal\user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user administrative listing.
 *
 * @todo Convert this to a entity list controller once table sort is supported.
 */
class UserAdmin extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The user storage controller.
   *
   * @var \Drupal\user\UserStorageControllerInterface
   */
  protected $storageController;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * Constructs a new UserAdmin object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\UserStorageControllerInterface $storage_controller
   *   The user storage controller.
   * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
   *   The entity query.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler, UserStorageControllerInterface $storage_controller, QueryInterface $entity_query) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->storageController = $storage_controller;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('entity.manager')->getStorageController('user'),
      $container->get('entity.query')->get('user')
    );
  }

  /**
   * User administrative listing.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function userList() {
    $header = array(
      'username' => array('data' => $this->t('Username'), 'field' => 'name', 'specifier' => 'name'),
      'status' => array('data' => $this->t('Status'), 'field' => 'status', 'specifier' => 'status', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'roles' => array('data' => $this->t('Roles'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'member_for' => array('data' => $this->t('Member for'), 'field' => 'created', 'specifier' => 'created', 'sort' => 'desc', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'access' => array('data' => $this->t('Last access'), 'field' => 'access', 'specifier' => 'access', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'operations' => $this->t('Operations'),
    );

    $this->entityQuery->condition('uid', 0, '<>');
    $this->entityQuery->pager(50);
    $this->entityQuery->tableSort($header);
    $uids = $this->entityQuery->execute();
    $accounts = $this->storageController->loadMultiple($uids);

    $destination = drupal_get_destination();
    $status = array($this->t('blocked'), $this->t('active'));
    $roles = array_map('\Drupal\Component\Utility\String::checkPlain', user_role_names(TRUE));
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $options = array();
    foreach ($accounts as $account) {
      $users_roles = array();
      foreach ($account->getRoles() as $role) {
        if (isset($roles[$role])) {
          $users_roles[] = $roles[$role];
        }
      }
      asort($users_roles);
      $options[$account->id()]['username']['data'] = array(
        '#theme' => 'username',
        '#account' => $account,
      );
      $options[$account->id()]['status'] = $status[$account->isActive()];
      $options[$account->id()]['roles']['data'] = array(
        '#theme' => 'item_list',
        '#items' => $users_roles,
      );
      $options[$account->id()]['member_for'] = format_interval(REQUEST_TIME - $account->getCreatedTime());
      $options[$account->id()]['access'] = $account->access ? $this->t('@time ago', array('@time' => format_interval(REQUEST_TIME - $account->getLastAccessedTime()))) : t('never');
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'href' => 'user/' . $account->id() . '/edit',
        'query' => $destination,
      );
      if ($this->moduleHandler->invoke('content_translation', 'translate_access', array($account))) {
        $links['translate'] = array(
          'title' => $this->t('Translate'),
          'href' => 'user/' . $account->id() . '/translations',
          'query' => $destination,
        );
      }
      $options[$account->id()]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    $build['accounts'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $options,
      '#empty' => $this->t('No people available.'),
    );
    $build['pager'] = array(
      '#theme' =>'pager',
    );

    return $build;
  }

}
