<?php

/**
 * @file
 * Contains \Drupal\views_ui\Controller\ViewsUIController.
 */

namespace Drupal\views_ui\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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
   * @param \Drupal\views\ViewsData views_data
   *   The Views data cache object.
   */
  public function __construct(ViewsData $views_data) {
    $this->viewsData = $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('views.views_data')
    );
  }

  /**
   * Lists all instances of fields on any views.
   *
   * @return array
   *   The Views fields report page.
   */
  public function reportFields() {
    $views = $this->entityManager()->getStorage('view')->loadMultiple();

    // Fetch all fieldapi fields which are used in views
    // Therefore search in all views, displays and handler-types.
    $fields = array();
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

    $header = array(t('Field name'), t('Used in'));
    $rows = array();
    foreach ($fields as $field_name => $views) {
      $rows[$field_name]['data'][0] = String::checkPlain($field_name);
      foreach ($views as $view) {
        $rows[$field_name]['data'][1][] = $this->l($view, new Url('entity.view.edit_form', array('view' => $view)));
      }
      $rows[$field_name]['data'][1] = SafeMarkup::set(implode(', ', $rows[$field_name]['data'][1]));
    }

    // Sort rows by field name.
    ksort($rows);
    $output = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No fields have been used in views yet.'),
    );

    return $output;
  }

  /**
   * Lists all plugins and what enabled Views use them.
   *
   * @return array
   *   The Views plugins report page.
   */
  public function reportPlugins() {
    $rows = Views::pluginList();
    foreach ($rows as &$row) {
      // Link each view name to the view itself.
      foreach ($row['views'] as $row_name => $view) {
        $row['views'][$row_name] = $this->l($view, new Url('entity.view.edit_form', array('view' => $view)));
      }
      $row['views'] = SafeMarkup::set(implode(', ', $row['views']));
    }

    // Sort rows by field name.
    ksort($rows);
    return array(
      '#type' => 'table',
      '#header' => array(t('Type'), t('Name'), t('Provided by'), t('Used in')),
      '#rows' => $rows,
      '#empty' => t('There are no enabled views.'),
    );
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
   *
   */
  public function ajaxOperation(ViewEntityInterface $view, $op, Request $request) {
    // Perform the operation.
    $view->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityManager()->getListBuilder('view')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#views-entity-list', drupal_render($list)));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('views_ui.list');
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
  public function autocompleteTag(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    // Get matches from default views.
    $views = $this->entityManager()->getStorage('view')->loadMultiple();
    foreach ($views as $view) {
      $tag = $view->get('tag');
      if ($tag && strpos($tag, $string) === 0) {
        $matches[$tag] = $tag;
        if (count($matches) >= 10) {
          break;
        }
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns the form to edit a view.
   *
   * @param \Drupal\views_ui\ViewUI $view
   *   The view being deleted.
   * @param string|null $display_id
   *   (optional) The display ID being edited. Defaults to NULL, which will load
   *   the first available display.
   *
   * @return array
   *   An array containing the Views edit and preview forms.
   */
  public function edit(ViewUI $view, $display_id = NULL) {
    $name = $view->label();
    $data = $this->viewsData->get($view->get('base_table'));

    if (isset($data['table']['base']['title'])) {
      $name .= ' (' . $data['table']['base']['title'] . ')';
    }
    $build['#title'] = $name;

    $build['edit'] = $this->entityFormBuilder()->getForm($view, 'edit', array('display_id' => $display_id));
    $build['preview'] = $this->entityFormBuilder()->getForm($view, 'preview', array('display_id' => $display_id));
    return $build;
  }

}
