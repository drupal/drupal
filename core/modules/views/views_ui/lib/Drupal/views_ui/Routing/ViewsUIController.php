<?php

/**
 * @file
 * Contains \Drupal\views_ui\Routing\ViewsUIController.
 */

namespace Drupal\views_ui\Routing;

use Drupal\views\ViewExecutable;
use Drupal\views\ViewStorageInterface;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewsDataCache;
use Drupal\user\TempStoreFactory;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Returns responses for Views UI routes.
 */
class ViewsUIController {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Stores the Views data cache object.
   *
   * @var \Drupal\views\ViewsDataCache
   */
  protected $viewsData;

  /**
   * Stores the user tempstore.
   *
   * @var \Drupal\user\TempStore
   */
  protected $tempStore;

  /**
   * Constructs a new \Drupal\views_ui\Routing\ViewsUIController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   * @param \Drupal\views\ViewsDataCache views_data
   *   The Views data cache object.
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManager $entity_manager, ViewsDataCache $views_data, TempStoreFactory $temp_store_factory) {
    $this->entityManager = $entity_manager;
    $this->viewsData = $views_data;
    $this->tempStore = $temp_store_factory->get('views');
  }

  /**
   * Lists all of the views.
   *
   * @return array
   *   The Views listing page.
   */
  public function listing() {
    return $this->entityManager->getListController('view')->render();
  }

  /**
   * Returns the form to add a new view.
   *
   * @return array
   *   The Views add form.
   */
  public function add() {
    drupal_set_title(t('Add new view'));

    $entity = $this->entityManager->getStorageController('view')->create(array());
    return entity_get_form($entity, 'add');
  }

  /**
   * Form builder for the admin display defaults page.
   *
   * @return array
   *   The Views basic settings form.
   */
  public function settingsBasic() {
    // @todo Remove the need for this.
    module_load_include('inc', 'views_ui', 'admin');
    return drupal_get_form('views_ui_admin_settings_basic');
  }

  /**
   * Form builder for the advanced admin settings page.
   *
   * @return array
   *   The Views advanced settings form.
   */
  public function settingsAdvanced() {
    // @todo Remove the need for this.
    module_load_include('inc', 'views_ui', 'admin');
    return drupal_get_form('views_ui_admin_settings_advanced');
  }

  /**
   * Lists all instances of fields on any views.
   *
   * @return array
   *   The Views fields report page.
   */
  public function reportFields() {
    $views = $this->entityManager->getStorageController('view')->load();

    // Fetch all fieldapi fields which are used in views
    // Therefore search in all views, displays and handler-types.
    $fields = array();
    $handler_types = ViewExecutable::viewsHandlerTypes();
    foreach ($views as $view) {
      $executable = $view->get('executable');
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display_id => $display) {
        if ($executable->setDisplay($display_id)) {
          foreach ($handler_types as $type => $info) {
            foreach ($executable->getItems($type, $display_id) as $item) {
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
      $rows[$field_name]['data'][0] = check_plain($field_name);
      foreach ($views as $view) {
        $rows[$field_name]['data'][1][] = l($view, "admin/structure/views/view/$view");
      }
      $rows[$field_name]['data'][1] = implode(', ', $rows[$field_name]['data'][1]);
    }

    // Sort rows by field name.
    ksort($rows);
    $output = array(
      '#theme' => 'table',
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
    $rows = views_plugin_list();
    foreach ($rows as &$row) {
      // Link each view name to the view itself.
      foreach ($row['views'] as $row_name => $view) {
        $row['views'][$row_name] = l($view, "admin/structure/views/view/$view");
      }
      $row['views'] = implode(', ', $row['views']);
    }

    // Sort rows by field name.
    ksort($rows);
    return array(
      '#theme' => 'table',
      '#header' => array(t('Type'), t('Name'), t('Provided by'), t('Used in')),
      '#rows' => $rows,
      '#empty' => t('There are no enabled views.'),
    );
  }

  /**
   * Calls a method on a view and reloads the listing page.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  public function ajaxOperation(ViewStorageInterface $view, $op, Request $request) {
    // Perform the operation.
    $view->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityManager->getListController('view')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#views-entity-list', drupal_render($list)));
      return $response;
    }

    // Otherwise, redirect back to the page.
    // @todo Remove url() wrapper once http://drupal.org/node/1668866 is in.
    return new RedirectResponse(url('admin/structure/views', array('absolute' => TRUE)));
  }

  /**
   * Returns the form to clone a view.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being cloned.
   *
   * @return array
   *   The Views clone form.
   */
  public function cloneForm(ViewStorageInterface $view) {
    drupal_set_title(t('Clone of @human_name', array('@human_name' => $view->getHumanName())));
    return entity_get_form($view, 'clone');
  }

  /**
   * Returns the form to delete a view.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being deleted.
   *
   * @return array
   *   The Views delete form.
   */
  public function deleteForm(ViewStorageInterface $view) {
    return drupal_get_form('views_ui_confirm_delete', $view);
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
    $views = $this->entityManager->getStorageController('view')->load();
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
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being deleted.
   * @param string|null $display_id
   *   (optional) The display ID being edited. Defaults to NULL, which will load
   *   the first available display.
   *
   * @return array
   *   An array containing the Views edit and preview forms.
   */
  public function edit(ViewStorageInterface $view, $display_id = NULL) {
    $view_ui = $this->getViewUI($view);

    $name = $view_ui->getHumanName();
    $data = $this->viewsData->get($view_ui->get('base_table'));
    if (isset($data['table']['base']['title'])) {
      $name .= ' (' . $data['table']['base']['title'] . ')';
    }
    drupal_set_title($name);

    $build['edit'] = entity_get_form($view_ui, 'edit', array('display_id' => $display_id));
    $build['preview'] = entity_get_form($view_ui, 'preview', array('display_id' => $display_id));
    return $build;
  }

  /**
   * Returns the form to preview a view.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being deleted.
   * @param string|null $display_id
   *   (optional) The display ID being edited. Defaults to NULL, which will
   *   load the first available display.
   *
   * @return array
   *   The Views preview form.
   */
  public function preview(ViewStorageInterface $view, $display_id = NULL) {
    $view_ui = $this->getViewUI($view);

    return entity_get_form($view_ui, 'preview', array('display_id' => $display_id));
  }

  /**
   * Returns the form to break the lock of an edited view.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The locked view.
   *
   * @return array
   *   The Views 'break lock' form.
   */
  public function breakLock(ViewStorageInterface $view) {
    // @todo Remove the need for this.
    module_load_include('inc', 'views_ui', 'admin');

    return drupal_get_form('views_ui_break_lock_confirm', $this->getViewUI($view));
  }

  /**
   * Provides a generic entry point to handle AJAX forms.
   *
   * @param string $js
   *   If this is an AJAX form, it will be the string 'ajax'. Otherwise, it will
   *   be 'nojs'. This determines the response.
   * @param string $key
   *   A string representing a section of the Views UI. Available keys are in
   *   views_ui_ajax_forms().
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being edited.
   * @param string|null $display_id
   *   The display ID being edited, or NULL to load the first available display.
   * @param string|null $type
   *   If $key is 'add-item' or 'config-item', this is the type of handler being
   *   edited. Otherwise, it is the subsection of the Views UI. For example, the
   *   'display' section has 'access' as a subsection, or the 'config-item' has
   *   'style' as a handler type. NULL if the section has no subsections.
   * @param string|null $id
   *   If $key is 'config-item', then this will be the plugin ID of the handler.
   *   Otherwise it will be NULL.
   *
   * @return array
   *   An form for a specific operation in the Views UI, or an array of AJAX
   *   commands to render a form.
   *
   * @todo When http://drupal.org/node/1843224 is in, this will return
   *   \Drupal\Core\Ajax\AjaxResponse instead of the array of AJAX commands.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function ajaxForm($js, $key, ViewStorageInterface $view, $display_id, $type, $id) {
    // Determine if this is an AJAX submission.
    $js = $js == 'ajax';

    // @todo Remove the need for this.
    module_load_include('inc', 'views_ui', 'admin');

    return views_ui_ajax_form($js, $key, $this->getViewUI($view), $display_id, $type, $id);
  }

  /**
   * Loads a view, first checking for a view being currently edited.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being acted upon.
   *
   * @return \Drupal\views_ui\ViewUI
   *   The view object, with a 'locked' property indicating whether or not
   *   someone else is already editing the view.
   */
  public function getViewUI(ViewStorageInterface $view) {
    $view_ui = new ViewUI($view);
    if ($new_view = $this->tempStore->get($view_ui->id())) {
      if ($view_ui->status()) {
        $new_view->enable();
      }
      else {
        $new_view->disable();
      }
    }
    else {
      $new_view = $view_ui;
    }
    $new_view->locked = $this->tempStore->getMetadata($new_view->id());
    return $new_view;
  }

}
