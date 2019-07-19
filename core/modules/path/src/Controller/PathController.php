<?php

namespace Drupal\path\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
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
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function adminOverview(Request $request) {
    $keys = $request->query->get('search');
    // Add the filter form above the overview table.
    $build['path_admin_filter_form'] = $this->formBuilder()->getForm('Drupal\path\Form\PathFilterForm', $keys);
    // Enable language column if language.module is enabled or if we have any
    // alias with a language.
    $multilanguage = ($this->moduleHandler()->moduleExists('language') || $this->aliasStorage->languageAliasExists());

    $header = [];
    $header[] = ['data' => $this->t('Alias'), 'field' => 'alias', 'sort' => 'asc'];
    $header[] = ['data' => $this->t('System'), 'field' => 'source'];
    if ($multilanguage) {
      $header[] = ['data' => $this->t('Language'), 'field' => 'langcode'];
    }
    $header[] = $this->t('Operations');

    $rows = [];
    $destination = $this->getDestinationArray();
    foreach ($this->aliasStorage->getAliasesForAdminListing($header, $keys) as $data) {
      $row = [];
      // @todo Should Path module store leading slashes? See
      //   https://www.drupal.org/node/2430593.
      $row['data']['alias'] = Link::fromTextAndUrl(Unicode::truncate($data->alias, 50, FALSE, TRUE), Url::fromUserInput($data->source, [
        'attributes' => ['title' => $data->alias],
      ]))->toString();
      $row['data']['source'] = Link::fromTextAndUrl(Unicode::truncate($data->source, 50, FALSE, TRUE), Url::fromUserInput($data->source, [
        'alias' => TRUE,
        'attributes' => ['title' => $data->source],
      ]))->toString();
      if ($multilanguage) {
        $row['data']['language_name'] = $this->languageManager()->getLanguageName($data->langcode);
      }

      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('path.admin_edit', ['pid' => $data->pid], ['query' => $destination]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('path.delete', ['pid' => $data->pid], ['query' => $destination]),
      ];
      $row['data']['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      // If the system path maps to a different URL alias, highlight this table
      // row to let the user know of old aliases.
      if ($data->alias != $this->aliasManager->getAliasByPath($data->source, $data->langcode)) {
        $row['class'] = ['warning'];
      }

      $rows[] = $row;
    }

    $build['path_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No URL aliases available. <a href=":link">Add URL alias</a>.', [':link' => Url::fromRoute('path.admin_add')->toString()]),
    ];
    $build['path_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
