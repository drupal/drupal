<?php

/**
 * @file
 * Definition of Drupal\views_ui\ViewUI.
 */

namespace Drupal\views_ui;

use Drupal\views\ViewExecutable;
use Drupal\Core\Database\Database;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\Core\Entity\View;
use Drupal\views\ViewStorageInterface;

/**
 * Stores UI related temporary settings.
 */
class ViewUI implements ViewStorageInterface {

  /**
   * Indicates if a view is currently being edited.
   *
   * @var bool
   */
  public $editing = FALSE;

  /**
   * Stores an array of errors for any displays.
   *
   * @var array
   */
  public $display_errors;

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
   * @var bool
   */
  public $locked;

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
   * @var \Drupal\views\Plugin\Core\Entity\View
   */
  protected $storage;

  /**
   * The View executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * Stores a list of database queries run beside the main one from views.
   *
   * @var array
   *
   * @see \Drupal\Core\Database\Log
   */
  protected $additionalQueries;

  /**
   * Constructs a View UI object.
   *
   * @param \Drupal\views\ViewStorageInterface $storage
   *   The View storage object to wrap.
   */
  public function __construct(ViewStorageInterface $storage) {
    $this->entityType = 'view';
    $this->storage = $storage;
    $this->executable = $storage->get('executable');
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
  public function set($property_name, $value) {
    if (property_exists($this->storage, $property_name)) {
      $this->storage->set($property_name, $value);
    }
    else {
      $this->{$property_name} = $value;
    }
  }

  public static function getDefaultAJAXMessage() {
    return '<div class="message">' . t("Click on an item to edit that item's details.") . '</div>';
  }

  /**
   * Basic submit handler applicable to all 'standard' forms.
   *
   * This submit handler determines whether the user wants the submitted changes
   * to apply to the default display or to the current display, and dispatches
   * control appropriately.
   */
  public function standardSubmit($form, &$form_state) {
    // Determine whether the values the user entered are intended to apply to
    // the current display or the default display.

    list($was_defaulted, $is_defaulted, $revert) = $this->getOverrideValues($form, $form_state);

    // Based on the user's choice in the display dropdown, determine which display
    // these changes apply to.
    if ($revert) {
      // If it's revert just change the override and return.
      $display = &$this->executable->displayHandlers->get($form_state['display_id']);
      $display->optionsOverride($form, $form_state);

      // Don't execute the normal submit handling but still store the changed view into cache.
      views_ui_cache_set($this);
      return;
    }
    elseif ($was_defaulted === $is_defaulted) {
      // We're not changing which display these form values apply to.
      // Run the regular submit handler for this form.
    }
    elseif ($was_defaulted && !$is_defaulted) {
      // We were using the default display's values, but we're now overriding
      // the default display and saving values specific to this display.
      $display = &$this->executable->displayHandlers->get($form_state['display_id']);
      // optionsOverride toggles the override of this section.
      $display->optionsOverride($form, $form_state);
      $display->submitOptionsForm($form, $form_state);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->executable->displayHandlers->get($form_state['display_id']);
      // optionsOverride toggles the override of this section.
      $display->optionsOverride($form, $form_state);
      $display->submitOptionsForm($form, $form_state);
    }

    $submit_handler = $form['#form_id'] . '_submit';
    if (function_exists($submit_handler)) {
      $submit_handler($form, $form_state);
    }
  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, &$form_state) {
    if (!empty($this->changed) && isset($this->form_cache)) {
      unset($this->form_cache);
      views_ui_cache_set($this);
    }

    $form_state['redirect'] = 'admin/structure/views/view/' . $this->id() . '/edit';
  }

  /**
   * Provide a standard set of Apply/Cancel/OK buttons for the forms. Also provide
   * a hidden op operator because the forms plugin doesn't seem to properly
   * provide which button was clicked.
   *
   * TODO: Is the hidden op operator still here somewhere, or is that part of the
   * docblock outdated?
   */
  public function getStandardButtons(&$form, &$form_state, $form_id, $name = NULL, $third = NULL, $submit = NULL) {
    $form['buttons'] = array(
      '#prefix' => '<div class="clearfix"><div class="form-buttons">',
      '#suffix' => '</div></div>',
    );

    if (empty($name)) {
      $name = t('Apply');
      if (!empty($this->stack) && count($this->stack) > 1) {
        $name = t('Apply and continue');
      }
      $names = array(t('Apply'), t('Apply and continue'));
    }

    // Forms that are purely informational set an ok_button flag, so we know not
    // to create an "Apply" button for them.
    if (empty($form_state['ok_button'])) {
      $form['buttons']['submit'] = array(
        '#type' => 'submit',
        '#value' => $name,
        // The regular submit handler ($form_id . '_submit') does not apply if
        // we're updating the default display. It does apply if we're updating
        // the current display. Since we have no way of knowing at this point
        // which display the user wants to update, views_ui_standard_submit will
        // take care of running the regular submit handler as appropriate.
        '#submit' => array(array($this, 'standardSubmit')),
        '#button_type' => 'primary',
      );
      // Form API button click detection requires the button's #value to be the
      // same between the form build of the initial page request, and the initial
      // form build of the request processing the form submission. Ideally, the
      // button's #value shouldn't change until the form rebuild step. However,
      // views_ui_ajax_form() implements a different multistep form workflow than
      // the Form API does, and adjusts $view->stack prior to form processing, so
      // we compensate by extending button click detection code to support any of
      // the possible button labels.
      if (isset($names)) {
        $form['buttons']['submit']['#values'] = $names;
        $form['buttons']['submit']['#process'] = array_merge(array('views_ui_form_button_was_clicked'), element_info_property($form['buttons']['submit']['#type'], '#process', array()));
      }
      // If a validation handler exists for the form, assign it to this button.
      if (function_exists($form_id . '_validate')) {
        $form['buttons']['submit']['#validate'][] = $form_id . '_validate';
      }
    }

    // Create a "Cancel" button. For purely informational forms, label it "OK".
    $cancel_submit = function_exists($form_id . '_cancel') ? $form_id . '_cancel' : array($this, 'standardCancel');
    $form['buttons']['cancel'] = array(
      '#type' => 'submit',
      '#value' => empty($form_state['ok_button']) ? t('Cancel') : t('Ok'),
      '#submit' => array($cancel_submit),
      '#validate' => array(),
    );

    // Some forms specify a third button, with a name and submit handler.
    if ($third) {
      if (empty($submit)) {
        $submit = 'third';
      }
      $third_submit = function_exists($form_id . '_' . $submit) ? $form_id . '_' . $submit : array($this, 'standardCancel');

      $form['buttons'][$submit] = array(
        '#type' => 'submit',
        '#value' => $third,
        '#validate' => array(),
        '#submit' => array($third_submit),
      );
    }

    // Compatibility, to be removed later: // TODO: When is "later"?
    // We used to set these items on the form, but now we want them on the $form_state:
    if (isset($form['#title'])) {
      $form_state['title'] = $form['#title'];
    }
    if (isset($form['#url'])) {
      $form_state['url'] = $form['#url'];
    }
    if (isset($form['#section'])) {
      $form_state['#section'] = $form['#section'];
    }
    // Finally, we never want these cached -- our object cache does that for us.
    $form['#no_cache'] = TRUE;

    // If this isn't an ajaxy form, then we want to set the title.
    if (!empty($form['#title'])) {
      drupal_set_title($form['#title']);
    }
  }

  /**
   * Return the was_defaulted, is_defaulted and revert state of a form.
   */
  public function getOverrideValues($form, $form_state) {
    // Make sure the dropdown exists in the first place.
    if (isset($form_state['values']['override']['dropdown'])) {
      // #default_value is used to determine whether it was the default value or not.
      // So the available options are: $display, 'default' and 'default_revert', not 'defaults'.
      $was_defaulted = ($form['override']['dropdown']['#default_value'] === 'defaults');
      $is_defaulted = ($form_state['values']['override']['dropdown'] === 'default');
      $revert = ($form_state['values']['override']['dropdown'] === 'default_revert');

      if ($was_defaulted !== $is_defaulted && isset($form['#section'])) {
        // We're changing which display these values apply to.
        // Update the #section so it knows what to mark changed.
        $form['#section'] = str_replace('default-', $form_state['display_id'] . '-', $form['#section']);
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
   * Submit handler to break_lock a view.
   */
  public function submitBreakLock(&$form, &$form_state) {
    drupal_container()->get('user.tempstore')->get('views')->delete($this->id());
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->id() . '/edit';
    drupal_set_message(t('The lock has been broken and you may now edit this view.'));
  }

  /**
   * Form constructor callback to reorder displays on a view
   */
  public function buildDisplaysReorderForm($form, &$form_state) {
    $display_id = $form_state['display_id'];

    $form['view'] = array('#type' => 'value', '#value' => $this);

    $form['#tree'] = TRUE;

    $count = count($this->get('display'));

    $displays = $this->get('display');
    uasort($displays, array('static', 'sortPosition'));
    $this->set('display', $displays);
    foreach ($displays as $display) {
      $form[$display['id']] = array(
        'title'  => array('#markup' => $display['display_title']),
        'weight' => array(
          '#type' => 'weight',
          '#value' => $display['position'],
          '#delta' => $count,
          '#title' => t('Weight for @display', array('@display' => $display['display_title'])),
          '#title_display' => 'invisible',
        ),
        '#tree' => TRUE,
        '#display' => $display,
        'removed' => array(
          '#type' => 'checkbox',
          '#id' => 'display-removed-' . $display['id'],
          '#attributes' => array('class' => array('views-remove-checkbox')),
          '#default_value' => isset($display['deleted']),
        ),
      );

      if (isset($display['deleted']) && $display['deleted']) {
        $form[$display['id']]['deleted'] = array('#type' => 'value', '#value' => TRUE);
      }
      if ($display['id'] === 'default') {
        unset($form[$display['id']]['weight']);
        unset($form[$display['id']]['removed']);
      }

    }

    $form['#title'] = t('Displays Reorder');
    $form['#section'] = 'reorder';

    // Add javascript settings that will be added via $.extend for tabledragging
    $form['#js']['tableDrag']['reorder-displays']['weight'][0] = array(
      'target' => 'weight',
      'source' => NULL,
      'relationship' => 'sibling',
      'action' => 'order',
      'hidden' => TRUE,
      'limit' => 0,
    );

    $form['#action'] = url('admin/structure/views/nojs/reorder-displays/' . $this->id() . '/' . $display_id);

    $this->getStandardButtons($form, $form_state, 'views_ui_reorder_displays_form');
    $form['buttons']['submit']['#submit'] = array(array($this, 'submitDisplaysReorderForm'));

    return $form;
  }

  /**
   * Submit handler for rearranging display form
   */
  public function submitDisplaysReorderForm($form, &$form_state) {
    foreach ($form_state['input'] as $display => $info) {
      // add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
        $order[$display] = $info['weight'];
      }
    }

    // Sort the order array
    asort($order);

    // Fixing up positions
    $position = 1;

    foreach (array_keys($order) as $display) {
      $order[$display] = $position++;
    }

    // Setting up position and removing deleted displays
    $displays = $this->get('display');
    foreach ($displays as $display_id => $display) {
      // Don't touch the default !!!
      if ($display_id === 'default') {
        $displays[$display_id]['position'] = 0;
        continue;
      }
      if (isset($order[$display_id])) {
        $displays[$display_id]['position'] = $order[$display_id];
      }
      else {
        $displays[$display_id]['deleted'] = TRUE;
      }
    }

    // Sorting back the display array as the position is not enough
    uasort($displays, array('static', 'sortPosition'));
    $this->set('display', $displays);

    // Store in cache
    views_ui_cache_set($this);
    $form_state['redirect'] = array('admin/structure/views/view/' . $this->id() . '/edit', array('fragment' => 'views-tab-default'));
  }

  /**
   * Add another form to the stack; clicking 'apply' will go to this form
   * rather than closing the ajax popup.
   */
  public function addFormToStack($key, $display_id, $args, $top = FALSE, $rebuild_keys = FALSE) {
    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    if (empty($this->stack)) {
      $this->stack = array();
    }

    $stack = array($this->buildIdentifier($key, $display_id, $args), $key, $display_id, $args);
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
  public function submitItemAdd($form, &$form_state) {
    $type = $form_state['type'];
    $types = ViewExecutable::viewsHandlerTypes();
    $section = $types[$type]['plural'];

    // Handle the override select.
    list($was_defaulted, $is_defaulted) = $this->getOverrideValues($form, $form_state);
    if ($was_defaulted && !$is_defaulted) {
      // We were using the default display's values, but we're now overriding
      // the default display and saving values specific to this display.
      $display = &$this->executable->displayHandlers->get($form_state['display_id']);
      // setOverride toggles the override of this section.
      $display->setOverride($section);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->executable->displayHandlers->get($form_state['display_id']);
      // optionsOverride toggles the override of this section.
      $display->setOverride($section);
    }

    if (!empty($form_state['values']['name']) && is_array($form_state['values']['name'])) {
      // Loop through each of the items that were checked and add them to the view.
      foreach (array_keys(array_filter($form_state['values']['name'])) as $field) {
        list($table, $field) = explode('.', $field, 2);

        if ($cut = strpos($field, '$')) {
          $field = substr($field, 0, $cut);
        }
        $id = $this->executable->addItem($form_state['display_id'], $type, $table, $field);

        // check to see if we have group by settings
        $key = $type;
        // Footer,header and empty text have a different internal handler type(area).
        if (isset($types[$type]['type'])) {
          $key = $types[$type]['type'];
        }
        $handler = views_get_handler($table, $field, $key);
        if ($this->executable->displayHandlers->get('default')->useGroupBy() && $handler->usesGroupBy()) {
          $this->addFormToStack('config-item-group', $form_state['display_id'], array($type, $id));
        }

        // check to see if this type has settings, if so add the settings form first
        if ($handler && $handler->hasExtraOptions()) {
          $this->addFormToStack('config-item-extra', $form_state['display_id'], array($type, $id));
        }
        // Then add the form to the stack
        $this->addFormToStack('config-item', $form_state['display_id'], array($type, $id));
      }
    }

    if (isset($this->form_cache)) {
      unset($this->form_cache);
    }

    // Store in cache
    views_ui_cache_set($this);
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
    $old_q = current_path();

    // Determine where the query and performance statistics should be output.
    $config = config('views.settings');
    $show_query = $config->get('ui.show.sql_query.enabled');
    $show_info = $config->get('ui.show.preview_information');
    $show_location = $config->get('ui.show.sql_query.where');

    $show_stats = $config->get('ui.show.performance_statistics');
    if ($show_stats) {
      $show_stats = $config->get('ui.show.sql_query.where');
    }

    $combined = $show_query && $show_stats;

    $rows = array('query' => array(), 'statistics' => array());
    $output = '';

    $errors = $this->executable->validate();
    if ($errors === TRUE) {
      $this->ajax = TRUE;
      $this->executable->live_preview = TRUE;
      $this->views_ui_context = TRUE;

      // AJAX happens via $_POST but everything expects exposed data to
      // be in GET. Copy stuff but remove ajax-framework specific keys.
      // If we're clicking on links in a preview, though, we could actually
      // still have some in $_GET, so we use $_REQUEST to ensure we get it all.
      $exposed_input = drupal_container()->get('request')->request->all();
      foreach (array('view_name', 'view_display_id', 'view_args', 'view_path', 'view_dom_id', 'pager_element', 'view_base_path', 'ajax_html_ids', 'ajax_page_state', 'form_id', 'form_build_id', 'form_token') as $key) {
        if (isset($exposed_input[$key])) {
          unset($exposed_input[$key]);
        }
      }

      $this->executable->setExposedInput($exposed_input);

      if (!$this->executable->setDisplay($display_id)) {
        return t('Invalid display id @display', array('@display' => $display_id));
      }

      $this->executable->setArguments($args);

      // Store the current view URL for later use:
      if ($this->executable->display_handler->getOption('path')) {
        $path = $this->executable->getUrl();
      }

      // Make view links come back to preview.
      $this->override_path = 'admin/structure/views/view/' . $this->id() . '/preview/' . $display_id;

      // Also override the current path so we get the pager.
      $original_path = current_path();
      $q = _current_path($this->override_path);
      if ($args) {
        $q .= '/' . implode('/', $args);
        _current_path($q);
      }

      // Suppress contextual links of entities within the result set during a
      // Preview.
      // @todo We'll want to add contextual links specific to editing the View, so
      //   the suppression may need to be moved deeper into the Preview pipeline.
      views_ui_contextual_links_suppress_push();

      $show_additional_queries = $config->get('ui.show.additional_queries');

      timer_start('views_ui.preview');

      if ($show_additional_queries) {
        $this->startQueryCapture();
      }

      // Execute/get the view preview.
      $preview = $this->executable->preview($display_id, $args);

      if ($show_additional_queries) {
        $this->endQueryCapture();
      }

      $this->render_time = timer_stop('views_ui.preview');

      views_ui_contextual_links_suppress_pop();

      // Reset variables.
      unset($this->override_path);
      _current_path($original_path);

      // Prepare the query information and statistics to show either above or
      // below the view preview.
      if ($show_info || $show_query || $show_stats) {
        // Get information from the preview for display.
        if (!empty($this->executable->build_info['query'])) {
          if ($show_query) {
            $query_string = $this->executable->build_info['query'];
            // Only the sql default class has a method getArguments.
            $quoted = array();

            if ($this->executable->query instanceof Sql) {
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
            $rows['query'][] = array('<strong>' . t('Query') . '</strong>', '<pre>' . check_plain(strtr($query_string, $quoted)) . '</pre>');
            if (!empty($this->additionalQueries)) {
              $queries = '<strong>' . t('These queries were run during view rendering:') . '</strong>';
              foreach ($this->additionalQueries as $query) {
                if ($queries) {
                  $queries .= "\n";
                }
                $query_string = strtr($query['query'], $query['args']);
                $queries .= t('[@time ms] @query', array('@time' => round($query['time'] * 100000, 1) / 100000.0, '@query' => $query_string));
              }

              $rows['query'][] = array('<strong>' . t('Other queries') . '</strong>', '<pre>' . $queries . '</pre>');
            }
          }
          if ($show_info) {
            $rows['query'][] = array('<strong>' . t('Title') . '</strong>', filter_xss_admin($this->executable->getTitle()));
            if (isset($path)) {
              $path = l($path, $path);
            }
            else {
              $path = t('This display has no path.');
            }
            $rows['query'][] = array('<strong>' . t('Path') . '</strong>', $path);
          }

          if ($show_stats) {
            $rows['statistics'][] = array('<strong>' . t('Query build time') . '</strong>', t('@time ms', array('@time' => intval($this->executable->build_time * 100000) / 100)));
            $rows['statistics'][] = array('<strong>' . t('Query execute time') . '</strong>', t('@time ms', array('@time' => intval($this->executable->execute_time * 100000) / 100)));
            $rows['statistics'][] = array('<strong>' . t('View render time') . '</strong>', t('@time ms', array('@time' => intval($this->executable->render_time * 100000) / 100)));

          }
          drupal_alter('views_preview_info', $rows, $this);
        }
        else {
          // No query was run. Display that information in place of either the
          // query or the performance statistics, whichever comes first.
          if ($combined || ($show_location === 'above')) {
            $rows['query'] = array(array('<strong>' . t('Query') . '</strong>', t('No query was run')));
          }
          else {
            $rows['statistics'] = array(array('<strong>' . t('Query') . '</strong>', t('No query was run')));
          }
        }
      }
    }
    else {
      foreach ($errors as $error) {
        drupal_set_message($error, 'error');
      }
      $preview = t('Unable to preview due to validation errors.');
    }

    // Assemble the preview, the query info, and the query statistics in the
    // requested order.
    if ($show_location === 'above') {
      if ($combined) {
        $output .= '<div class="views-query-info">' . theme('table', array('rows' => array_merge($rows['query'], $rows['statistics']))) . '</div>';
      }
      else {
        $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['query'])) . '</div>';
      }
    }
    elseif ($show_stats === 'above') {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['statistics'])) . '</div>';
    }

    $output .= $preview;

    if ($show_location === 'below') {
      if ($combined) {
        $output .= '<div class="views-query-info">' . theme('table', array('rows' => array_merge($rows['query'], $rows['statistics']))) . '</div>';
      }
      else {
        $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['query'])) . '</div>';
      }
    }
    elseif ($show_stats === 'below') {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['statistics'])) . '</div>';
    }

    _current_path($old_q);
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
      $stack = $this->stack;
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
   * Build a form identifier that we can use to see if one form
   * is the same as another. Since the arguments differ slightly
   * we do a lot of spiffy concatenation here.
   */
  public function buildIdentifier($key, $display_id, $args) {
    $form = views_ui_ajax_forms($key);
    // Automatically remove the single-form cache if it exists and
    // does not match the key.
    $identifier = implode('-', array($key, $this->id(), $display_id));

    foreach ($form['args'] as $id) {
      $arg = (!empty($args)) ? array_shift($args) : NULL;
      $identifier .= '-' . $arg;
    }
    return $identifier;
  }

  /**
   * Display position sorting function
   */
  public static function sortPosition($display1, $display2) {
    if ($display1['position'] != $display2['position']) {
      return $display1['position'] < $display2['position'] ? -1 : 1;
    }

    return 0;
  }

  /**
   * Build up a $form_state object suitable for use with drupal_build_form
   * based on known information about a form.
   */
  public function buildFormState($js, $key, $display_id, $args) {
    $form = views_ui_ajax_forms($key);
    // Build up form state
    $form_state = array(
      'form_key' => $key,
      'form_id' => $form['form_id'],
      'view' => &$this,
      'ajax' => $js,
      'display_id' => $display_id,
      'no_redirect' => TRUE,
    );
    // If an method was specified, use that for the callback.
    if (isset($form['callback'])) {
      $form_state['build_info']['args'] = array();
      $form_state['build_info']['callback'] = array($this, $form['callback']);
    }

    foreach ($form['args'] as $id) {
      $form_state[$id] = (!empty($args)) ? array_shift($args) : NULL;
    }

    return $form_state;
  }

  /**
   * Passes through all unknown calls onto the storage object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->storage, $method), $args);
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::uuid().
   */
  public function uuid() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isNew().
   */
  public function isNew() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::entityType().
   */
  public function entityType() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isDefaultRevision().
   */
  public function isDefaultRevision($new_value = NULL) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::delete().
   */
  public function delete() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::save().
   */
  public function save() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isNewRevision().
   */
  public function isNewRevision() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::setNewRevision().
   */
  public function setNewRevision($value = TRUE) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::enforceIsNew().
   */
  public function enforceIsNew($value = TRUE) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::getExportProperties().
   */
  public function getExportProperties() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslation().
   */
  public function getTranslation($langcode, $strict = TRUE) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages().
   */
  public function getTranslationLanguages($include_default = TRUE) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::language)().
   */
  public function language() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', \Drupal\user\Plugin\Core\Entity\User $account = NULL) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty)().
   */
  public function isEmpty() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::enable().
   */
  public function enable() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::disable().
   */
  public function disable() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::status().
   */
  public function status() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::getOriginalID().
   */
  public function getOriginalID() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::setOriginalID().
   */
  public function setOriginalID($id) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getBCEntity().
   */
  public function getBCEntity() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getOriginalEntity().
   */
  public function getOriginalEntity() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getName().
   */
  public function getName() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getRoot().
   */
  public function getRoot() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getPropertyPath().
   */
  public function getPropertyPath() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getParent().
   */
  public function getParent() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::setContext().
   */
  public function setContext($name = NULL, ContextAwareInterface $parent = NULL) {
    return $this->__call(__FUNCTION__, func_get_args());
  }
}
