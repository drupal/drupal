<?php

/**
 * @file
 * Contains \Drupal\path\Controller\PathController.
 */

namespace Drupal\path\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for path routes.
 */
class PathController extends ControllerBase {

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(AliasStorageInterface $alias_storage, AliasManagerInterface $alias_manager) {
    $this->aliasStorage = $alias_storage;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_storage'),
      $container->get('path.alias_manager')
    );
  }

  public function adminOverview($keys) {
    // Add the filter form above the overview table.
    $build['path_admin_filter_form'] = $this->formBuilder()->getForm('Drupal\path\Form\PathFilterForm', $keys);
    // Enable language column if language.module is enabled or if we have any
    // alias with a language.
    $multilanguage = ($this->moduleHandler()->moduleExists('language') || $this->aliasStorage->languageAliasExists());

    $header = array();
    $header[] = array('data' => $this->t('Alias'), 'field' => 'alias', 'sort' => 'asc');
    $header[] = array('data' => $this->t('System'), 'field' => 'source');
    if ($multilanguage) {
      $header[] = array('data' => $this->t('Language'), 'field' => 'langcode');
    }
    $header[] = $this->t('Operations');

    $rows = array();
    $destination = drupal_get_destination();
    foreach ($this->aliasStorage->getAliasesForAdminListing($header, $keys) as $data) {
      $row = array();
      $row['data']['alias'] = l(truncate_utf8($data->alias, 50, FALSE, TRUE), $data->source, array(
        'attributes' => array('title' => $data->alias),
      ));
      $row['data']['source'] = l(truncate_utf8($data->source, 50, FALSE, TRUE), $data->source, array(
        'alias' => TRUE,
        'attributes' => array('title' => $data->source),
      ));
      if ($multilanguage) {
        $row['data']['language_name'] = $this->languageManager()->getLanguageName($data->langcode);
      }

      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'path.admin_edit',
        'route_parameters' => array(
          'pid' => $data->pid,
        ),
        'query' => $destination,
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'path.delete',
        'route_parameters' => array(
          'pid' => $data->pid,
        ),
        'query' => $destination,
      );
      $row['data']['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );

      // If the system path maps to a different URL alias, highlight this table
      // row to let the user know of old aliases.
      if ($data->alias != $this->aliasManager->getAliasByPath($data->source, $data->langcode)) {
        $row['class'] = array('warning');
      }

      $rows[] = $row;
    }

    $build['path_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No URL aliases available. <a href="@link">Add URL alias</a>.', array('@link' => $this->url('path.admin_add'))),
    );
    $build['path_pager'] = array('#theme' => 'pager');

    return $build;
  }

}
