<?php

/**
 * @file
 * Contains \Drupal\action\Controller\ActionController.
 */

namespace Drupal\action\Controller;

use Drupal\action\Form\ActionAdminManageForm;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller providing page callbacks for the action admin interface.
 */
class ActionController implements ControllerInterface {

  /**
   * The database connection object for this controller.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ActionController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection object to be used by this controller.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Displays an overview of available and configured actions.
   *
   * @return
   *   A render array containing a table of existing actions and the advanced
   *   action creation form.
   */
  public function adminManage() {
    action_synchronize();
    $actions = action_list();
    $actions_map = action_actions_map($actions);
    $options = array();
    $unconfigurable = array();

    foreach ($actions_map as $key => $array) {
      if ($array['configurable']) {
        $options[$key] = $array['label'] . '...';
      }
      else {
        $unconfigurable[] = $array;
      }
    }

    $row = array();
    $instances_present = $this->database->query("SELECT aid FROM {actions} WHERE parameters <> ''")->fetchField();
    $header = array(
      array('data' => t('Action type'), 'field' => 'type'),
      array('data' => t('Label'), 'field' => 'label'),
      $instances_present ? t('Operations') : '',
    );
    $query = $this->database->select('actions')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $result = $query
      ->fields('actions')
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $action) {
      $row = array();
      $row[] = $action->type;
      $row[] = check_plain($action->label);
      $links = array();
      if ($action->parameters) {
        $links['configure'] = array(
          'title' => t('configure'),
          'href' => "admin/config/system/actions/configure/$action->aid",
        );
        $links['delete'] = array(
          'title' => t('delete'),
          'href' => "admin/config/system/actions/delete/$action->aid",
        );
      }
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );

      $rows[] = $row;
    }

    if ($rows) {
      $pager = theme('pager');
      if (!empty($pager)) {
        $rows[] = array(array('data' => $pager, 'colspan' => '3'));
      }
      $build['action_header'] = array(
        '#markup' => '<h3>' . t('Available actions:') . '</h3>'
      );
      $build['action_table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      );
    }

    if ($actions_map) {
      $build['action_admin_manage_form'] = drupal_get_form(new ActionAdminManageForm(), $options);
    }

    return $build;
  }

  /**
   * Removes actions that are in the database but not supported by any enabled module.
   */
  public function adminRemoveOrphans() {
    action_synchronize(TRUE);
    return new RedirectResponse(url('admin/config/system/actions', array('absolute' => TRUE)));
  }

}
