<?php

namespace Drupal\views_ui\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewsData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Component\Utility\Html;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns responses for Views UI routes.
 */
class ViewsUIController extends ControllerBase {

  /**
   * Stores the Views data cache object.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * Constructs a new \Drupal\views_ui\Controller\ViewsUIController object.
   *
   * @param \Drupal\views\ViewsData $views_data
   *   The Views data cache object.
   */
  public function __construct(ViewsData $views_data) {
    $this->viewsData = $views_data;
  }

  /**
   * Lists all instances of fields on any views.
   *
   * @return array
   *   The Views fields report page.
   */
  #[Route(
    path: '/admin/reports/fields/views-fields',
    name: 'views_ui.reports_fields',
    requirements: ['_permission' => 'administer views'],
    defaults: ['_title' => new TranslatableMarkup('Used in views')],
  )]
  public function reportFields() {
    $views = $this->entityTypeManager()->getStorage('view')->loadMultiple();

    // Fetch all fieldapi fields which are used in views
    // Therefore search in all views, displays and handler-types.
    $fields = [];
    $handler_types = ViewExecutable::getHandlerTypes();
    foreach ($views as $view) {
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display_id => $display) {
        if ($executable->setDisplay($display_id)) {
          foreach ($handler_types as $type => $info) {
            foreach ($executable->getHandlers($type, $display_id) as $item) {
              $table_data = $this->viewsData->get($item['table']);
              if (isset($table_data[$item['field']]) && isset($table_data[$item['field']][$type])
                && $field_data = $table_data[$item['field']][$type]) {
                // The final check that we have a fieldapi field now.
                if (isset($field_data['field_name'])) {
                  $fields[$field_data['field_name']][$view->id()] = $view->id();
                }
              }
            }
          }
        }
      }
    }

    $header = [$this->t('Field name'), $this->t('Used in')];
    $rows = [];
    foreach ($fields as $field_name => $views) {
      $rows[$field_name]['data'][0]['data']['#plain_text'] = $field_name;
      foreach ($views as $view) {
        $rows[$field_name]['data'][1][] = Link::fromTextAndUrl($view, new Url('entity.view.edit_form', ['view' => $view]))->toString();
      }
      $item_list = [
        '#theme' => 'item_list',
        '#items' => $rows[$field_name]['data'][1],
        '#context' => ['list_style' => 'comma-list'],
      ];
      $rows[$field_name]['data'][1] = ['data' => $item_list];
    }

    // Sort rows by field name.
    ksort($rows);
    $output = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No fields have been used in views yet.'),
    ];

    return $output;
  }

  /**
   * Lists all plugins and what enabled Views use them.
   *
   * @return array
   *   The Views plugins report page.
   */
  #[Route(
    path: '/admin/reports/views-plugins',
    name: 'views_ui.reports_plugins',
    requirements: ['_permission' => 'administer views'],
    defaults: ['_title' => new TranslatableMarkup('Views plugins')],
  )]
  public function reportPlugins() {
    $rows = Views::pluginList();
    foreach ($rows as &$row) {
      $views = [];
      // Link each view name to the view itself.
      foreach ($row['views'] as $view) {
        $views[] = Link::fromTextAndUrl($view, new Url('entity.view.edit_form', ['view' => $view]))->toString();
      }
      unset($row['views']);
      $row['views']['data'] = [
        '#theme' => 'item_list',
        '#items' => $views,
        '#context' => ['list_style' => 'comma-list'],
      ];
    }

    // Sort rows by field name.
    ksort($rows);
    return [
      '#type' => 'table',
      '#header' => [$this->t('Type'), $this->t('Name'), $this->t('Provided by'), $this->t('Used in')],
      '#rows' => $rows,
      '#empty' => $this->t('There are no enabled views.'),
    ];
  }

  /**
   * Calls a method on a view and reloads the listing page.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  #[Route(
    path: '/admin/structure/views/view/{view}/enable',
    name: 'entity.view.enable',
    defaults: ['op' => 'enable'],
    requirements: [
      '_entity_access' => 'view.enable',
      '_csrf_token' => 'TRUE',
    ],
  )]
  #[Route(
    path: '/admin/structure/views/view/{view}/disable',
    name: 'entity.view.disable',
    defaults: ['op' => 'disable'],
    requirements: [
      '_entity_access' => 'view.disable',
      '_csrf_token' => 'TRUE',
    ],
  )]
  public function ajaxOperation(ViewEntityInterface $view, $op, Request $request) {
    // Perform the operation.
    $view->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityTypeManager()->getListBuilder('view')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#views-entity-list', $list));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('entity.view.collection');
  }

  /**
   * Menu callback for Views tag autocompletion.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Views tags.
   */
  #[Route(
    path: '/admin/views/ajax/autocomplete/tag',
    name: 'views_ui.autocomplete',
    requirements: ['_permission' => 'administer views'],
  )]
  public function autocompleteTag(Request $request) {
    $matches = [];
    $string = $request->query->get('q');
    // Get matches from default views.
    $views = $this->entityTypeManager()->getStorage('view')->loadMultiple();
    // Keep track of previously processed tags so they can be skipped.
    $tags = [];
    foreach ($views as $view) {
      $view_tag = $view->get('tag');
      foreach (Tags::explode($view_tag) as $tag) {
        if ($tag && !in_array($tag, $tags, TRUE)) {
          $tags[] = $tag;
          if (mb_stripos($tag, $string) !== FALSE) {
            $matches[] = ['value' => $tag, 'label' => Html::escape($tag)];
            if (count($matches) >= 10) {
              break 2;
            }
          }
        }
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns the form to edit a view.
   *
   * @param \Drupal\views_ui\ViewUI $view
   *   The view to be edited.
   * @param string|null $display_id
   *   (optional) The display ID being edited. Defaults to NULL, which will load
   *   the first available display.
   *
   * @return array
   *   An array containing the Views edit and preview forms.
   */
  #[Route(
    path: '/admin/structure/views/view/{view}',
    name: 'entity.view.edit_form',
    options: [
      'parameters' => [
        'view' => [
          'tempstore' => TRUE,
          'type' => 'entity:view',
        ],
      ],
    ],
    requirements: ['_entity_access' => 'view.update'],
  )]
  #[Route(
    path: '/admin/structure/views/view/{view}/edit/{display_id}',
    name: 'entity.view.edit_display_form',
    defaults: ['display_id' => NULL],
    options: [
      'parameters' => [
        'view' => [
          'tempstore' => TRUE,
          'type' => 'entity:view',
        ],
      ],
    ],
    requirements: ['_entity_access' => 'view.update'],
  )]
  public function edit(ViewUI $view, $display_id = NULL) {
    $name = $view->label();
    $data = $this->viewsData->get($view->get('base_table'));

    if (isset($data['table']['base']['title'])) {
      $name .= ' (' . $data['table']['base']['title'] . ')';
    }
    $build['#title'] = $name;

    $build['edit'] = $this->entityFormBuilder()->getForm($view, 'edit', ['display_id' => $display_id]);
    $build['preview'] = $this->entityFormBuilder()->getForm($view, 'preview', ['display_id' => $display_id]);
    return $build;
  }

}
