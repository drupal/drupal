<?php

/**
 * @file
 * Definition of Drupal\views_ui\ViewUI.
 */

namespace Drupal\views_ui;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Timer;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Database\Database;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Entity\View;
use Drupal\views\ViewEntityInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stores UI related temporary settings.
 */
class ViewUI implements ViewEntityInterface {

  /**
   * Indicates if a view is currently being edited.
   *
   * @var bool
   */
  public $editing = FALSE;

  /**
   * Stores an array of displays that have been changed.
   *
   * @var array
   */
  public $changed_display;

  /**
   * How long the view takes to build.
   *
   * @var int
   */
  public $build_time;

  /**
   * How long the view takes to render.
   *
   * @var int
   */
  public $render_time;

  /**
   * How long the view takes to execute.
   *
   * @var int
   */
  public $execute_time;

  /**
   * If this view is locked for editing.
   *
   * If this view is locked it will contain the result of
   * \Drupal\user\SharedTempStore::getMetadata(). Which can be a stdClass or
   * NULL.
   *
   * @var stdClass
   */
  public $lock;

  /**
   * If this view has been changed.
   *
   * @var bool
   */
  public $changed;

  /**
   * Stores options temporarily while editing.
   *
   * @var array
   */
  public $temporary_options;

  /**
   * Stores a stack of UI forms to display.
   *
   * @var array
   */
  public $stack;

  /**
   * Is the view runned in a context of the preview in the admin interface.
   *
   * @var bool
   */
  public $live_preview;

  public $renderPreview = FALSE;

  /**
   * The View storage object.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $storage;

  /**
   * Stores a list of database queries run beside the main one from views.
   *
   * @var array
   *
   * @see \Drupal\Core\Database\Log
   */
  protected $additionalQueries;

  /**
   * Contains an array of form keys and their respective classes.
   *
   * @var array
   */
  public static $forms = array(
    'add-handler' => '\Drupal\views_ui\Form\Ajax\AddItem',
    'analyze' => '\Drupal\views_ui\Form\Ajax\Analyze',
    'handler' => '\Drupal\views_ui\Form\Ajax\ConfigHandler',
    'handler-extra' => '\Drupal\views_ui\Form\Ajax\ConfigHandlerExtra',
    'handler-group' => '\Drupal\views_ui\Form\Ajax\ConfigHandlerGroup',
    'display' => '\Drupal\views_ui\Form\Ajax\Display',
    'edit-details' => '\Drupal\views_ui\Form\Ajax\EditDetails',
    'rearrange' => '\Drupal\views_ui\Form\Ajax\Rearrange',
    'rearrange-filter' => '\Drupal\views_ui\Form\Ajax\RearrangeFilter',
    'reorder-displays' => '\Drupal\views_ui\Form\Ajax\ReorderDisplays',
  );

  /**
   * Whether the config is being created, updated or deleted through the
   * import process.
   *
   * @var bool
   */
  private $isSyncing = FALSE;

  /**
   * Whether the config is being deleted through the uninstall process.
   *
   * @var bool
   */
  private $isUninstalling = FALSE;

  /**
   * Constructs a View UI object.
   *
   * @param \Drupal\views\ViewEntityInterface $storage
   *   The View storage object to wrap.
   */
  public function __construct(ViewEntityInterface $storage) {
    $this->entityType = 'view';
    $this->storage = $storage;
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::get().
   */
  public function get($property_name, $langcode = NULL) {
    if (property_exists($this->storage, $property_name)) {
      return $this->storage->get($property_name, $langcode);
    }

    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::setStatus().
   */
  public function setStatus($status) {
    return $this->storage->setStatus($status);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    if (property_exists($this->storage, $property_name)) {
      $this->storage->set($property_name, $value);
    }
    else {
      $this->{$property_name} = $value;
    }
  }

  public static function getDefaultAJAXMessage() {
    return SafeMarkup::set('<div class="message">' . t("Click on an item to edit that item's details.") . '</div>');
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($syncing) {
    $this->isSyncing = $syncing;
  }

  /**
   * {@inheritdoc}
   */
  public function setUninstalling($isUninstalling) {
    $this->isUninstalling = $isUninstalling;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->isSyncing;
  }

  /**
   * {@inheritdoc}
   */
  public function isUninstalling() {
    return $this->isUninstalling;
  }

  /**
   * Basic submit handler applicable to all 'standard' forms.
   *
   * This submit handler determines whether the user wants the submitted changes
   * to apply to the default display or to the current display, and dispatches
   * control appropriately.
   */
  public function standardSubmit($form, FormStateInterface $form_state) {
    // Determine whether the values the user entered are intended to apply to
    // the current display or the default display.

    list($was_defaulted, $is_defaulted, $revert) = $this->getOverrideValues($form, $form_state);

    // Based on the user's choice in the display dropdown, determine which display
    // these changes apply to.
    $display_id = $form_state->get('display_id');
    if ($revert) {
      // If it's revert just change the override and return.
      $display = &$this->getExecutable()->displayHandlers->get($display_id);
      $display->optionsOverride($form, $form_state);

      // Don't execute the normal submit handling but still store the changed view into cache.
      $this->cacheSet();
      return;
    }
    elseif ($was_defaulted === $is_defaulted) {
      // We're not changing which display these form values apply to.
      // Run the regular submit handler for this form.
    }
    elseif ($was_defaulted && !$is_defaulted) {
      // We were using the default display's values, but we're now overriding
      // the default display and saving values specific to this display.
      $display = &$this->getExecutable()->displayHandlers->get($display_id);
      // optionsOverride toggles the override of this section.
      $display->optionsOverride($form, $form_state);
      $display->submitOptionsForm($form, $form_state);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->getExecutable()->displayHandlers->get($display_id);
      // optionsOverride toggles the override of this section.
      $display->optionsOverride($form, $form_state);
      $display->submitOptionsForm($form, $form_state);
    }

    $submit_handler = [$form_state->getFormObject(), 'submitForm'];
    call_user_func_array($submit_handler, [&$form, $form_state]);
  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, FormStateInterface $form_state) {
    if (!empty($this->changed) && isset($this->form_cache)) {
      unset($this->form_cache);
      $this->cacheSet();
    }

    $form_state->setRedirectUrl($this->urlInfo('edit-form'));
  }

  /**
   * Provide a standard set of Apply/Cancel/OK buttons for the forms. Also provide
   * a hidden op operator because the forms plugin doesn't seem to properly
   * provide which button was clicked.
   *
   * TODO: Is the hidden op operator still here somewhere, or is that part of the
   * docblock outdated?
   */
  public function getStandardButtons(&$form, FormStateInterface $form_state, $form_id, $name = NULL) {
    $form['actions'] = array(
      '#type' => 'actions',
    );

    if (empty($name)) {
      $name = t('Apply');
      if (!empty($this->stack) && count($this->stack) > 1) {
        $name = t('Apply and continue');
      }
      $names = array(t('Apply'), t('Apply and continue'));
    }

    // Views provides its own custom handling of AJAX form submissions. Usually
    // this happens at the same path, but custom paths may be specified in
    // $form_state.
    $form_url = $form_state->get('url') ?: Url::fromRouteMatch(\Drupal::routeMatch());

    // Forms that are purely informational set an ok_button flag, so we know not
    // to create an "Apply" button for them.
    if (!$form_state->get('ok_button')) {
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $name,
        '#id' => 'edit-submit-' . Html::getUniqueId($form_id),
        // The regular submit handler ($form_id . '_submit') does not apply if
        // we're updating the default display. It does apply if we're updating
        // the current display. Since we have no way of knowing at this point
        // which display the user wants to update, views_ui_standard_submit will
        // take care of running the regular submit handler as appropriate.
        '#submit' => array(array($this, 'standardSubmit')),
        '#button_type' => 'primary',
        '#ajax' => array(
          'url' => $form_url,
        ),
      );
      // Form API button click detection requires the button's #value to be the
      // same between the form build of the initial page request, and the
      // initial form build of the request processing the form submission.
      // Ideally, the button's #value shouldn't change until the form rebuild
      // step. However, \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm()
      // implements a different multistep form workflow than the Form API does,
      // and adjusts $view->stack prior to form processing, so we compensate by
      // extending button click detection code to support any of the possible
      // button labels.
      if (isset($names)) {
        $form['actions']['submit']['#values'] = $names;
        $form['actions']['submit']['#process'] = array_merge(array('views_ui_form_button_was_clicked'), element_info_property($form['actions']['submit']['#type'], '#process', array()));
      }
      // If a validation handler exists for the form, assign it to this button.
      $form['actions']['submit']['#validate'][] = [$form_state->getFormObject(), 'validateForm'];
    }

    // Create a "Cancel" button. For purely informational forms, label it "OK".
    $cancel_submit = function_exists($form_id . '_cancel') ? $form_id . '_cancel' : array($this, 'standardCancel');
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => !$form_state->get('ok_button') ? t('Cancel') : t('Ok'),
      '#submit' => array($cancel_submit),
      '#validate' => array(),
      '#ajax' => array(
        'path' => $form_url,
      ),
      '#limit_validation_errors' => array(),
    );

    // Compatibility, to be removed later: // TODO: When is "later"?
    // We used to set these items on the form, but now we want them on the $form_state:
    if (isset($form['#title'])) {
      $form_state->set('title', $form['#title']);
    }
    if (isset($form['#section'])) {
      $form_state->set('#section', $form['#section']);
    }
    // Finally, we never want these cached -- our object cache does that for us.
    $form['#no_cache'] = TRUE;
  }

  /**
   * Return the was_defaulted, is_defaulted and revert state of a form.
   */
  public function getOverrideValues($form, FormStateInterface $form_state) {
    // Make sure the dropdown exists in the first place.
    if ($form_state->hasValue(array('override', 'dropdown'))) {
      // #default_value is used to determine whether it was the default value or not.
      // So the available options are: $display, 'default' and 'default_revert', not 'defaults'.
      $was_defaulted = ($form['override']['dropdown']['#default_value'] === 'defaults');
      $dropdown = $form_state->getValue(array('override', 'dropdown'));
      $is_defaulted = ($dropdown === 'default');
      $revert = ($dropdown === 'default_revert');

      if ($was_defaulted !== $is_defaulted && isset($form['#section'])) {
        // We're changing which display these values apply to.
        // Update the #section so it knows what to mark changed.
        $form['#section'] = str_replace('default-', $form_state->get('display_id') . '-', $form['#section']);
      }
    }
    else {
      // The user didn't get the dropdown for overriding the default display.
      $was_defaulted = FALSE;
      $is_defaulted = FALSE;
      $revert = FALSE;
    }

    return array($was_defaulted, $is_defaulted, $revert);
  }

  /**
   * Add another form to the stack; clicking 'apply' will go to this form
   * rather than closing the ajax popup.
   */
  public function addFormToStack($key, $display_id, $type, $id = NULL, $top = FALSE, $rebuild_keys = FALSE) {
    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    Html::resetSeenIds();

    if (empty($this->stack)) {
      $this->stack = array();
    }

    $stack = array(implode('-', array_filter(array($key, $this->id(), $display_id, $type, $id))), $key, $display_id, $type, $id);
    // If we're being asked to add this form to the bottom of the stack, no
    // special logic is required. Our work is equally easy if we were asked to add
    // to the top of the stack, but there's nothing in it yet.
    if (!$top || empty($this->stack)) {
      $this->stack[] = $stack;
    }
    // If we're adding to the top of an existing stack, we have to maintain the
    // existing integer keys, so they can be used for the "2 of 3" progress
    // indicator (which will now read "2 of 4").
    else {
      $keys = array_keys($this->stack);
      $first = current($keys);
      $last = end($keys);
      for ($i = $last; $i >= $first; $i--) {
        if (!isset($this->stack[$i])) {
          continue;
        }
        // Move form number $i to the next position in the stack.
        $this->stack[$i + 1] = $this->stack[$i];
        unset($this->stack[$i]);
      }
      // Now that the previously $first slot is free, move the new form into it.
      $this->stack[$first] = $stack;
      ksort($this->stack);

      // Start the keys from 0 again, if requested.
      if ($rebuild_keys) {
        $this->stack = array_values($this->stack);
      }
    }
  }

  /**
   * Submit handler for adding new item(s) to a view.
   */
  public function submitItemAdd($form, FormStateInterface $form_state) {
    $type = $form_state->get('type');
    $types = ViewExecutable::getHandlerTypes();
    $section = $types[$type]['plural'];
    $display_id = $form_state->get('display_id');

    // Handle the override select.
    list($was_defaulted, $is_defaulted) = $this->getOverrideValues($form, $form_state);
    if ($was_defaulted && !$is_defaulted) {
      // We were using the default display's values, but we're now overriding
      // the default display and saving values specific to this display.
      $display = &$this->getExecutable()->displayHandlers->get($display_id);
      // setOverride toggles the override of this section.
      $display->setOverride($section);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->getExecutable()->displayHandlers->get($display_id);
      // optionsOverride toggles the override of this section.
      $display->setOverride($section);
    }

    if (!$form_state->isValueEmpty('name') && is_array($form_state->getValue('name'))) {
      // Loop through each of the items that were checked and add them to the view.
      foreach (array_keys(array_filter($form_state->getValue('name'))) as $field) {
        list($table, $field) = explode('.', $field, 2);

        if ($cut = strpos($field, '$')) {
          $field = substr($field, 0, $cut);
        }
        $id = $this->getExecutable()->addHandler($display_id, $type, $table, $field);

        // check to see if we have group by settings
        $key = $type;
        // Footer,header and empty text have a different internal handler type(area).
        if (isset($types[$type]['type'])) {
          $key = $types[$type]['type'];
        }
        $item = array(
          'table' => $table,
          'field' => $field,
        );
        $handler = Views::handlerManager($key)->getHandler($item);
        if ($this->getExecutable()->displayHandlers->get('default')->useGroupBy() && $handler->usesGroupBy()) {
          $this->addFormToStack('handler-group', $display_id, $type, $id);
        }

        // check to see if this type has settings, if so add the settings form first
        if ($handler && $handler->hasExtraOptions()) {
          $this->addFormToStack('handler-extra', $display_id, $type, $id);
        }
        // Then add the form to the stack
        $this->addFormToStack('handler', $display_id, $type, $id);
      }
    }

    if (isset($this->form_cache)) {
      unset($this->form_cache);
    }

    // Store in cache
    $this->cacheSet();
  }

  /**
   * Set up query capturing.
   *
   * \Drupal\Core\Database\Database stores the queries that it runs, if logging
   * is enabled.
   *
   * @see ViewUI::endQueryCapture()
   */
  public function startQueryCapture() {
    Database::startLog('views');
  }

  /**
   * Add the list of queries run during render to buildinfo.
   *
   * @see ViewUI::startQueryCapture()
   */
  public function endQueryCapture() {
    $queries = Database::getLog('views');

    $this->additionalQueries = $queries;
  }

  public function renderPreview($display_id, $args = array()) {
    // Save the current path so it can be restored before returning from this function.
    $request_stack = \Drupal::requestStack();
    $current_request = $request_stack->getCurrentRequest();
    $executable = $this->getExecutable();

    // Determine where the query and performance statistics should be output.
    $config = \Drupal::config('views.settings');
    $show_query = $config->get('ui.show.sql_query.enabled');
    $show_info = $config->get('ui.show.preview_information');
    $show_location = $config->get('ui.show.sql_query.where');

    $show_stats = $config->get('ui.show.performance_statistics');
    if ($show_stats) {
      $show_stats = $config->get('ui.show.sql_query.where');
    }

    $combined = $show_query && $show_stats;

    $rows = array('query' => array(), 'statistics' => array());

    $errors = $executable->validate();
    $executable->destroy();
    if (empty($errors)) {
      $this->ajax = TRUE;
      $executable->live_preview = TRUE;

      // AJAX happens via HTTP POST but everything expects exposed data to
      // be in GET. Copy stuff but remove ajax-framework specific keys.
      // If we're clicking on links in a preview, though, we could actually
      // have some input in the query parameters, so we merge request() and
      // query() to ensure we get it all.
      $exposed_input = array_merge(\Drupal::request()->request->all(), \Drupal::request()->query->all());
      foreach (array('view_name', 'view_display_id', 'view_args', 'view_path', 'view_dom_id', 'pager_element', 'view_base_path', 'ajax_html_ids', 'ajax_page_state', 'form_id', 'form_build_id', 'form_token') as $key) {
        if (isset($exposed_input[$key])) {
          unset($exposed_input[$key]);
        }
      }
      $executable->setExposedInput($exposed_input);

      if (!$executable->setDisplay($display_id)) {
        return [
          '#markup' => t('Invalid display id @display', array('@display' => $display_id)),
        ];
      }

      $executable->setArguments($args);

      // Store the current view URL for later use:
      if ($executable->display_handler->getOption('path')) {
        $path = $executable->getUrl();
      }

      // Make view links come back to preview.

      // Also override the current path so we get the pager, and make sure the
      // Request object gets all of the proper values from $_SERVER.
      $request = Request::createFromGlobals();
      $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'entity.view.preview_form');
      $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, \Drupal::service('router.route_provider')->getRouteByName('entity.view.preview_form'));
      $request->attributes->set('view', $this->storage);
      $request->attributes->set('display_id', $display_id);
      $raw_parameters = new ParameterBag();
      $raw_parameters->set('view', $this->id());
      $raw_parameters->set('display_id', $display_id);
      $request->attributes->set('_raw_variables', $raw_parameters);

      foreach ($args as $key => $arg) {
        $request->attributes->set('arg_' . $key, $arg);
      }
      $request_stack->push($request);

      // Suppress contextual links of entities within the result set during a
      // Preview.
      // @todo We'll want to add contextual links specific to editing the View, so
      //   the suppression may need to be moved deeper into the Preview pipeline.
      views_ui_contextual_links_suppress_push();

      $show_additional_queries = $config->get('ui.show.additional_queries');

      Timer::start('entity.view.preview_form');

      if ($show_additional_queries) {
        $this->startQueryCapture();
      }

      // Execute/get the view preview.
      $preview = $executable->preview($display_id, $args);

      if ($show_additional_queries) {
        $this->endQueryCapture();
      }

      $this->render_time = Timer::stop('entity.view.preview_form');

      views_ui_contextual_links_suppress_pop();

      // Prepare the query information and statistics to show either above or
      // below the view preview.
      if ($show_info || $show_query || $show_stats) {
        // Get information from the preview for display.
        if (!empty($executable->build_info['query'])) {
          if ($show_query) {
            $query_string = $executable->build_info['query'];
            // Only the sql default class has a method getArguments.
            $quoted = array();

            if ($executable->query instanceof Sql) {
              $quoted = $query_string->getArguments();
              $connection = Database::getConnection();
              foreach ($quoted as $key => $val) {
                if (is_array($val)) {
                  $quoted[$key] = implode(', ', array_map(array($connection, 'quote'), $val));
                }
                else {
                  $quoted[$key] = $connection->quote($val);
                }
              }
            }
            $rows['query'][] = array(
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => "<strong>{% trans 'Query' %}</strong>",
                ),
              ),
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => '<pre>{{ query }}</pre>',
                  '#context' => array('query' => strtr($query_string, $quoted)),
                ),
              ),
            );
            if (!empty($this->additionalQueries)) {
              $queries = '<strong>' . t('These queries were run during view rendering:') . '</strong>';
              foreach ($this->additionalQueries as $query) {
                if ($queries) {
                  $queries .= "\n";
                }
                $query_string = strtr($query['query'], $query['args']);
                $queries .= t('[@time ms] @query', array('@time' => round($query['time'] * 100000, 1) / 100000.0, '@query' => $query_string));
              }

              $rows['query'][] = array(
                array(
                  'data' => array(
                    '#type' => 'inline_template',
                    '#template' => "<strong>{% trans 'Other queries' %}</strong>",
                  ),
                ),
                SafeMarkup::set('<pre>' . $queries . '</pre>'),
              );
            }
          }
          if ($show_info) {
            $rows['query'][] = array(
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => "<strong>{% trans 'Title' %}</strong>",
                ),
              ),
              Xss::filterAdmin($executable->getTitle()),
            );
            if (isset($path)) {
              // @todo Views should expect and store a leading /. See:
              //   https://www.drupal.org/node/2423913
              $path = \Drupal::l($path->toString(), $path);
            }
            else {
              $path = t('This display has no path.');
            }
            $rows['query'][] = array(SafeMarkup::set('<strong>' . t('Path') . '</strong>'), $path);
          }

          if ($show_stats) {
            $rows['statistics'][] = array(
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => "<strong>{% trans 'Query build time' %}</strong>",
                ),
              ),
              t('@time ms', array('@time' => intval($executable->build_time * 100000) / 100)),
            );

            $rows['statistics'][] = array(
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => "<strong>{% trans 'Query execute time' %}</strong>",
                ),
              ),
              t('@time ms', array('@time' => intval($executable->execute_time * 100000) / 100)),
            );

            $rows['statistics'][] = array(
              array(
                'data' => array(
                  '#type' => 'inline_template',
                  '#template' => "<strong>{% trans 'View render time' %}</strong>",
                ),
              ),
              t('@time ms', array('@time' => intval($executable->render_time * 100000) / 100)),
            );
          }
          \Drupal::moduleHandler()->alter('views_preview_info', $rows, $executable);
        }
        else {
          // No query was run. Display that information in place of either the
          // query or the performance statistics, whichever comes first.
          if ($combined || ($show_location === 'above')) {
            $rows['query'] = array(array(SafeMarkup::set('<strong>' . t('Query') . '</strong>'), t('No query was run')));
          }
          else {
            $rows['statistics'] = array(array(SafeMarkup::set('<strong>' . t('Query') . '</strong>'), t('No query was run')));
          }
        }
      }
    }
    else {
      foreach ($errors as $display_errors) {
        foreach ($display_errors as $error) {
          drupal_set_message($error, 'error');
        }
      }
      $preview = t('Unable to preview due to validation errors.');
    }

    // Assemble the preview, the query info, and the query statistics in the
    // requested order.
    $table = array(
      '#type' => 'table',
      '#prefix' => '<div class="views-query-info">',
      '#suffix' => '</div>',
    );
    if ($show_location === 'above' || $show_location === 'below') {
      if ($combined) {
        $table['#rows'] = array_merge($rows['query'], $rows['statistics']);
      }
      else {
        $table['#rows'] = $rows['query'];
      }
    }
    elseif ($show_stats === 'above' || $show_stats === 'below') {
      $table['#rows'] = $rows['statistics'];
    }

    if ($show_location === 'above' || $show_stats === 'above') {
      $output = [
        'table' => $table,
        'preview' => $preview,
      ];
    }
    elseif ($show_location === 'below' || $show_stats === 'below') {
      $output = [
        'preview' => $preview,
        'table' => $table,
      ];
    }

    // Ensure that we just remove an additional request we pushed earlier.
    // This could happen if $errors was not empty.
    if ($request_stack->getCurrentRequest() != $current_request) {
      $request_stack->pop();
    }
    return $output;
  }

  /**
   * Get the user's current progress through the form stack.
   *
   * @return
   *   FALSE if the user is not currently in a multiple-form stack. Otherwise,
   *   an associative array with the following keys:
   *   - current: The number of the current form on the stack.
   *   - total: The total number of forms originally on the stack.
   */
  public function getFormProgress() {
    $progress = FALSE;
    if (!empty($this->stack)) {
      // The forms on the stack have integer keys that don't change as the forms
      // are completed, so we can see which ones are still left.
      $keys = array_keys($this->stack);
      // Add 1 to the array keys for the benefit of humans, who start counting
      // from 1 and not 0.
      $current = reset($keys) + 1;
      $total = end($keys) + 1;
      if ($total > 1) {
        $progress = array();
        $progress['current'] = $current;
        $progress['total'] = $total;
      }
    }
    return $progress;
  }

  /**
   * Sets a cached view object in the user tempstore.
   */
  public function cacheSet() {
    if ($this->isLocked()) {
      drupal_set_message(t('Changes cannot be made to a locked view.'), 'error');
      return;
    }

    // Let any future object know that this view has changed.
    $this->changed = TRUE;

    $executable = $this->getExecutable();
    if (isset($executable->current_display)) {
      // Add the knowledge of the changed display, too.
      $this->changed_display[$executable->current_display] = TRUE;
      $executable->current_display = NULL;
    }

    // Unset handlers. We don't want to write these into the cache.
    $executable->display_handler = NULL;
    $executable->default_display = NULL;
    $executable->query = NULL;
    $executable->displayHandlers = NULL;
    \Drupal::service('user.shared_tempstore')->get('views')->set($this->id(), $this);
  }

  /**
   * Returns whether the current view is locked.
   *
   * @return bool
   *   TRUE if the view is locked, FALSE otherwise.
   */
  public function isLocked() {
    return is_object($this->lock) && ($this->lock->owner != \Drupal::currentUser()->id());
  }

  /**
   * Passes through all unknown calls onto the storage object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->storage, $method), $args);
  }

  /**
   * {@inheritdoc}
   */
  public function &getDisplay($display_id) {
    return $this->storage->getDisplay($display_id);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->storage->id();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::uuid().
   */
  public function uuid() {
    return $this->storage->uuid();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isNew().
   */
  public function isNew() {
    return $this->storage->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->storage->getEntityTypeId();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->storage->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->storage->getEntityType();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    return $this->storage->createDuplicate();
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    return View::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    return View::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = array()) {
    return View::create($values);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::delete().
   */
  public function delete() {
    return $this->storage->delete();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::save().
   */
  public function save() {
    return $this->storage->save();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::uri().
   */
  public function urlInfo($rel = 'edit-form', array $options = []) {
    return $this->storage->urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'edit-form', array $options = []) {
    return $this->storage->link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemPath($rel = 'edit-form') {
    return $this->storage->getSystemPath($rel);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::label().
   */
  public function label() {
    return $this->storage->label();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::enforceIsNew().
   */
  public function enforceIsNew($value = TRUE) {
    return $this->storage->enforceIsNew($value);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->storage->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->storage->language();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->storage->access($operation, $account, $return_as_object);
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::enable().
   */
  public function enable() {
    return $this->storage->enable();
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::disable().
   */
  public function disable() {
    return $this->storage->disable();
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::status().
   */
  public function status() {
    return $this->storage->status();
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    return $this->storage->getOriginalId();
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    return $this->storage->setOriginalId($id);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->storage->presave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->storage->postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    $this->storage->postCreate($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutable() {
    return $this->storage->getExecutable();
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateDisplayAsType($old_display_id, $new_display_type) {
    return $this->storage->duplicateDisplayAsType($old_display_id, $new_display_type);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDefaultDisplaysOptions() {
    $this->storage->mergeDefaultDisplaysOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function uriRelationships() {
    return $this->storage->uriRelationships();
  }

  /**
   * {@inheritdoc}
   */
   public function referencedEntities() {
     return $this->storage->referencedEntities();
   }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'edit-form', $options = array()) {
    return $this->storage->url($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    return $this->storage->hasLinkTemplate($key);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return $this->storage->getConfigDependencyKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->storage->getConfigDependencyName();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    return $this->storage->getConfigTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return $this->storage->onDependencyRemoval($dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return $this->storage->getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->storage->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->storage->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->storage->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedData() {
    $this->storage->getTypedData();
  }

  /**
   * {@inheritdoc}
   */
  public function addDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    return $this->storage->addDisplay($plugin_id, $title, $id);
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    return $this->storage->isInstallable();
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    return $this->storage->setThirdPartySetting($module, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->storage->getThirdPartySetting($module, $key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return $this->storage->getThirdPartySettings($module);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    return $this->storage->unsetThirdPartySetting($module, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return $this->storage->getThirdPartyProviders();
  }

}
