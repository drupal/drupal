<?php

/**
 * @file
 * Contains Drupal\views_ui\ViewEditForm.
 */

namespace Drupal\views_ui;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\user\TempStoreFactory;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Views edit form.
 */
class ViewEditForm extends ViewFormBase {

  /**
   * The views temp store.
   *
   * @var \Drupal\user\TempStore
   */
  protected $tempStore;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new ViewEditForm object.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date Formatter service.
   */
  public function __construct(TempStoreFactory $temp_store_factory, RequestStack $requestStack, DateFormatter $date_formatter) {
    $this->tempStore = $temp_store_factory->get('views');
    $this->requestStack = $requestStack;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.tempstore'),
      $container->get('request_stack'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $view = $this->entity;
    $display_id = $this->displayID;
    // Do not allow the form to be cached, because $form_state->get('view') can become
    // stale between page requests.
    // See views_ui_ajax_get_form() for how this affects #ajax.
    // @todo To remove this and allow the form to be cacheable:
    //   - Change $form_state->get('view') to $form_state->getTemporary()['view'].
    //   - Add a #process function to initialize $form_state->getTemporary()['view']
    //     on cached form submissions.
    //   - Use \Drupal\Core\Form\FormStateInterface::loadInclude().
    $form_state->disableCache();

    if ($display_id) {
      if (!$view->getExecutable()->setDisplay($display_id)) {
        $form['#markup'] = $this->t('Invalid display id @display', array('@display' => $display_id));
        return $form;
      }
    }

    $form['#tree'] = TRUE;

    $form['#attached']['library'][] = 'core/jquery.ui.tabs';
    $form['#attached']['library'][] = 'core/jquery.ui.dialog';
    $form['#attached']['library'][] = 'core/drupal.states';
    $form['#attached']['library'][] = 'core/drupal.tabledrag';
    $form['#attached']['library'][] = 'views/views.exposed-form';
    $form['#attached']['library'][] = 'views_ui/views_ui.admin';
    $form['#attached']['library'][] = 'views_ui/admin.styling';

    $form['#attached']['drupalSettings']['views']['ajax'] = [
      'id' => '#views-ajax-body',
      'title' => '#views-ajax-title',
      'popup' => '#views-ajax-popup',
      'defaultForm' => $view->getDefaultAJAXMessage(),
    ];

    $form += array(
      '#prefix' => '',
      '#suffix' => '',
    );

    $view_status = $view->status() ? 'enabled' : 'disabled';
    $form['#prefix'] .= '<div class="views-edit-view views-admin ' . $view_status . ' clearfix">';
    $form['#suffix'] = '</div>' . $form['#suffix'];

    $form['#attributes']['class'] = array('form-edit');

    if ($view->isLocked()) {
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($view->lock->owner),
      );
      $lock_message_substitutions = array(
        '!user' => drupal_render($username),
        '!age' => $this->dateFormatter->formatInterval(REQUEST_TIME - $view->lock->updated),
        '@url' => $view->url('break-lock-form'),
      );
      $form['locked'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('view-locked', 'messages', 'messages--warning')),
        '#children' => $this->t('This view is being edited by user !user, and is therefore locked from editing by others. This lock is !age old. Click here to <a href="@url">break this lock</a>.', $lock_message_substitutions),
        '#weight' => -10,
      );
    }
    else {
      $form['changed'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('view-changed', 'messages', 'messages--warning')),
        '#children' => $this->t('You have unsaved changes.'),
        '#weight' => -10,
      );
      if (empty($view->changed)) {
        $form['changed']['#attributes']['class'][] = 'js-hide';
      }
    }

    $form['displays'] = array(
      '#prefix' => '<h1 class="unit-title clearfix">' . $this->t('Displays') . '</h1>',
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'views-displays',
        ),
      ),
    );


    $form['displays']['top'] = $this->renderDisplayTop($view);

    // The rest requires a display to be selected.
    if ($display_id) {
      $form_state->set('display_id', $display_id);

      // The part of the page where editing will take place.
      $form['displays']['settings'] = array(
        '#type' => 'container',
        '#id' => 'edit-display-settings',
      );

      // Add a text that the display is disabled.
      if ($view->getExecutable()->displayHandlers->has($display_id)) {
        if (!$view->getExecutable()->displayHandlers->get($display_id)->isEnabled()) {
          $form['displays']['settings']['disabled']['#markup'] = $this->t('This display is disabled.');
        }
      }

      // Add the edit display content
      $tab_content = $this->getDisplayTab($view);
      $tab_content['#theme_wrappers'] = array('container');
      $tab_content['#attributes'] = array('class' => array('views-display-tab'));
      $tab_content['#id'] = 'views-tab-' . $display_id;
      // Mark deleted displays as such.
      $display = $view->get('display');
      if (!empty($display[$display_id]['deleted'])) {
        $tab_content['#attributes']['class'][] = 'views-display-deleted';
      }
      // Mark disabled displays as such.

      if ($view->getExecutable()->displayHandlers->has($display_id) && !$view->getExecutable()->displayHandlers->get($display_id)->isEnabled()) {
        $tab_content['#attributes']['class'][] = 'views-display-disabled';
      }
      $form['displays']['settings']['settings_content'] = array(
        '#type' => 'container',
        'tab_content' => $tab_content,
      );

      // The content of the popup dialog.
      $form['ajax-area'] = array(
        '#type' => 'container',
        '#id' => 'views-ajax-popup',
      );
      $form['ajax-area']['ajax-title'] = array(
        '#markup' => '<div id="views-ajax-title"></div>',
      );
      $form['ajax-area']['ajax-body'] = array(
        '#type' => 'container',
        '#id' => 'views-ajax-body',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);

    $actions['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => array('::cancel'),
    );
    if ($this->entity->isLocked()) {
      $actions['submit']['#access'] = FALSE;
      $actions['cancel']['#access'] = FALSE;
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    $view = $this->entity;
    if ($view->isLocked()) {
      $form_state->setErrorByName('', $this->t('Changes cannot be made to a locked view.'));
    }
    foreach ($view->getExecutable()->validate() as $display_errors) {
      foreach ($display_errors as $error) {
        $form_state->setErrorByName('', $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $view = $this->entity;
    $executable = $view->getExecutable();

    // Go through and remove displayed scheduled for removal.
    $displays = $view->get('display');
    foreach ($displays as $id => $display) {
      if (!empty($display['deleted'])) {
        $executable->displayHandlers->remove($id);
        unset($displays[$id]);
      }
    }

    // Rename display ids if needed.
    foreach ($executable->displayHandlers as $id => $display) {
      if (!empty($display->display['new_id'])) {
        $new_id = $display->display['new_id'];
        $display->display['id'] = $new_id;
        unset($display->display['new_id']);
        $executable->displayHandlers->set($new_id, $display);

        $displays[$new_id] = $displays[$id];
        unset($displays[$id]);

        // Redirect the user to the renamed display to be sure that the page itself exists and doesn't throw errors.
        $form_state->setRedirect('entity.view.edit_display_form', array(
          'view' => $view->id(),
          'display_id' => $new_id,
        ));
      }
    }
    $view->set('display', $displays);

    // @todo: Revisit this when http://drupal.org/node/1668866 is in.
    $query = $this->requestStack->getCurrentRequest()->query;
    $destination = $query->get('destination');

    if (!empty($destination)) {
      // Find out the first display which has a changed path and redirect to this url.
      $old_view = Views::getView($view->id());
      $old_view->initDisplay();
      foreach ($old_view->displayHandlers as $id => $display) {
        // Only check for displays with a path.
        $old_path = $display->getOption('path');
        if (empty($old_path)) {
          continue;
        }

        if (($display->getPluginId() == 'page') && ($old_path == $destination) && ($old_path != $view->getExecutable()->displayHandlers->get($id)->getOption('path'))) {
          $destination = $view->getExecutable()->displayHandlers->get($id)->getOption('path');
          $query->remove('destination');
        }
      }
      // @todo Use Url::fromPath() once https://www.drupal.org/node/2351379 is
      //   resolved.
      $form_state->setRedirectUrl(Url::fromUri("base://$destination"));
    }

    $view->save();

    drupal_set_message($this->t('The view %name has been saved.', array('%name' => $view->label())));

    // Remove this view from cache so we can edit it properly.
    $this->tempStore->delete($view->id());
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    // Remove this view from cache so edits will be lost.
    $view = $this->entity;
    $this->tempStore->delete($view->id());
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * Returns a renderable array representing the edit page for one display.
   */
  public function getDisplayTab($view) {
    $build = array();
    $display_id = $this->displayID;
    $display = $view->getExecutable()->displayHandlers->get($display_id);
    // If the plugin doesn't exist, display an error message instead of an edit
    // page.
    if (empty($display)) {
      // @TODO: Improved UX for the case where a plugin is missing.
      $build['#markup'] = $this->t("Error: Display @display refers to a plugin named '@plugin', but that plugin is not available.", array('@display' => $display->display['id'], '@plugin' => $display->display['display_plugin']));
    }
    // Build the content of the edit page.
    else {
      $build['details'] = $this->getDisplayDetails($view, $display->display);
    }
    // In AJAX context, ViewUI::rebuildCurrentTab() returns this outside of form
    // context, so hook_form_views_ui_edit_form_alter() is insufficient.
    \Drupal::moduleHandler()->alter('views_ui_display_tab', $build, $view, $display_id);
    return $build;
  }

  /**
   * Helper function to get the display details section of the edit UI.
   *
   * @param $display
   *
   * @return array
   *   A renderable page build array.
   */
  public function getDisplayDetails($view, $display) {
    $display_title = $this->getDisplayLabel($view, $display['id'], FALSE);
    $build = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('id' => 'edit-display-settings-details'),
    );

    $is_display_deleted = !empty($display['deleted']);
    // The master display cannot be duplicated.
    $is_default = $display['id'] == 'default';
    // @todo: Figure out why getOption doesn't work here.
    $is_enabled = $view->getExecutable()->displayHandlers->get($display['id'])->isEnabled();

    if ($display['id'] != 'default') {
      $build['top']['#theme_wrappers'] = array('container');
      $build['top']['#attributes']['id'] = 'edit-display-settings-top';
      $build['top']['#attributes']['class'] = array('views-ui-display-tab-actions', 'views-ui-display-tab-bucket', 'clearfix');

      // The Delete, Duplicate and Undo Delete buttons.
      $build['top']['actions'] = array(
        '#theme_wrappers' => array('dropbutton_wrapper'),
      );

      // Because some of the 'links' are actually submit buttons, we have to
      // manually wrap each item in <li> and the whole list in <ul>.
      $build['top']['actions']['prefix']['#markup'] = '<ul class="dropbutton">';

      if (!$is_display_deleted) {
        if (!$is_enabled) {
          $build['top']['actions']['enable'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Enable !display_title', array('!display_title' => $display_title)),
            '#limit_validation_errors' => array(),
            '#submit' => array('::submitDisplayEnable', '::submitDelayDestination'),
            '#prefix' => '<li class="enable">',
            "#suffix" => '</li>',
          );
        }
        // Add a link to view the page unless the view is disabled or has no
        // path.
        elseif ($view->status() && $view->getExecutable()->displayHandlers->get($display['id'])->hasPath()) {
          $path = $view->getExecutable()->displayHandlers->get($display['id'])->getPath();
          if ($path && (strpos($path, '%') === FALSE)) {
            $build['top']['actions']['path'] = array(
              '#type' => 'link',
              '#title' => $this->t('View !display_title', array('!display_title' => $display_title)),
              '#options' => array('alt' => array($this->t("Go to the real page for this display"))),
              // @todo Use Url::fromPath() once
              //   https://www.drupal.org/node/2351379 is resolved.
              '#url' => Url::fromUri("base://$path"),
              '#prefix' => '<li class="view">',
              "#suffix" => '</li>',
            );
          }
        }
        if (!$is_default) {
          $build['top']['actions']['duplicate'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Duplicate !display_title', array('!display_title' => $display_title)),
            '#limit_validation_errors' => array(),
            '#submit' => array('::submitDisplayDuplicate', '::submitDelayDestination'),
            '#prefix' => '<li class="duplicate">',
            "#suffix" => '</li>',
          );
        }
        // Always allow a display to be deleted.
        $build['top']['actions']['delete'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Delete !display_title', array('!display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('::submitDisplayDelete', '::submitDelayDestination'),
          '#prefix' => '<li class="delete">',
          "#suffix" => '</li>',
        );

        foreach (Views::fetchPluginNames('display', NULL, array($view->get('storage')->get('base_table'))) as $type => $label) {
          if ($type == $display['display_plugin']) {
            continue;
          }

          $build['top']['actions']['duplicate_as'][$type] = array(
            '#type' => 'submit',
            '#value' => $this->t('Duplicate as !type', array('!type' => $label)),
            '#limit_validation_errors' => array(),
            '#submit' => array('::submitDuplicateDisplayAsType', '::submitDelayDestination'),
            '#prefix' => '<li class="duplicate">',
            '#suffix' => '</li>',
          );
        }
      }
      else {
        $build['top']['actions']['undo_delete'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Undo delete of !display_title', array('!display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('::submitDisplayUndoDelete', '::submitDelayDestination'),
          '#prefix' => '<li class="undo-delete">',
          "#suffix" => '</li>',
        );
      }
      if ($is_enabled) {
        $build['top']['actions']['disable'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Disable !display_title', array('!display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('::submitDisplayDisable', '::submitDelayDestination'),
          '#prefix' => '<li class="disable">',
          "#suffix" => '</li>',
        );
      }
      $build['top']['actions']['suffix']['#markup'] = '</ul>';

      // The area above the three columns.
      $build['top']['display_title'] = array(
        '#theme' => 'views_ui_display_tab_setting',
        '#description' => $this->t('Display name'),
        '#link' => $view->getExecutable()->displayHandlers->get($display['id'])->optionLink(String::checkPlain($display_title), 'display_title'),
      );
    }

    $build['columns'] = array();
    $build['columns']['#theme_wrappers'] = array('container');
    $build['columns']['#attributes'] = array('id' => 'edit-display-settings-main', 'class' => array('clearfix', 'views-display-columns'));

    $build['columns']['first']['#theme_wrappers'] = array('container');
    $build['columns']['first']['#attributes'] = array('class' => array('views-display-column', 'first'));

    $build['columns']['second']['#theme_wrappers'] = array('container');
    $build['columns']['second']['#attributes'] = array('class' => array('views-display-column', 'second'));

    $build['columns']['second']['settings'] = array();
    $build['columns']['second']['header'] = array();
    $build['columns']['second']['footer'] = array();
    $build['columns']['second']['empty'] = array();
    $build['columns']['second']['pager'] = array();

    // The third column buckets are wrapped in details.
    $build['columns']['third'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#theme_wrappers' => array('details'),
      '#attributes' => array(
        'class' => array(
          'views-display-column',
          'third',
        ),
      ),
    );
    // Collapse the details by default.
    $build['columns']['third']['#open'] = \Drupal::config('views.settings')->get('ui.show.advanced_column');

    // Each option (e.g. title, access, display as grid/table/list) fits into one
    // of several "buckets," or boxes (Format, Fields, Sort, and so on).
    $buckets = array();

    // Fetch options from the display plugin, with a list of buckets they go into.
    $options = array();
    $view->getExecutable()->displayHandlers->get($display['id'])->optionsSummary($buckets, $options);

    // Place each option into its bucket.
    foreach ($options as $id => $option) {
      // Each option self-identifies as belonging in a particular bucket.
      $buckets[$option['category']]['build'][$id] = $this->buildOptionForm($view, $id, $option, $display);
    }

    // Place each bucket into the proper column.
    foreach ($buckets as $id => $bucket) {
      // Let buckets identify themselves as belonging in a column.
      if (isset($bucket['column']) && isset($build['columns'][$bucket['column']])) {
        $column = $bucket['column'];
      }
      // If a bucket doesn't pick one of our predefined columns to belong to, put
      // it in the last one.
      else {
        $column = 'third';
      }
      if (isset($bucket['build']) && is_array($bucket['build'])) {
        $build['columns'][$column][$id] = $bucket['build'];
        $build['columns'][$column][$id]['#theme_wrappers'][] = 'views_ui_display_tab_bucket';
        $build['columns'][$column][$id]['#title'] = !empty($bucket['title']) ? $bucket['title'] : '';
        $build['columns'][$column][$id]['#name'] = !empty($bucket['title']) ? $bucket['title'] : $id;
      }
    }

    $build['columns']['first']['fields'] = $this->getFormBucket($view, 'field', $display);
    $build['columns']['first']['filters'] = $this->getFormBucket($view, 'filter', $display);
    $build['columns']['first']['sorts'] = $this->getFormBucket($view, 'sort', $display);
    $build['columns']['second']['header'] = $this->getFormBucket($view, 'header', $display);
    $build['columns']['second']['footer'] = $this->getFormBucket($view, 'footer', $display);
    $build['columns']['second']['empty'] = $this->getFormBucket($view, 'empty', $display);
    $build['columns']['third']['arguments'] = $this->getFormBucket($view, 'argument', $display);
    $build['columns']['third']['relationships'] = $this->getFormBucket($view, 'relationship', $display);

    return $build;
  }

  /**
   * Submit handler to add a restore a removed display to a view.
   */
  public function submitDisplayUndoDelete($form, FormStateInterface $form_state) {
    $view = $this->entity;
    // Create the new display
    $id = $form_state->get('display_id');
    $displays = $view->get('display');
    $displays[$id]['deleted'] = FALSE;
    $view->set('display', $displays);

    // Store in cache
    $view->cacheSet();

    // Redirect to the top-level edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $id,
    ));
  }

  /**
   * Submit handler to enable a disabled display.
   */
  public function submitDisplayEnable($form, FormStateInterface $form_state) {
    $view = $this->entity;
    $id = $form_state->get('display_id');
    // setOption doesn't work because this would might affect upper displays
    $view->getExecutable()->displayHandlers->get($id)->setOption('enabled', TRUE);

    // Store in cache
    $view->cacheSet();

    // Redirect to the top-level edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $id,
    ));
  }

  /**
   * Submit handler to disable display.
   */
  public function submitDisplayDisable($form, FormStateInterface $form_state) {
    $view = $this->entity;
    $id = $form_state->get('display_id');
    $view->getExecutable()->displayHandlers->get($id)->setOption('enabled', FALSE);

    // Store in cache
    $view->cacheSet();

    // Redirect to the top-level edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $id,
    ));
  }

  /**
   * Submit handler to delete a display from a view.
   */
  public function submitDisplayDelete($form, FormStateInterface $form_state) {
    $view = $this->entity;
    $display_id = $form_state->get('display_id');

    // Mark the display for deletion.
    $displays = $view->get('display');
    $displays[$display_id]['deleted'] = TRUE;
    $view->set('display', $displays);
    $view->cacheSet();

    // Redirect to the top-level edit page. The first remaining display will
    // become the active display.
    $form_state->setRedirectUrl($view->urlInfo('edit-form'));
  }

  /**
   * Regenerate the current tab for AJAX updates.
   *
   * @param \Drupal\views_ui\ViewUI $view
   *   The view to regenerate its tab.
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object to add new commands to.
   * @param string $display_id
   *   The display ID of the tab to regenerate.
   */
  public function rebuildCurrentTab(ViewUI $view, AjaxResponse $response, $display_id) {
    $this->displayID = $display_id;
    if (!$view->getExecutable()->setDisplay('default')) {
      return;
    }

    // Regenerate the main display area.
    $build = $this->getDisplayTab($view);
    static::addMicroweights($build);
    $response->addCommand(new HtmlCommand('#views-tab-' . $display_id, drupal_render($build)));

    // Regenerate the top area so changes to display names and order will appear.
    $build = $this->renderDisplayTop($view);
    static::addMicroweights($build);
    $response->addCommand(new ReplaceCommand('#views-display-top', drupal_render($build)));
  }

  /**
   * Render the top of the display so it can be updated during ajax operations.
   */
  public function renderDisplayTop(ViewUI $view) {
    $display_id = $this->displayID;
    $element['#theme_wrappers'][] = 'views_ui_container';
    $element['#attributes']['class'] = array('views-display-top', 'clearfix');
    $element['#attributes']['id'] = array('views-display-top');

    // Extra actions for the display
    $element['extra_actions'] = array(
      '#type' => 'dropbutton',
      '#attributes' => array(
        'id' => 'views-display-extra-actions',
      ),
      '#links' => array(
        'edit-details' => array(
          'title' => $this->t('Edit view name/description'),
          'url' => Url::fromRoute('views_ui.form_edit_details', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display_id]),
          'attributes' => array('class' => array('views-ajax-link')),
        ),
        'analyze' => array(
          'title' => $this->t('Analyze view'),
          'url' => Url::fromRoute('views_ui.form_analyze', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display_id]),
          'attributes' => array('class' => array('views-ajax-link')),
        ),
        'duplicate' => array(
          'title' => $this->t('Duplicate view'),
          'url' => $view->urlInfo('duplicate-form'),
        ),
        'reorder' => array(
          'title' => $this->t('Reorder displays'),
          'url' => Url::fromRoute('views_ui.form_reorder_displays', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display_id]),
          'attributes' => array('class' => array('views-ajax-link')),
        ),
      ),
    );

    if ($view->access('delete')) {
      $element['extra_actions']['#links']['delete'] = array(
        'title' => $this->t('Delete view'),
        'url' => $view->urlInfo('delete-form'),
      );
    }

    // Let other modules add additional links here.
    \Drupal::moduleHandler()->alter('views_ui_display_top_links', $element['extra_actions']['#links'], $view, $display_id);

    if (isset($view->type) && $view->type != $this->t('Default')) {
      if ($view->type == $this->t('Overridden')) {
        $element['extra_actions']['#links']['revert'] = array(
          'title' => $this->t('Revert view'),
          'href' => "admin/structure/views/view/{$view->id()}/revert",
          'query' => array('destination' => "admin/structure/views/view/{$view->id()}"),
        );
      }
      else {
        $element['extra_actions']['#links']['delete'] = array(
          'title' => $this->t('Delete view'),
          'url' => $view->urlInfo('delete-form'),
        );
      }
    }

    // Determine the displays available for editing.
    if ($tabs = $this->getDisplayTabs($view)) {
      if ($display_id) {
        $tabs[$display_id]['#active'] = TRUE;
      }
      $tabs['#prefix'] = '<h2 class="visually-hidden">' . $this->t('Secondary tabs') . '</h2><ul id = "views-display-menu-tabs" class="tabs secondary">';
      $tabs['#suffix'] = '</ul>';
      $element['tabs'] = $tabs;
    }

    // Buttons for adding a new display.
    foreach (Views::fetchPluginNames('display', NULL, array($view->get('base_table'))) as $type => $label) {
      $element['add_display'][$type] = array(
        '#type' => 'submit',
        '#value' => $this->t('Add !display', array('!display' => $label)),
        '#limit_validation_errors' => array(),
        '#submit' => array('::submitDisplayAdd', '::submitDelayDestination'),
        '#attributes' => array('class' => array('add-display')),
        // Allow JavaScript to remove the 'Add ' prefix from the button label when
        // placing the button in a "Add" dropdown menu.
        '#process' => array_merge(array('views_ui_form_button_was_clicked'), element_info_property('submit', '#process', array())),
        '#values' => array($this->t('Add !display', array('!display' => $label)), $label),
      );
    }

    return $element;
  }

  /**
   * Submit handler for form buttons that do not complete a form workflow.
   *
   * The Edit View form is a multistep form workflow, but with state managed by
   * the TempStore rather than $form_state->setRebuild(). Without this
   * submit handler, buttons that add or remove displays would redirect to the
   * destination parameter (e.g., when the Edit View form is linked to from a
   * contextual link). This handler can be added to buttons whose form submission
   * should not yet redirect to the destination.
   */
  public function submitDelayDestination($form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();
    $destination = $request->query->get('destination');

    $redirect = $form_state->getRedirect();
    // If there is a destination, and redirects are not explicitly disabled, add
    // the destination as a query string to the redirect and suppress it for the
    // current request.
    if (isset($destination) && $redirect !== FALSE) {
      // Create a valid redirect if one does not exist already.
      if (!($redirect instanceof Url)) {
        $redirect = Url::createFromRequest($request);
      }

      // Add the current destination to the redirect unless one exists already.
      $options = $redirect->getOptions();
      if (!isset($options['query']['destination'])) {
        $options['query']['destination'] = $destination;
        $redirect->setOptions($options);
      }

      $form_state->setRedirectUrl($redirect);
      $request->query->remove('destination');
    }
  }

  /**
   * Submit handler to duplicate a display for a view.
   */
  public function submitDisplayDuplicate($form, FormStateInterface $form_state) {
    $view = $this->entity;
    $display_id = $this->displayID;

    // Create the new display.
    $displays = $view->get('display');
    $display = $view->getExecutable()->newDisplay($displays[$display_id]['display_plugin']);
    $new_display_id = $display->display['id'];
    $displays[$new_display_id] = $displays[$display_id];
    $displays[$new_display_id]['id'] = $new_display_id;
    $view->set('display', $displays);

    // By setting the current display the changed marker will appear on the new
    // display.
    $view->getExecutable()->current_display = $new_display_id;
    $view->cacheSet();

    // Redirect to the new display's edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $new_display_id,
    ));
  }

  /**
   * Submit handler to add a display to a view.
   */
  public function submitDisplayAdd($form, FormStateInterface $form_state) {
    $view = $this->entity;
    // Create the new display.
    $parents = $form_state->getTriggeringElement()['#parents'];
    $display_type = array_pop($parents);
    $display = $view->getExecutable()->newDisplay($display_type);
    $display_id = $display->display['id'];
    // A new display got added so the asterisks symbol should appear on the new
    // display.
    $view->getExecutable()->current_display = $display_id;
    $view->cacheSet();

    // Redirect to the new display's edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $display_id,
    ));
  }

  /**
   * Submit handler to Duplicate a display as another display type.
   */
  public function submitDuplicateDisplayAsType($form, FormStateInterface $form_state) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->entity;
    $display_id = $this->displayID;

    // Create the new display.
    $parents = $form_state->getTriggeringElement()['#parents'];
    $display_type = array_pop($parents);

    $new_display_id = $view->duplicateDisplayAsType($display_id, $display_type);

    // By setting the current display the changed marker will appear on the new
    // display.
    $view->getExecutable()->current_display = $new_display_id;
    $view->cacheSet();

    // Redirect to the new display's edit page.
    $form_state->setRedirect('entity.view.edit_display_form', array(
      'view' => $view->id(),
      'display_id' => $new_display_id,
    ));
  }

  /**
   * Build a renderable array representing one option on the edit form.
   *
   * This function might be more logical as a method on an object, if a suitable
   * object emerges out of refactoring.
   */
  public function buildOptionForm(ViewUI $view, $id, $option, $display) {
    $option_build = array();
    $option_build['#theme'] = 'views_ui_display_tab_setting';

    $option_build['#description'] = $option['title'];

    $option_build['#link'] = $view->getExecutable()->displayHandlers->get($display['id'])->optionLink($option['value'], $id, '', empty($option['desc']) ? '' : $option['desc']);

    $option_build['#links'] = array();
    if (!empty($option['links']) && is_array($option['links'])) {
      foreach ($option['links'] as $link_id => $link_value) {
        $option_build['#settings_links'][] = $view->getExecutable()->displayHandlers->get($display['id'])->optionLink($option['setting'], $link_id, 'views-button-configure', $link_value);
      }
    }

    if (!empty($view->getExecutable()->displayHandlers->get($display['id'])->options['defaults'][$id])) {
      $display_id = 'default';
      $option_build['#defaulted'] = TRUE;
    }
    else {
      $display_id = $display['id'];
      if (!$view->getExecutable()->displayHandlers->get($display['id'])->isDefaultDisplay()) {
        if ($view->getExecutable()->displayHandlers->get($display['id'])->defaultableSections($id)) {
          $option_build['#overridden'] = TRUE;
        }
      }
    }
    $option_build['#attributes']['class'][] = Html::cleanCssIdentifier($display_id . '-' . $id);
    return $option_build;
  }

  /**
   * Add information about a section to a display.
   */
  public function getFormBucket(ViewUI $view, $type, $display) {
    $executable = $view->getExecutable();
    $executable->setDisplay($display['id']);
    $executable->initStyle();

    $types = $executable->getHandlerTypes();

    $build = array(
      '#theme_wrappers' => array('views_ui_display_tab_bucket'),
    );

    $build['#overridden'] = FALSE;
    $build['#defaulted'] = FALSE;

    $build['#name'] = $build['#title'] = $types[$type]['title'];

    $rearrange_url = Url::fromRoute('views_ui.form_rearrange', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display['id'], 'type' => $type]);
    $class = 'icon compact rearrange';

    // Different types now have different rearrange forms, so we use this switch
    // to get the right one.
    switch ($type) {
      case 'filter':
        // The rearrange form for filters contains the and/or UI, so override
        // the used path.
        $rearrange_url = Url::fromRoute('views_ui.form_rearrange_filter', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display['id']]);
        // TODO: Add another class to have another symbol for filter rearrange.
        $class = 'icon compact rearrange';
        break;
      case 'field':
        // Fetch the style plugin info so we know whether to list fields or not.
        $style_plugin = $executable->style_plugin;
        $uses_fields = $style_plugin && $style_plugin->usesFields();
        if (!$uses_fields) {
          $build['fields'][] = array(
            '#markup' => $this->t('The selected style or row format does not use fields.'),
            '#theme_wrappers' => array('views_ui_container'),
            '#attributes' => array('class' => array('views-display-setting')),
          );
          return $build;
        }
        break;
      case 'header':
      case 'footer':
      case 'empty':
        if (!$executable->display_handler->usesAreas()) {
          $build[$type][] = array(
            '#markup' => $this->t('The selected display type does not use @type plugins', array('@type' => $type)),
            '#theme_wrappers' => array('views_ui_container'),
            '#attributes' => array('class' => array('views-display-setting')),
          );
          return $build;
        }
        break;
    }

    // Create an array of actions to pass to links template.
    $actions = array();
    $count_handlers = count($executable->display_handler->getHandlers($type));

    // Create the add text variable for the add action.
    $add_text = $this->t('Add <span class="visually-hidden">@type</span>', array('@type' => $types[$type]['ltitle']));

    $actions['add'] = array(
      'title' => $add_text,
      'url' => Url::fromRoute('views_ui.form_add_handler', ['js' => 'nojs', 'view' => $view->id(), 'display_id' => $display['id'], 'type' => $type]),
      'attributes' => array('class' => array('icon compact add', 'views-ajax-link'), 'id' => 'views-add-' . $type),
      'html' => TRUE,
    );
    if ($count_handlers > 0) {
      // Create the rearrange text variable for the rearrange action.
      $rearrange_text = $type == 'filter' ? $this->t('And/Or Rearrange <span class="visually-hidden">filter criteria</span>') : $this->t('Rearrange <span class="visually-hidden">@type</span>', array('@type' => $types[$type]['ltitle']));

      $actions['rearrange'] = array(
        'title' => $rearrange_text,
        'url' => $rearrange_url,
        'attributes' => array('class' => array($class, 'views-ajax-link'), 'id' => 'views-rearrange-' . $type),
        'html' => TRUE,
      );
    }

    // Render the array of links
    $build['#actions'] = array(
      '#type' => 'dropbutton',
      '#links' => $actions,
      '#attributes' => array(
        'class' => array('views-ui-settings-bucket-operations'),
      ),
    );

    if (!$executable->display_handler->isDefaultDisplay()) {
      if (!$executable->display_handler->isDefaulted($types[$type]['plural'])) {
        $build['#overridden'] = TRUE;
      }
      else {
        $build['#defaulted'] = TRUE;
      }
    }

    static $relationships = NULL;
    if (!isset($relationships)) {
      // Get relationship labels.
      $relationships = array();
      foreach ($executable->display_handler->getHandlers('relationship') as $id => $handler) {
        $relationships[$id] = $handler->adminLabel();
      }
    }

    // Filters can now be grouped so we do a little bit extra:
    $groups = array();
    $grouping = FALSE;
    if ($type == 'filter') {
      $group_info = $executable->display_handler->getOption('filter_groups');
      // If there is only one group but it is using the "OR" filter, we still
      // treat it as a group for display purposes, since we want to display the
      // "OR" label next to items within the group.
      if (!empty($group_info['groups']) && (count($group_info['groups']) > 1 || current($group_info['groups']) == 'OR')) {
        $grouping = TRUE;
        $groups = array(0 => array());
      }
    }

    $build['fields'] = array();

    foreach ($executable->display_handler->getOption($types[$type]['plural']) as $id => $field) {
      // Build the option link for this handler ("Node: ID = article").
      $build['fields'][$id] = array();
      $build['fields'][$id]['#theme'] = 'views_ui_display_tab_setting';

      $handler = $executable->display_handler->getHandler($type, $id);
      if ($handler->broken()) {
        $build['fields'][$id]['#class'][] = 'broken';
        $field_name = $handler->adminLabel();
        $build['fields'][$id]['#link'] = $this->l($field_name, new Url('views_ui.form_handler', array(
          'js' => 'nojs',
          'view' => $view->id(),
          'display_id' => $display['id'],
          'type' => $type,
          'id' => $id,
        ), array('attributes' => array('class' => array('views-ajax-link')), 'html' => TRUE)));
        continue;
      }

      $field_name = String::checkPlain($handler->adminLabel(TRUE));
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
      }

      $description = Xss::filterAdmin($handler->adminSummary());
      $link_text = $field_name . (empty($description) ? '' : " ($description)");
      $link_attributes = array('class' => array('views-ajax-link'));
      if (!empty($field['exclude'])) {
        $link_attributes['class'][] = 'views-field-excluded';
        // Add a [hidden] marker, if the field is excluded.
        $link_text .= ' [' . $this->t('hidden') . ']';
      }
      $build['fields'][$id]['#link'] = $this->l($link_text, new Url('views_ui.form_handler', array(
        'js' => 'nojs',
        'view' => $view->id(),
        'display_id' => $display['id'],
        'type' => $type,
        'id' => $id,
      ), array('attributes' => $link_attributes, 'html' => TRUE)));
      $build['fields'][$id]['#class'][] = Html::cleanCssIdentifier($display['id']. '-' . $type . '-' . $id);

      if ($executable->display_handler->useGroupBy() && $handler->usesGroupBy()) {
        $build['fields'][$id]['#settings_links'][] = $this->l('<span class="label">' . $this->t('Aggregation settings') . '</span>', new Url('views_ui.form_handler_group', array(
          'js' => 'nojs',
          'view' => $view->id(),
          'display_id' => $display['id'],
          'type' => $type,
          'id' => $id,
        ), array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => $this->t('Aggregation settings')), 'html' => TRUE)));
      }

      if ($handler->hasExtraOptions()) {
        $build['fields'][$id]['#settings_links'][] = $this->l('<span class="label">' . $this->t('Settings') . '</span>', new Url('views_ui.form_handler_extra', array(
          'js' => 'nojs',
          'view' => $view->id(),
          'display_id' => $display['id'],
          'type' => $type,
          'id' => $id,
        ), array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => $this->t('Settings')), 'html' => TRUE)));
      }

      if ($grouping) {
        $gid = $handler->options['group'];

        // Show in default group if the group does not exist.
        if (empty($group_info['groups'][$gid])) {
          $gid = 0;
        }
        $groups[$gid][] = $id;
      }
    }

    // If using grouping, re-order fields so that they show up properly in the list.
    if ($type == 'filter' && $grouping) {
      $store = $build['fields'];
      $build['fields'] = array();
      foreach ($groups as $gid => $contents) {
        // Display an operator between each group.
        if (!empty($build['fields'])) {
          $build['fields'][] = array(
            '#theme' => 'views_ui_display_tab_setting',
            '#class' => array('views-group-text'),
            '#link' => ($group_info['operator'] == 'OR' ? $this->t('OR') : $this->t('AND')),
          );
        }
        // Display an operator between each pair of filters within the group.
        $keys = array_keys($contents);
        $last = end($keys);
        foreach ($contents as $key => $pid) {
          if ($key != $last) {
            $store[$pid]['#link'] .= '&nbsp;&nbsp;' . ($group_info['groups'][$gid] == 'OR' ? $this->t('OR') : $this->t('AND'));
          }
          $build['fields'][$pid] = $store[$pid];
        }
      }
    }

    return $build;
  }

  /**
   * Recursively adds microweights to a render array, similar to what
   * \Drupal::formBuilder()->doBuildForm() does for forms.
   *
   * @todo Submit a core patch to fix drupal_render() to do this, so that all
   *   render arrays automatically preserve array insertion order, as forms do.
   */
  public static function addMicroweights(&$build) {
    $count = 0;
    foreach (Element::children($build) as $key) {
      if (!isset($build[$key]['#weight'])) {
        $build[$key]['#weight'] = $count/1000;
      }
      static::addMicroweights($build[$key]);
      $count++;
    }
  }

}
