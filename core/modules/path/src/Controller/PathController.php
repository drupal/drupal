<?php

/**
 * @file
 * Contains \Drupal\path\Controller\PathController.
 */

namespace Drupal\path\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * Displays the path administration overview page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview(Request $request) {
    $keys = $request->query->get('search');
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
    $destination = $this->getDestinationArray();
    foreach ($this->aliasStorage->getAliasesForAdminListing($header, $keys) as $data) {
      $row = array();
      // @todo Should Path module store leading slashes? See
      //   https://www.drupal.org/node/2430593.
      $row['data']['alias'] = $this->l(Unicode::truncate($data->alias, 50, FALSE, TRUE), Url::fromUserInput($data->source, array(
        'attributes' => array('title' => $data->alias),
      )));
      $row['data']['source'] = $this->l(Unicode::truncate($data->source, 50, FALSE, TRUE), Url::fromUserInput($data->source, array(
        'alias' => TRUE,
        'attributes' => array('title' => $data->source),
      )));
      if ($multilanguage) {
        $row['data']['language_name'] = $this->languageManager()->getLanguageName($data->langcode);
      }

      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('path.admin_edit', ['pid' => $data->pid], ['query' => $destination]),
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('path.delete', ['pid' => $data->pid], ['query' => $destination]),
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
      '#empty' => $this->t('No URL aliases available. <a href=":link">Add URL alias</a>.', array(':link' => $this->url('path.admin_add'))),
    );
    $build['path_pager'] = array('#type' => 'pager');

    return $build;
  }

}
