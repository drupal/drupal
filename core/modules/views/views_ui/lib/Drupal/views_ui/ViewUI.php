<?php

/**
 * @file
 * Definition of Drupal\views_ui\ViewUI.
 */

namespace Drupal\views_ui;

use Drupal\views\ViewExecutable;

/**
 * Stores UI related temporary settings.
 */
class ViewUI extends ViewExecutable {

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

  /**
   * Overrides Drupal\views\ViewExecutable::cloneView().
   */
  public function cloneView() {
    $storage = clone $this->storage;
    return $storage->getExecutable(TRUE, TRUE);
  }

  /**
   * Placeholder function for overriding $display['display_title'].
   *
   * @todo Remove this function once editing the display title is possible.
   */
  public function getDisplayLabel($display_id, $check_changed = TRUE) {
    $title = $display_id == 'default' ? t('Master') : $this->storage->display[$display_id]['display_title'];
    $title = views_ui_truncate($title, 25);

    if ($check_changed && !empty($this->changed_display[$display_id])) {
      $changed = '*';
      $title = $title . $changed;
    }

    return $title;
  }

  /**
   * Helper function to return the used display_id for the edit page
   *
   * This function handles access to the display.
   */
  public function getDisplayEditPage($display_id) {
    // Determine the displays available for editing.
    if ($tabs = $this->getDisplayTabs($display_id)) {
      // If a display isn't specified, use the first one.
      if (empty($display_id)) {
        foreach ($tabs as $id => $tab) {
          if (!isset($tab['#access']) || $tab['#access']) {
            $display_id = $id;
            break;
          }
        }
      }
      // If a display is specified, but we don't have access to it, return
      // an access denied page.
      if ($display_id && (!isset($tabs[$display_id]) || (isset($tabs[$display_id]['#access']) && !$tabs[$display_id]['#access']))) {
        return MENU_ACCESS_DENIED;
      }

      return $display_id;
    }
    elseif ($display_id) {
      return MENU_ACCESS_DENIED;
    }
    else {
      $display_id = NULL;
    }

    return $display_id;
  }

  /**
   * Helper function to get the display details section of the edit UI.
   *
   * @param $display
   *
   * @return array
   *   A renderable page build array.
   */
  public function getDisplayDetails($display) {
    $display_title = $this->getDisplayLabel($display['id'], FALSE);
    $build = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('id' => 'edit-display-settings-details'),
    );

    $is_display_deleted = !empty($display['deleted']);
    // The master display cannot be cloned.
    $is_default = $display['id'] == 'default';
    // @todo: Figure out why getOption doesn't work here.
    $is_enabled = $this->displayHandlers[$display['id']]->isEnabled();

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
            '#value' => t('enable @display_title', array('@display_title' => $display_title)),
            '#limit_validation_errors' => array(),
            '#submit' => array(array($this, 'submitDisplayEnable'), array($this, 'submitDelayDestination')),
            '#prefix' => '<li class="enable">',
            "#suffix" => '</li>',
          );
        }
        // Add a link to view the page.
        elseif ($this->displayHandlers[$display['id']]->hasPath()) {
          $path = $this->displayHandlers[$display['id']]->getPath();
          if (strpos($path, '%') === FALSE) {
            $build['top']['actions']['path'] = array(
              '#type' => 'link',
              '#title' => t('view @display', array('@display' => $display['display_title'])),
              '#options' => array('alt' => array(t("Go to the real page for this display"))),
              '#href' => $path,
              '#prefix' => '<li class="view">',
              "#suffix" => '</li>',
            );
          }
        }
        if (!$is_default) {
          $build['top']['actions']['duplicate'] = array(
            '#type' => 'submit',
            '#value' => t('clone @display_title', array('@display_title' => $display_title)),
            '#limit_validation_errors' => array(),
            '#submit' => array(array($this, 'submitDisplayDuplicate'), array($this, 'submitDelayDestination')),
            '#prefix' => '<li class="duplicate">',
            "#suffix" => '</li>',
          );
        }
        // Always allow a display to be deleted.
        $build['top']['actions']['delete'] = array(
          '#type' => 'submit',
          '#value' => t('delete @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array(array($this, 'submitDisplayDelete'), array($this, 'submitDelayDestination')),
          '#prefix' => '<li class="delete">',
          "#suffix" => '</li>',
        );
        if ($is_enabled) {
          $build['top']['actions']['disable'] = array(
            '#type' => 'submit',
            '#value' => t('disable @display_title', array('@display_title' => $display_title)),
            '#limit_validation_errors' => array(),
            '#submit' => array(array($this, 'submitDisplayDisable'), array($this, 'submitDelayDestination')),
            '#prefix' => '<li class="disable">',
            "#suffix" => '</li>',
          );
        }
      }
      else {
        $build['top']['actions']['undo_delete'] = array(
          '#type' => 'submit',
          '#value' => t('undo delete of @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array(array($this, 'submitDisplayUndoDelete'), array($this, 'submitDelayDestination')),
          '#prefix' => '<li class="undo-delete">',
          "#suffix" => '</li>',
        );
      }
      $build['top']['actions']['suffix']['#markup'] = '</ul>';

      // The area above the three columns.
      $build['top']['display_title'] = array(
        '#theme' => 'views_ui_display_tab_setting',
        '#description' => t('Display name'),
        '#link' => $this->displayHandlers[$display['id']]->optionLink(check_plain($display_title), 'display_title'),
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
    $build['columns']['second']['pager'] = array();

    // The third column buckets are wrapped in a fieldset.
    $build['columns']['third'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advanced'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#theme_wrappers' => array('fieldset', 'container'),
      '#attributes' => array(
        'class' => array(
          'views-display-column',
          'third',
        ),
      ),
    );

    // Collapse the fieldset by default.
    if (config('views.settings')->get('ui.show.advanced_column')) {
      $build['columns']['third']['#collapsed'] = FALSE;
    }

    // Each option (e.g. title, access, display as grid/table/list) fits into one
    // of several "buckets," or boxes (Format, Fields, Sort, and so on).
    $buckets = array();

    // Fetch options from the display plugin, with a list of buckets they go into.
    $options = array();
    $this->displayHandlers[$display['id']]->optionsSummary($buckets, $options);

    // Place each option into its bucket.
    foreach ($options as $id => $option) {
      // Each option self-identifies as belonging in a particular bucket.
      $buckets[$option['category']]['build'][$id] = $this->buildOptionForm($id, $option, $display);
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

    $build['columns']['first']['fields'] = $this->getFormBucket('field', $display);
    $build['columns']['first']['filters'] = $this->getFormBucket('filter', $display);
    $build['columns']['first']['sorts'] = $this->getFormBucket('sort', $display);
    $build['columns']['second']['header'] = $this->getFormBucket('header', $display);
    $build['columns']['second']['footer'] = $this->getFormBucket('footer', $display);
    $build['columns']['third']['arguments'] = $this->getFormBucket('argument', $display);
    $build['columns']['third']['relationships'] = $this->getFormBucket('relationship', $display);
    $build['columns']['third']['empty'] = $this->getFormBucket('empty', $display);

    return $build;
  }

  /**
   * Build a renderable array representing one option on the edit form.
   *
   * This function might be more logical as a method on an object, if a suitable
   * object emerges out of refactoring.
   */
  public function buildOptionForm($id, $option, $display) {
    $option_build = array();
    $option_build['#theme'] = 'views_ui_display_tab_setting';

    $option_build['#description'] = $option['title'];

    $option_build['#link'] = $this->displayHandlers[$display['id']]->optionLink($option['value'], $id, '', empty($option['desc']) ? '' : $option['desc']);

    $option_build['#links'] = array();
    if (!empty($option['links']) && is_array($option['links'])) {
      foreach ($option['links'] as $link_id => $link_value) {
        $option_build['#settings_links'][] = $this->displayHandlers[$display['id']]->optionLink($option['setting'], $link_id, 'views-button-configure', $link_value);
      }
    }

    if (!empty($this->displayHandlers[$display['id']]->options['defaults'][$id])) {
      $display_id = 'default';
      $option_build['#defaulted'] = TRUE;
    }
    else {
      $display_id = $display['id'];
      if (!$this->displayHandlers[$display['id']]->isDefaultDisplay()) {
        if ($this->displayHandlers[$display['id']]->defaultableSections($id)) {
          $option_build['#overridden'] = TRUE;
        }
      }
    }
    $option_build['#attributes']['class'][] = drupal_clean_css_identifier($display_id . '-' . $id);
    return $option_build;
  }

  /**
   * Render the top of the display so it can be updated during ajax operations.
   */
  public function renderDisplayTop($display_id) {
    $element['#theme_wrappers'] = array('views_ui_container');
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
          'title' => t('edit view name/description'),
          'href' => "admin/structure/views/nojs/edit-details/{$this->storage->name}",
          'attributes' => array('class' => array('views-ajax-link')),
        ),
        'analyze' => array(
          'title' => t('analyze view'),
          'href' => "admin/structure/views/nojs/analyze/{$this->storage->name}/$display_id",
          'attributes' => array('class' => array('views-ajax-link')),
        ),
        'clone' => array(
          'title' => t('clone view'),
          'href' => "admin/structure/views/view/{$this->storage->name}/clone",
        ),
        'reorder' => array(
          'title' => t('reorder displays'),
          'href' => "admin/structure/views/nojs/reorder-displays/{$this->storage->name}/$display_id",
          'attributes' => array('class' => array('views-ajax-link')),
        ),
      ),
    );

    // Let other modules add additional links here.
    drupal_alter('views_ui_display_top_links', $element['extra_actions']['#links'], $this, $display_id);

    if (isset($this->type) && $this->type != t('Default')) {
      if ($this->type == t('Overridden')) {
        $element['extra_actions']['#links']['revert'] = array(
          'title' => t('revert view'),
          'href' => "admin/structure/views/view/{$this->storage->name}/revert",
          'query' => array('destination' => "admin/structure/views/view/{$this->storage->name}"),
        );
      }
      else {
        $element['extra_actions']['#links']['delete'] = array(
          'title' => t('delete view'),
          'href' => "admin/structure/views/view/{$this->storage->name}/delete",
        );
      }
    }

    // Determine the displays available for editing.
    if ($tabs = $this->getDisplayTabs($display_id)) {
      if ($display_id) {
        $tabs[$display_id]['#active'] = TRUE;
      }
      $tabs['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2><ul id = "views-display-menu-tabs" class="tabs secondary">';
      $tabs['#suffix'] = '</ul>';
      $element['tabs'] = $tabs;
    }

    // Buttons for adding a new display.
    foreach (views_fetch_plugin_names('display', NULL, array($this->storage->base_table)) as $type => $label) {
      $element['add_display'][$type] = array(
        '#type' => 'submit',
        '#value' => t('Add !display', array('!display' => $label)),
        '#limit_validation_errors' => array(),
        '#submit' => array(array($this, 'submitDisplayAdd'), array($this, 'submitDelayDestination')),
        '#attributes' => array('class' => array('add-display')),
        // Allow JavaScript to remove the 'Add ' prefix from the button label when
        // placing the button in a "Add" dropdown menu.
        '#process' => array_merge(array('views_ui_form_button_was_clicked'), element_info_property('submit', '#process', array())),
        '#values' => array(t('Add !display', array('!display' => $label)), $label),
      );
    }

    return $element;
  }

  public static function getDefaultAJAXMessage() {
    return '<div class="message">' . t("Click on an item to edit that item's details.") . '</div>';
  }

  /**
   * Adds tabs for navigating across Displays when editing a View.
   *
   * This function can be called from hook_menu_local_tasks_alter() to implement
   * these tabs as secondary local tasks, or it can be called from elsewhere if
   * having them as secondary local tasks isn't desired. The caller is responsible
   * for setting the active tab's #active property to TRUE.
   *
   * @param $display_id
   *   The display_id which is edited on the current request.
   */
  public function getDisplayTabs($display_id = NULL) {
    $tabs = array();

    // Create a tab for each display.
    uasort($this->storage->display, array('static', 'sortPosition'));
    foreach ($this->storage->display as $id => $display) {
      $tabs[$id] = array(
        '#theme' => 'menu_local_task',
        '#link' => array(
          'title' => $this->getDisplayLabel($id),
          'href' => 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $id,
          'localized_options' => array(),
        ),
      );
      if (!empty($display['deleted'])) {
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-deleted-link';
      }
      if (isset($display['display_options']['enabled']) && !$display['display_options']['enabled']) {
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-disabled-link';
      }
    }

    // If the default display isn't supposed to be shown, don't display its tab, unless it's the only display.
    if ((!$this->isDefaultDisplayShown() && $display_id != 'default') && count($tabs) > 1) {
      $tabs['default']['#access'] = FALSE;
    }

    // Mark the display tab as red to show validation errors.
    $this->validate();
    foreach ($this->storage->display as $id => $display) {
      if (!empty($this->display_errors[$id])) {
        // Always show the tab.
        $tabs[$id]['#access'] = TRUE;
        // Add a class to mark the error and a title to make a hover tip.
        $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'error';
        $tabs[$id]['#link']['localized_options']['attributes']['title'] = t('This display has one or more validation errors; please review it.');
      }
    }

    return $tabs;
  }

  /**
   * Returns a renderable array representing the edit page for one display.
   */
  public function getDisplayTab($display_id) {
    $build = array();
    $display = $this->displayHandlers[$display_id];
    // If the plugin doesn't exist, display an error message instead of an edit
    // page.
    if (empty($display)) {
      $title = isset($display['display_title']) ? $display['display_title'] : t('Invalid');
      // @TODO: Improved UX for the case where a plugin is missing.
      $build['#markup'] = t("Error: Display @display refers to a plugin named '@plugin', but that plugin is not available.", array('@display' => $display['id'], '@plugin' => $display['display_plugin']));
    }
    // Build the content of the edit page.
    else {
      $build['details'] = $this->getDisplayDetails($display->display);
    }
    // In AJAX context, ViewUI::rebuildCurrentTab() returns this outside of form
    // context, so hook_form_views_ui_edit_form_alter() is insufficient.
    drupal_alter('views_ui_display_tab', $build, $this, $display_id);
    return $build;
  }

  /**
   * Controls whether or not the default display should have its own tab on edit.
   */
  public function isDefaultDisplayShown() {
    // Always show the default display for advanced users who prefer that mode.
    $advanced_mode = config('views.settings')->get('ui.show.master_display');
    // For other users, show the default display only if there are no others, and
    // hide it if there's at least one "real" display.
    $additional_displays = (count($this->displayHandlers) == 1);

    return $advanced_mode || $additional_displays;
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
      $display = &$this->displayHandlers[$form_state['display_id']];
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
      $display = &$this->displayHandlers[$form_state['display_id']];
      // optionsOverride toggles the override of this section.
      $display->optionsOverride($form, $form_state);
      $display->submitOptionsForm($form, $form_state);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->displayHandlers[$form_state['display_id']];
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

    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit';
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
   * Creates an array of Views admin CSS for adding or attaching.
   *
   * This returns an array of arrays. Each array represents a single
   * file. The array format is:
   * - file: The fully qualified name of the file to send to drupal_add_css
   * - options: An array of options to pass to drupal_add_css.
   */
  public static function getAdminCSS() {
    $module_path = drupal_get_path('module', 'views_ui');
    $list = array();
    $list[$module_path . '/css/views-admin.css'] = array();
    $list[$module_path . '/css/views-admin.theme.css'] = array();

    // Add in any theme specific CSS files we have
    $themes = list_themes();
    $theme_key = $GLOBALS['theme'];
    while ($theme_key) {
      // Try to find the admin css file for non-core themes.
      if (!in_array($theme_key, array('seven', 'bartik'))) {
        $theme_path = drupal_get_path('theme', $theme_key);
        // First search in the css directory, then in the root folder of the theme.
        if (file_exists($theme_path . "/css/views-admin.$theme_key.css")) {
          $list[$theme_path . "/css/views-admin.$theme_key.css"] = array(
            'group' => CSS_THEME,
          );
        }
        elseif (file_exists($theme_path . "/views-admin.$theme_key.css")) {
          $list[$theme_path . "/views-admin.$theme_key.css"] = array(
            'group' => CSS_THEME,
          );
        }
      }
      else {
        $list[$module_path . "/css/views-admin.$theme_key.css"] = array(
          'group' => CSS_THEME,
        );
      }
      $theme_key = isset($themes[$theme_key]->base_theme) ? $themes[$theme_key]->base_theme : '';
    }
    if (module_exists('contextual')) {
      $list[$module_path . '/css/views-admin.contextual.css'] = array();
    }

    return $list;
  }

  /**
   * Submit handler to add a display to a view.
   */
  public function submitDisplayAdd($form, &$form_state) {
    // Create the new display.
    $parents = $form_state['triggering_element']['#parents'];
    $display_type = array_pop($parents);
    $display_id = $this->storage->addDisplay($display_type);
    // A new display got added so the asterisks symbol should appear on the new
    // display.
    $this->current_display = $display_id;
    views_ui_cache_set($this);

    // Redirect to the new display's edit page.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $display_id;
  }

  /**
   * Submit handler to duplicate a display for a view.
   */
  public function submitDisplayDuplicate($form, &$form_state) {
    $display_id = $form_state['display_id'];

    // Create the new display.
    $display = $this->storage->display[$display_id];
    $new_display_id = $this->storage->addDisplay($display['display_plugin']);
    $this->storage->display[$new_display_id] = $display;
    $this->storage->display[$new_display_id]['id'] = $new_display_id;

    // By setting the current display the changed marker will appear on the new
    // display.
    $this->current_display = $new_display_id;
    views_ui_cache_set($this);

    // Redirect to the new display's edit page.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $new_display_id;
  }

  /**
   * Submit handler to delete a display from a view.
   */
  public function submitDisplayDelete($form, &$form_state) {
    $display_id = $form_state['display_id'];

    // Mark the display for deletion.
    $this->storage->display[$display_id]['deleted'] = TRUE;
    views_ui_cache_set($this);

    // Redirect to the top-level edit page. The first remaining display will
    // become the active display.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name;
  }

  /**
   * Submit handler for form buttons that do not complete a form workflow.
   *
   * The Edit View form is a multistep form workflow, but with state managed by
   * the TempStore rather than $form_state['rebuild']. Without this
   * submit handler, buttons that add or remove displays would redirect to the
   * destination parameter (e.g., when the Edit View form is linked to from a
   * contextual link). This handler can be added to buttons whose form submission
   * should not yet redirect to the destination.
   */
  public function submitDelayDestination($form, &$form_state) {
    $query = drupal_container()->get('request')->query;
    // @todo: Revisit this when http://drupal.org/node/1668866 is in.
    $destination = $query->get('destination');
    if (isset($destination) && $form_state['redirect'] !== FALSE) {
      if (!isset($form_state['redirect'])) {
        $form_state['redirect'] = current_path();
      }
      if (is_string($form_state['redirect'])) {
        $form_state['redirect'] = array($form_state['redirect']);
      }
      $options = isset($form_state['redirect'][1]) ? $form_state['redirect'][1] : array();
      if (!isset($options['query']['destination'])) {
        $options['query']['destination'] = $destination;
      }
      $form_state['redirect'][1] = $options;
      $query->remove('destination');
    }
  }

  /**
   * Submit handler to enable a disabled display.
   */
  public function submitDisplayEnable($form, &$form_state) {
    $id = $form_state['display_id'];
    // setOption doesn't work because this would might affect upper displays
    $this->displayHandlers[$id]->setOption('enabled', TRUE);

    // Store in cache
    views_ui_cache_set($this);

    // Redirect to the top-level edit page.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $id;
  }

  /**
   * Submit handler to disable display.
   */
  public function submitDisplayDisable($form, &$form_state) {
    $id = $form_state['display_id'];
    $this->displayHandlers[$id]->setOption('enabled', FALSE);

    // Store in cache
    views_ui_cache_set($this);

    // Redirect to the top-level edit page.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $id;
  }

  /**
   * Submit handler to add a restore a removed display to a view.
   */
  public function submitDisplayUndoDelete($form, &$form_state) {
    // Create the new display
    $id = $form_state['display_id'];
    $this->storage->display[$id]['deleted'] = FALSE;

    // Store in cache
    views_ui_cache_set($this);

    // Redirect to the top-level edit page.
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit/' . $id;
  }

  /**
   * Add information about a section to a display.
   */
  public function getFormBucket($type, $display) {
    $build = array(
      '#theme_wrappers' => array('views_ui_display_tab_bucket'),
    );
    $types = static::viewsHandlerTypes();

    $build['#overridden'] = FALSE;
    $build['#defaulted'] = FALSE;

    $build['#name'] = $build['#title'] = $types[$type]['title'];

    // Different types now have different rearrange forms, so we use this switch
    // to get the right one.
    switch ($type) {
      case 'filter':
        $rearrange_url = "admin/structure/views/nojs/rearrange-$type/{$this->storage->name}/{$display['id']}/$type";
        $rearrange_text = t('And/Or, Rearrange');
        // TODO: Add another class to have another symbol for filter rearrange.
        $class = 'icon compact rearrange';
        break;
      case 'field':
        // Fetch the style plugin info so we know whether to list fields or not.
        $style_plugin = $this->displayHandlers[$display['id']]->getPlugin('style');
        $uses_fields = $style_plugin && $style_plugin->usesFields();
        if (!$uses_fields) {
          $build['fields'][] = array(
            '#markup' => t('The selected style or row format does not utilize fields.'),
            '#theme_wrappers' => array('views_ui_container'),
            '#attributes' => array('class' => array('views-display-setting')),
          );
          return $build;
        }

      default:
        $rearrange_url = "admin/structure/views/nojs/rearrange/{$this->storage->name}/{$display['id']}/$type";
        $rearrange_text = t('Rearrange');
        $class = 'icon compact rearrange';
    }

    // Create an array of actions to pass to theme_links
    $actions = array();
    $count_handlers = count($this->displayHandlers[$display['id']]->getHandlers($type));
    $actions['add'] = array(
      'title' => t('Add'),
      'href' => "admin/structure/views/nojs/add-item/{$this->storage->name}/{$display['id']}/$type",
      'attributes' => array('class' => array('icon compact add', 'views-ajax-link'), 'title' => t('Add'), 'id' => 'views-add-' . $type),
      'html' => TRUE,
    );
    if ($count_handlers > 0) {
      $actions['rearrange'] = array(
        'title' => $rearrange_text,
        'href' => $rearrange_url,
        'attributes' => array('class' => array($class, 'views-ajax-link'), 'title' => t('Rearrange'), 'id' => 'views-rearrange-' . $type),
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

    if (!$this->displayHandlers[$display['id']]->isDefaultDisplay()) {
      if (!$this->displayHandlers[$display['id']]->isDefaulted($types[$type]['plural'])) {
        $build['#overridden'] = TRUE;
      }
      else {
        $build['#defaulted'] = TRUE;
      }
    }

    // If there's an options form for the bucket, link to it.
    if (!empty($types[$type]['options'])) {
      $build['#title'] = l($build['#title'], "admin/structure/views/nojs/config-type/{$this->storage->name}/{$display['id']}/$type", array('attributes' => array('class' => array('views-ajax-link'), 'id' => 'views-title-' . $type)));
    }

    static $relationships = NULL;
    if (!isset($relationships)) {
      // Get relationship labels
      $relationships = array();
      foreach ($this->displayHandlers[$display['id']]->getHandlers('relationship') as $id => $handler) {
        $relationships[$id] = $handler->label();
      }
    }

    // Filters can now be grouped so we do a little bit extra:
    $groups = array();
    $grouping = FALSE;
    if ($type == 'filter') {
      $group_info = $this->display_handler->getOption('filter_groups');
      // If there is only one group but it is using the "OR" filter, we still
      // treat it as a group for display purposes, since we want to display the
      // "OR" label next to items within the group.
      if (!empty($group_info['groups']) && (count($group_info['groups']) > 1 || current($group_info['groups']) == 'OR')) {
        $grouping = TRUE;
        $groups = array(0 => array());
      }
    }

    $build['fields'] = array();

    foreach ($this->displayHandlers[$display['id']]->getOption($types[$type]['plural']) as $id => $field) {
      // Build the option link for this handler ("Node: ID = article").
      $build['fields'][$id] = array();
      $build['fields'][$id]['#theme'] = 'views_ui_display_tab_setting';

      $handler = $this->displayHandlers[$display['id']]->getHandler($type, $id);
      if (empty($handler)) {
        $build['fields'][$id]['#class'][] = 'broken';
        $field_name = t('Broken/missing handler: @table > @field', array('@table' => $field['table'], '@field' => $field['field']));
        $build['fields'][$id]['#link'] = l($field_name, "admin/structure/views/nojs/config-item/{$this->storage->name}/{$display['id']}/$type/$id", array('attributes' => array('class' => array('views-ajax-link')), 'html' => TRUE));
        continue;
      }

      $field_name = check_plain($handler->adminLabel(TRUE));
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
      }

      $description = filter_xss_admin($handler->adminSummary());
      $link_text = $field_name . (empty($description) ? '' : " ($description)");
      $link_attributes = array('class' => array('views-ajax-link'));
      if (!empty($field['exclude'])) {
        $link_attributes['class'][] = 'views-field-excluded';
      }
      $build['fields'][$id]['#link'] = l($link_text, "admin/structure/views/nojs/config-item/{$this->storage->name}/{$display['id']}/$type/$id", array('attributes' => $link_attributes, 'html' => TRUE));
      $build['fields'][$id]['#class'][] = drupal_clean_css_identifier($display['id']. '-' . $type . '-' . $id);

      if ($this->displayHandlers[$display['id']]->useGroupBy() && $handler->usesGroupBy()) {
        $build['fields'][$id]['#settings_links'][] = l('<span class="label">' . t('Aggregation settings') . '</span>', "admin/structure/views/nojs/config-item-group/{$this->storage->name}/{$display['id']}/$type/$id", array('attributes' => array('class' => 'views-button-configure views-ajax-link', 'title' => t('Aggregation settings')), 'html' => TRUE));
      }

      if ($handler->hasExtraOptions()) {
        $build['fields'][$id]['#settings_links'][] = l('<span class="label">' . t('Settings') . '</span>', "admin/structure/views/nojs/config-item-extra/{$this->storage->name}/{$display['id']}/$type/$id", array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => t('Settings')), 'html' => TRUE));
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
            '#link' => ($group_info['operator'] == 'OR' ? t('OR') : t('AND')),
          );
        }
        // Display an operator between each pair of filters within the group.
        $keys = array_keys($contents);
        $last = end($keys);
        foreach ($contents as $key => $pid) {
          if ($key != $last) {
            $store[$pid]['#link'] .= '&nbsp;&nbsp;' . ($group_info['groups'][$gid] == 'OR' ? t('OR') : t('AND'));
          }
          $build['fields'][$pid] = $store[$pid];
        }
      }
    }

    return $build;
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
   * Regenerate the current tab for AJAX updates.
   */
  public function rebuildCurrentTab(&$output, $display_id) {
    if (!$this->setDisplay('default')) {
      return;
    }

    // Regenerate the main display area.
    $build = $this->getDisplayTab($display_id);
    static::addMicroweights($build);
    $output[] = ajax_command_html('#views-tab-' . $display_id, drupal_render($build));

    // Regenerate the top area so changes to display names and order will appear.
    $build = $this->renderDisplayTop($display_id);
    static::addMicroweights($build);
    $output[] = ajax_command_replace('#views-display-top', drupal_render($build));
  }

  /**
   * Submit handler to break_lock a view.
   */
  public function submitBreakLock(&$form, &$form_state) {
    drupal_container()->get('user.tempstore')->get('views')->delete($this->storage->name);
    $form_state['redirect'] = 'admin/structure/views/view/' . $this->storage->name . '/edit';
    drupal_set_message(t('The lock has been broken and you may now edit this view.'));
  }

  public static function buildAddForm($form, &$form_state) {
    $form['#attached']['css'] = static::getAdminCSS();
    $form['#attached']['js'][] = drupal_get_path('module', 'views_ui') . '/js/views-admin.js';
    $form['#attributes']['class'] = array('views-admin');

    $form['human_name'] = array(
      '#type' => 'textfield',
      '#title' => t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'views_get_view',
        'source' => array('human_name'),
      ),
      '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['description_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Description'),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide description'),
      '#title_display' => 'invisible',
      '#size' => 64,
      '#default_value' => '',
      '#states' => array(
        'visible' => array(
          ':input[name="description_enable"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Create a wrapper for the entire dynamic portion of the form. Everything
    // that can be updated by AJAX goes somewhere inside here. For example, this
    // is needed by "Show" dropdown (below); it changes the base table of the
    // view and therefore potentially requires all options on the form to be
    // dynamically updated.
    $form['displays'] = array();

    // Create the part of the form that allows the user to select the basic
    // properties of what the view will display.
    $form['displays']['show'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    // Create the "Show" dropdown, which allows the base table of the view to be
    // selected.
    $wizard_plugins = views_ui_get_wizards();
    $options = array();
    foreach ($wizard_plugins as $key => $wizard) {
      $options[$key] = $wizard['title'];
    }
    $form['displays']['show']['wizard_key'] = array(
      '#type' => 'select',
      '#title' => t('Show'),
      '#options' => $options,
    );
    $show_form = &$form['displays']['show'];
    $default_value = module_exists('node') ? 'node' : 'users';
    $show_form['wizard_key']['#default_value'] = views_ui_get_selected($form_state, array('show', 'wizard_key'), $default_value, $show_form['wizard_key']);
    // Changing this dropdown updates the entire content of $form['displays'] via
    // AJAX.
    views_ui_add_ajax_trigger($show_form, 'wizard_key', array('displays'));

    // Build the rest of the form based on the currently selected wizard plugin.
    $wizard_key = $show_form['wizard_key']['#default_value'];

    views_include_handlers();
    $wizard_instance = views_get_plugin('wizard', $wizard_key);

    $form = $wizard_instance->build_form($form, $form_state);

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save & exit'),
      '#validate' => array('views_ui_wizard_form_validate'),
      '#submit' => array('views_ui_add_form_save_submit'),
    );
    $form['continue'] = array(
      '#type' => 'submit',
      '#value' => t('Continue & edit'),
      '#validate' => array('views_ui_wizard_form_validate'),
      '#submit' => array('views_ui_add_form_store_edit_submit'),
      '#process' => array_merge(array(array(get_called_class(), 'processDefaultButton')), element_info_property('submit', '#process', array())),
    );
    $form['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => array('views_ui_add_form_cancel_submit'),
      '#limit_validation_errors' => array(),
    );

    return $form;
  }

  /**
   * Form builder callback for editing a View.
   *
   * @todo Remove as many #prefix/#suffix lines as possible. Use #theme_wrappers
   *   instead.
   *
   * @todo Rename to views_ui_edit_view_form(). See that function for the "old"
   *   version.
   *
   * @see views_ui_ajax_get_form()
   */
  public function buildEditForm($form, &$form_state, $display_id = NULL) {
    // Do not allow the form to be cached, because $form_state['view'] can become
    // stale between page requests.
    // See views_ui_ajax_get_form() for how this affects #ajax.
    // @todo To remove this and allow the form to be cacheable:
    //   - Change $form_state['view'] to $form_state['temporary']['view'].
    //   - Add a #process function to initialize $form_state['temporary']['view']
    //     on cached form submissions.
    //   - Use form_load_include().
    $form_state['no_cache'] = TRUE;

    if ($display_id) {
      if (!$this->setDisplay($display_id)) {
        $form['#markup'] = t('Invalid display id @display', array('@display' => $display_id));
        return $form;
      }
    }

    $form['#tree'] = TRUE;
    // @todo When more functionality is added to this form, cloning here may be
    //   too soon. But some of what we do with $view later in this function
    //   results in making it unserializable due to PDO limitations.
    $form_state['view'] = clone($this);

    $form['#attached']['library'][] = array('system', 'jquery.ui.tabs');
    $form['#attached']['library'][] = array('system', 'jquery.ui.dialog');
    $form['#attached']['library'][] = array('system', 'drupal.ajax');
    $form['#attached']['library'][] = array('system', 'jquery.form');
    $form['#attached']['library'][] = array('system', 'drupal.states');
    $form['#attached']['library'][] = array('system', 'drupal.tabledrag');

    $form['#attached']['css'] = static::getAdminCSS();

    $form['#attached']['js'][] = drupal_get_path('module', 'views_ui') . '/js/views-admin.js';
    $form['#attached']['js'][] = array(
      'data' => array('views' => array('ajax' => array(
        'id' => '#views-ajax-body',
        'title' => '#views-ajax-title',
        'popup' => '#views-ajax-popup',
        'defaultForm' => static::getDefaultAJAXMessage(),
      ))),
      'type' => 'setting',
    );

    $form += array(
      '#prefix' => '',
      '#suffix' => '',
    );
    $form['#prefix'] .= '<div class="views-edit-view views-admin clearfix">';
    $form['#suffix'] = '</div>' . $form['#suffix'];

    $form['#attributes']['class'] = array('form-edit');

    if (isset($this->locked) && is_object($this->locked) && $this->locked->owner != $GLOBALS['user']->uid) {
      $form['locked'] = array(
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('view-locked', 'messages', 'warning')),
        '#markup' => t('This view is being edited by user !user, and is therefore locked from editing by others. This lock is !age old. Click here to <a href="!break">break this lock</a>.', array('!user' => theme('username', array('account' => user_load($this->locked->owner))), '!age' => format_interval(REQUEST_TIME - $this->locked->updated), '!break' => url('admin/structure/views/view/' . $this->storage->name . '/break-lock'))),
      );
    }
    else {
      if (isset($this->vid) && $this->vid == 'new') {
        $message = t('* All changes are stored temporarily. Click Save to make your changes permanent. Click Cancel to discard the view.');
      }
      else {
        $message = t('* All changes are stored temporarily. Click Save to make your changes permanent. Click Cancel to discard your changes.');
      }

      $form['changed'] = array(
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('view-changed', 'messages', 'warning')),
        '#markup' => $message,
      );
      if (empty($this->changed)) {
        $form['changed']['#attributes']['class'][] = 'js-hide';
      }
    }

    $form['help_text'] = array(
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#markup' => t('Modify the display(s) of your view below or add new displays.'),
    );

    $form['actions'] = array(
      '#type' => 'actions',
      '#weight' => 0,
    );

    if (empty($this->changed)) {
      $form['actions']['#attributes'] = array(
        'class' => array(
          'js-hide',
        ),
      );
    }

    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      // Taken from the "old" UI. @TODO: Review and rename.
      '#validate' => array('views_ui_edit_view_form_validate'),
      '#submit' => array('views_ui_edit_view_form_submit'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => array('views_ui_edit_view_form_cancel'),
    );

    $form['displays'] = array(
      '#prefix' => '<h1 class="unit-title clearfix">' . t('Displays') . '</h1>' . "\n" . '<div class="views-displays">',
      '#suffix' => '</div>',
    );

    $form['displays']['top'] = $this->renderDisplayTop($display_id);

    // The rest requires a display to be selected.
    if ($display_id) {
      $form_state['display_id'] = $display_id;

      // The part of the page where editing will take place.
      $form['displays']['settings'] = array(
        '#type' => 'container',
        '#id' => 'edit-display-settings',
      );
      $display_title = $this->getDisplayLabel($display_id, FALSE);

      $form['displays']['settings']['#title'] = '<h2>' . t('@display_title details', array('@display_title' => ucwords($display_title))) . '</h2>';

      // Add a text that the display is disabled.
      if (!empty($this->displayHandlers[$display_id])) {
        if (!$this->displayHandlers[$display_id]->isEnabled()) {
          $form['displays']['settings']['disabled']['#markup'] = t('This display is disabled.');
        }
      }

      $form['displays']['settings']['settings_content']= array(
        '#theme_wrappers' => array('container'),
      );
      // Add the edit display content
      $form['displays']['settings']['settings_content']['tab_content'] = $this->getDisplayTab($display_id);
      $form['displays']['settings']['settings_content']['tab_content']['#theme_wrappers'] = array('container');
      $form['displays']['settings']['settings_content']['tab_content']['#attributes'] = array('class' => array('views-display-tab'));
      $form['displays']['settings']['settings_content']['tab_content']['#id'] = 'views-tab-' . $display_id;
      // Mark deleted displays as such.
      if (!empty($this->storage->display[$display_id]['deleted'])) {
        $form['displays']['settings']['settings_content']['tab_content']['#attributes']['class'][] = 'views-display-deleted';
      }
      // Mark disabled displays as such.
      if (!$this->displayHandlers[$display_id]->isEnabled()) {
        $form['displays']['settings']['settings_content']['tab_content']['#attributes']['class'][] = 'views-display-disabled';
      }

      // The content of the popup dialog.
      $form['ajax-area'] = array(
        '#theme_wrappers' => array('container'),
        '#id' => 'views-ajax-popup',
      );
      $form['ajax-area']['ajax-title'] = array(
        '#markup' => '<h2 id="views-ajax-title"></h2>',
      );
      $form['ajax-area']['ajax-body'] = array(
        '#theme_wrappers' => array('container'),
        '#id' => 'views-ajax-body',
        '#markup' => static::getDefaultAJAXMessage(),
      );
    }

    // If relationships had to be fixed, we want to get that into the cache
    // so that edits work properly, and to try to get the user to save it
    // so that it's not using weird fixed up relationships.
    if (!empty($this->relationships_changed) && drupal_container()->get('request')->request->count()) {
      drupal_set_message(t('This view has been automatically updated to fix missing relationships. While this View should continue to work, you should verify that the automatic updates are correct and save this view.'));
      views_ui_cache_set($this);
    }
    return $form;
  }

  /**
   * Provide the preview formulas and the preview output, too.
   */
  public function buildPreviewForm($form, &$form_state, $display_id = 'default') {
    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    $form_state['no_cache'] = TRUE;
    $form_state['view'] = $this;

    $form['#attributes'] = array('class' => array('clearfix'));

    // Add a checkbox controlling whether or not this display auto-previews.
    $form['live_preview'] = array(
      '#type' => 'checkbox',
      '#id' => 'edit-displays-live-preview',
      '#title' => t('Auto preview'),
      '#default_value' => config('views.settings')->get('ui.always_live_preview'),
    );

    // Add the arguments textfield
    $form['view_args'] = array(
      '#type' => 'textfield',
      '#title' => t('Preview with contextual filters:'),
      '#description' => t('Separate contextual filter values with a "/". For example, %example.', array('%example' => '40/12/10')),
      '#id' => 'preview-args',
    );

    // Add the preview button
    $form['button'] = array(
      '#type' => 'submit',
      '#value' => t('Update preview'),
      '#attributes' => array('class' => array('arguments-preview')),
      '#prefix' => '<div id="preview-submit-wrapper">',
      '#suffix' => '</div>',
      '#id' => 'preview-submit',
      '#ajax' => array(
        'path' => 'admin/structure/views/view/' . $this->storage->name . '/preview/' . $display_id . '/ajax',
        'wrapper' => 'views-preview-wrapper',
        'event' => 'click',
        'progress' => array('type' => 'throbber'),
        'method' => 'replace',
      ),
      // Make ENTER in arguments textfield (and other controls) submit the form
      // as this button, not the Save button.
      // @todo This only works for JS users. To make this work for nojs users,
      //   we may need to split Preview into a separate form.
      '#process' => array_merge(array(array($this, 'processDefaultButton')), element_info_property('submit', '#process', array())),
    );
    $form['#action'] = url('admin/structure/views/view/' . $this->storage->name .'/preview/' . $display_id);

    return $form;
  }

  /**
   * Form constructor callback to reorder displays on a view
   */
  public function buildDisplaysReorderForm($form, &$form_state) {
    $display_id = $form_state['display_id'];

    $form['view'] = array('#type' => 'value', '#value' => $this);

    $form['#tree'] = TRUE;

    $count = count($this->storage->display);

    uasort($this->storage->display, array('static', 'sortPosition'));
    foreach ($this->storage->display as $display) {
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

    $form['#action'] = url('admin/structure/views/nojs/reorder-displays/' . $this->storage->name . '/' . $display_id);

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
    $displays = $this->storage->display;
    foreach ($displays as $display_id => $display) {
      // Don't touch the default !!!
      if ($display_id === 'default') {
        $this->storage->display[$display_id]['position'] = 0;
        continue;
      }
      if (isset($order[$display_id])) {
        $this->storage->display[$display_id]['position'] = $order[$display_id];
      }
      else {
        $this->storage->display[$display_id]['deleted'] = TRUE;
      }
    }

    // Sorting back the display array as the position is not enough
    uasort($this->storage->display, array('static', 'sortPosition'));

    // Store in cache
    views_ui_cache_set($this);
    $form_state['redirect'] = array('admin/structure/views/view/' . $this->storage->name . '/edit', array('fragment' => 'views-tab-default'));
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
    $types = static::viewsHandlerTypes();
    $section = $types[$type]['plural'];

    // Handle the override select.
    list($was_defaulted, $is_defaulted) = $this->getOverrideValues($form, $form_state);
    if ($was_defaulted && !$is_defaulted) {
      // We were using the default display's values, but we're now overriding
      // the default display and saving values specific to this display.
      $display = &$this->displayHandlers[$form_state['display_id']];
      // setOverride toggles the override of this section.
      $display->setOverride($section);
    }
    elseif (!$was_defaulted && $is_defaulted) {
      // We used to have an override for this display, but the user now wants
      // to go back to the default display.
      // Overwrite the default display with the current form values, and make
      // the current display use the new default values.
      $display = &$this->displayHandlers[$form_state['display_id']];
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
        $id = $this->addItem($form_state['display_id'], $type, $table, $field);

        // check to see if we have group by settings
        $key = $type;
        // Footer,header and empty text have a different internal handler type(area).
        if (isset($types[$type]['type'])) {
          $key = $types[$type]['type'];
        }
        $handler = views_get_handler($table, $field, $key);
        if ($this->display_handler->useGroupBy() && $handler->usesGroupBy()) {
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

    $errors = $this->validate();
    if ($errors === TRUE) {
      $this->ajax = TRUE;
      $this->live_preview = TRUE;
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

      $this->setExposedInput($exposed_input);

      if (!$this->setDisplay($display_id)) {
        return t('Invalid display id @display', array('@display' => $display_id));
      }

      $this->setArguments($args);

      // Store the current view URL for later use:
      if ($this->display_handler->getOption('path')) {
        $path = $this->getUrl();
      }

      // Make view links come back to preview.
      $this->override_path = 'admin/structure/views/nojs/preview/' . $this->storage->name . '/' . $display_id;

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
      $preview = $this->preview($display_id, $args);
      views_ui_contextual_links_suppress_pop();

      // Reset variables.
      unset($this->override_path);
      _current_path($original_path);

      // Prepare the query information and statistics to show either above or
      // below the view preview.
      if ($show_info || $show_query || $show_stats) {
        // Get information from the preview for display.
        if (!empty($this->build_info['query'])) {
          if ($show_query) {
            $query = $this->build_info['query'];
            // Only the sql default class has a method getArguments.
            $quoted = array();

            if (get_class($this->query) == 'views_plugin_query_default') {
              $quoted = $query->getArguments();
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
            $rows['query'][] = array('<strong>' . t('Query') . '</strong>', '<pre>' . check_plain(strtr($query, $quoted)) . '</pre>');
            if (!empty($this->additional_queries)) {
              $queries = '<strong>' . t('These queries were run during view rendering:') . '</strong>';
              foreach ($this->additional_queries as $query) {
                if ($queries) {
                  $queries .= "\n";
                }
                $queries .= t('[@time ms]', array('@time' => intval($query[1] * 100000) / 100)) . ' ' . $query[0];
              }

              $rows['query'][] = array('<strong>' . t('Other queries') . '</strong>', '<pre>' . $queries . '</pre>');
            }
          }
          if ($show_info) {
            $rows['query'][] = array('<strong>' . t('Title') . '</strong>', filter_xss_admin($this->getTitle()));
            if (isset($path)) {
              $path = l($path, $path);
            }
            else {
              $path = t('This display has no path.');
            }
            $rows['query'][] = array('<strong>' . t('Path') . '</strong>', $path);
          }

          if ($show_stats) {
            $rows['statistics'][] = array('<strong>' . t('Query build time') . '</strong>', t('@time ms', array('@time' => intval($this->build_time * 100000) / 100)));
            $rows['statistics'][] = array('<strong>' . t('Query execute time') . '</strong>', t('@time ms', array('@time' => intval($this->execute_time * 100000) / 100)));
            $rows['statistics'][] = array('<strong>' . t('View render time') . '</strong>', t('@time ms', array('@time' => intval($this->render_time * 100000) / 100)));

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
   * Recursively adds microweights to a render array, similar to what form_builder() does for forms.
   *
   * @todo Submit a core patch to fix drupal_render() to do this, so that all
   *   render arrays automatically preserve array insertion order, as forms do.
   */
  public static function addMicroweights(&$build) {
    $count = 0;
    foreach (element_children($build) as $key) {
      if (!isset($build[$key]['#weight'])) {
        $build[$key]['#weight'] = $count/1000;
      }
      static::addMicroweights($build[$key]);
      $count++;
    }
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
    $identifier = implode('-', array($key, $this->storage->name, $display_id));

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
   * #process callback for a button; makes implicit form submissions trigger as this button.
   *
   * @see Drupal.behaviors.viewsImplicitFormSubmission
   */
  public static function processDefaultButton($element, &$form_state, $form) {
    $setting['viewsImplicitFormSubmission'][$form['#id']]['defaultButton'] = $element['#id'];
    $element['#attached']['js'][] = array('type' => 'setting', 'data' => $setting);
    return $element;
  }

}
