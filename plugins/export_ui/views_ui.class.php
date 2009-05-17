<?php

/**
 * @file
 * Contains the CTools Export UI integration code.
 *
 * Note that this is only a partial integration.
 */

/**
 * CTools Export UI class handler for Views UI.
 */
class views_ui extends ctools_export_ui {

  function init($plugin) {
    // We modify the plugin info here so that we take the defaults and
    // twiddle, rather than completely override them.

    // Reset the edit path to match what we're really using.
    $plugin['menu']['items']['edit']['path'] = 'view/%ctools_export_ui/edit';
    $plugin['menu']['items']['clone']['path'] = 'view/%ctools_export_ui/clone';
    $plugin['menu']['items']['clone']['type'] = MENU_VISIBLE_IN_BREADCRUMB;
    $plugin['menu']['items']['export']['path'] = 'view/%ctools_export_ui/export';
    $plugin['menu']['items']['export']['type'] = MENU_VISIBLE_IN_BREADCRUMB;
    $plugin['menu']['items']['enable']['path'] = 'view/%ctools_export_ui/enable';
    $plugin['menu']['items']['disable']['path'] = 'view/%ctools_export_ui/disable';
    $plugin['menu']['items']['delete']['path'] = 'view/%ctools_export_ui/delete';
    $plugin['menu']['items']['delete']['type'] = MENU_VISIBLE_IN_BREADCRUMB;
    $plugin['menu']['items']['revert']['path'] = 'view/%ctools_export_ui/revert';
    $plugin['menu']['items']['revert']['type'] = MENU_VISIBLE_IN_BREADCRUMB;

    $prefix_count = count(explode('/', $plugin['menu']['menu prefix']));
    $plugin['menu']['items']['add-template'] = array(
      'path' => 'template/%/add',
      'title' => 'Add from template',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'add_template', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'add_template', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );

    return parent::init($plugin);
  }

  function hook_menu(&$items) {
    // We are using our own 'edit' still, rather than having edit on this
    // object (maybe in the future) so unset the edit callbacks:

    // Store this so we can put them back as sometimes they're needed
    // again laster:
    $stored_items = $this->plugin['menu']['items'];
    // We leave these to make sure the operations still exist in the plugin so
    // that the path finder.
    unset($this->plugin['menu']['items']['edit']);
    unset($this->plugin['menu']['items']['add']);
    unset($this->plugin['menu']['items']['import']);
    unset($this->plugin['menu']['items']['edit callback']);

    parent::hook_menu($items);

    $this->plugin['menu']['items'] = $stored_items;
  }

  function load_item($item_name) {
    return views_ui_cache_load($item_name);
  }

  function list_form(&$form, &$form_state) {
    $row_class = 'container-inline';
    if (!variable_get('views_ui_show_listing_filters', FALSE)) {
      $row_class .= " element-invisible";
    }

    views_include('admin');

    parent::list_form($form, $form_state);

    // ctools only has two rows. We want four.
    // That's why we create our own structure.
    $form['bottom row']['submit']['#attributes']['class'][] = 'js-hide';
    $form['first row'] = array(
      '#prefix' => '<div class="' . $row_class . ' ctools-export-ui-row ctools-export-ui-first-row clearfix">',
      '#suffix' => '</div>',
      'search' => $form['top row']['search'],
      'submit' => $form['bottom row']['submit'],
      'reset' => $form['bottom row']['reset'],
    );
    $form['second row'] = array(
      '#prefix' => '<div class="' . $row_class . ' ctools-export-ui-row ctools-export-ui-second-row clearfix">',
      '#suffix' => '</div>',
      'storage' => $form['top row']['storage'],
      'disabled' => $form['top row']['disabled'],
    );
    $form['third row'] = array(
      '#prefix' => '<div class="' . $row_class . ' ctools-export-ui-row ctools-export-ui-third-row clearfix element-hidden">',
      '#suffix' => '</div>',
      'order' => $form['bottom row']['order'],
      'sort' => $form['bottom row']['sort'],
    );
    unset($form['top row']);
    unset($form['bottom row']);

    // Modify the look and contents of existing form elements.
    $form['second row']['storage']['#title'] = '';
    $form['second row']['storage']['#options'] = array(
      'all' => t('All storage'),
      t('Normal') => t('In database'),
      t('Default') => t('In code'),
      t('Overridden') => t('Database overriding code'),
    );
    $form['second row']['disabled']['#title'] = '';
    $form['second row']['disabled']['#options']['all'] = t('All status');
    $form['third row']['sort']['#title'] = '';

    // And finally, add our own.
    $this->bases = array();
    foreach (views_fetch_base_tables() as $table => $info) {
      $this->bases[$table] = $info['title'];
    }

    $form['second row']['base'] = array(
      '#type' => 'select',
      '#options' => array_merge(array('all' => t('All types')), $this->bases),
      '#default_value' => 'all',
      '#weight' => -1,
    );

    $tags = array();
    if (isset($form_state['object']->items)) {
      foreach ($form_state['object']->items as $name => $view) {
        if (!empty($view->tag)) {
          $view_tags = drupal_explode_tags($view->tag);
          foreach ($view_tags as $tag) {
            $tags[$tag] = $tag;
          }
        }
      }
    }
    asort($tags);

    $form['second row']['tag'] = array(
      '#type' => 'select',
      '#title' => t('Filter'),
      '#options' => array_merge(array('all' => t('All tags')), array('none' => t('No tags')), $tags),
      '#default_value' => 'all',
      '#weight' => -9,
    );

    $displays = array();
    foreach (views_fetch_plugin_data('display') as $id => $info) {
      if (!empty($info['admin'])) {
        $displays[$id] = $info['admin'];
      }
    }
    asort($displays);

    $form['second row']['display'] = array(
      '#type' => 'select',
      '#options' => array_merge(array('all' => t('All displays')), $displays),
      '#default_value' => 'all',
      '#weight' => -1,
    );
  }

  function list_filter($form_state, $view) {
    // Don't filter by tags if all is set up.
    if ($form_state['values']['tag'] != 'all') {
      // If none is selected check whether the view has a tag.
      if ($form_state['values']['tag'] == 'none') {
        return !empty($view->tag);
      }
      else {
        // Check whether the tag can be found in the views tag.
        return strpos($view->tag, $form_state['values']['tag']) === FALSE;
      }
    }
    if ($form_state['values']['base'] != 'all' && $form_state['values']['base'] != $view->base_table) {
      return TRUE;
    }

    return parent::list_filter($form_state, $view);
  }

  function list_sort_options() {
    return array(
      'disabled' => t('Enabled, name'),
      'name' => t('Name'),
      'path' => t('Path'),
      'tag' => t('Tag'),
      'storage' => t('Storage'),
    );
  }


  function list_build_row($view, &$form_state, $operations) {
    if (!empty($view->human_name)) {
      $title = $view->human_name;
    }
    else {
      $title = $view->get_title();
      if (empty($title)) {
        $title = $view->name;
      }
    }

    $paths = _views_ui_get_paths($view);
    $paths = implode(", ", $paths);

    $base = !empty($this->bases[$view->base_table]) ? $this->bases[$view->base_table] : t('Broken');

    $info = theme('views_ui_view_info', array('view' => $view, 'base' => $base));

    // Reorder the operations so that enable is the default action for a templatic views
    if (!empty($operations['enable'])) {
      $operations = array('enable' => $operations['enable']) + $operations;
    }

    // Set up sorting
    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$view->name] = strtolower(empty($view->disabled) . $title);
        break;
      case 'name':
        $this->sorts[$view->name] = strtolower($title);
        break;
      case 'path':
        $this->sorts[$view->name] = strtolower($paths);
        break;
      case 'tag':
        $this->sorts[$view->name] = strtolower($view->tag);
        break;
      case 'storage':
        $this->sorts[$view->name] = strtolower($view->type . $title);
        break;
    }

    $ops = theme('links__ctools_dropbutton', array('links' => $operations, 'attributes' => array('class' => array('links', 'inline'))));

    $this->rows[$view->name] = array(
      'data' => array(
        array('data' => $info, 'class' => array('views-ui-name')),
        array('data' => check_plain($view->description), 'class' => array('views-ui-description')),
        array('data' => check_plain($view->tag), 'class' => array('views-ui-tag')),
        array('data' => $paths, 'class' => array('views-ui-path')),
        array('data' => $ops, 'class' => array('views-ui-operations')),
      ),
      'title' => t('Machine name: ') . check_plain($view->name),
      'class' => array(!empty($view->disabled) ? 'ctools-export-ui-disabled' : 'ctools-export-ui-enabled'),
    );
  }

  function list_render(&$form_state) {
    views_include('admin');
    views_ui_add_admin_css();
    if (empty($_REQUEST['js'])) {
      views_ui_check_advanced_help();
    }
    drupal_add_library('system', 'jquery.bbq');
    views_add_js('views-list');

    $this->active = $form_state['values']['order'];
    $this->order = $form_state['values']['sort'];

    $query    = tablesort_get_query_parameters();

    $header = array(
      $this->tablesort_link(t('View name'), 'name', 'views-ui-name'),
      array('data' => t('Description'), 'class' => array('views-ui-description')),
      $this->tablesort_link(t('Tag'), 'tag', 'views-ui-tag'),
      $this->tablesort_link(t('Path'), 'path', 'views-ui-path'),
      array('data' => t('Operations'), 'class' => array('views-ui-operations')),
    );

    $table = array(
      'header' => $header,
      'rows' => $this->rows,
      'empty' => t('No views match the search criteria.'),
      'attributes' => array('id' => 'ctools-export-ui-list-items'),
    );
    return theme('table', $table);
  }

  function tablesort_link($label, $field, $class) {
    $title = t('sort by @s', array('@s' => $label));
    $initial = 'asc';

    if ($this->active == $field) {
      $initial = ($this->order == 'asc') ? 'desc' : 'asc';
      $label .= theme('tablesort_indicator', array('style' => $initial));
    }

    $query['order'] = $field;
    $query['sort'] = $initial;
    $link_options = array(
      'html' => TRUE,
      'attributes' => array('title' => $title),
      'query' => $query,
    );
    $link = l($label, $_GET['q'], $link_options);
    if ($this->active == $field) {
      $class .= ' active';
    }

    return array('data' => $link, 'class' => $class);
  }

  function clone_page($js, $input, $item, $step = NULL) {
    drupal_set_title($this->get_page_title('clone', $item));

    $name = $item->{$this->plugin['export']['key']};

    $form_state = array(
      'plugin' => $this->plugin,
      'object' => &$this,
      'ajax' => $js,
      'item' => $item,
      'op' => 'add',
      'form type' => 'clone',
      'original name' => $name,
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'step' => $step,
      // Store these in case additional args are needed.
      'function args' => func_get_args(),
    );

    $output = drupal_build_form('views_ui_clone_form', $form_state);
    if (!empty($form_state['executed'])) {
      $item->name = $form_state['values']['name'];
      $item->human_name = $form_state['values']['human_name'];
      $item->vid = NULL;
      views_ui_cache_set($item);

      drupal_goto(ctools_export_ui_plugin_menu_path($this->plugin, 'edit', $item->name));
    }

    return $output;
  }

  function add_template_page($js, $input, $name, $step = NULL) {
    $templates = views_get_all_templates();

    if (empty($templates[$name])) {
      return MENU_NOT_FOUND;
    }

    $template = $templates[$name];

    // The template description probably describes the template, not the
    // view that will be created from it, but users aren't that likely to
    // touch it.
    if (!empty($template->description)) {
      unset($template->description);
    }

    $template->is_template = TRUE;
    $template->type = t('Default');

    $output = $this->clone_page($js, $input, $template, $step);
    drupal_set_title(t('Create view from template @template', array('@template' => $template->get_human_name())));
    return $output;
  }

  function set_item_state($state, $js, $input, $item) {
    ctools_export_set_object_status($item, $state);
    menu_rebuild();

    if (!$js) {
      drupal_goto(ctools_export_ui_plugin_base_path($this->plugin));
    }
    else {
      return $this->list_page($js, $input);
    }
  }

  function list_page($js, $input) {
    // wrap output in a div for CSS
    $output = parent::list_page($js, $input);
    if (is_string($output)) {
      $output = '<div id="views-ui-list-page">' . $output . '</div>';
      return $output;
    }
  }
}

/**
 * Form callback to edit an exportable item using the wizard
 *
 * This simply loads the object defined in the plugin and hands it off.
 */
function views_ui_clone_form($form, &$form_state) {
  $counter = 1;

  if (!isset($form_state['item'])) {
    $view = views_get_view($form_state['original name']);
  }
  else {
    $view = $form_state['item'];
  }
  do {
    if (empty($form_state['item']->is_template)) {
      $name = format_plural($counter, 'Clone of', 'Clone @count of') . ' ' . $view->get_human_name();
    }
    else {
      $name = $view->get_human_name();
      if ($counter > 1) {
        $name .= ' ' . $counter;
      }
    }
    $counter++;
    $machine_name = preg_replace('/[^a-z0-9_]+/', '_', drupal_strtolower($name));
  } while (ctools_export_crud_load($form_state['plugin']['schema'], $machine_name));

  $form['human_name'] = array(
    '#type' => 'textfield',
    '#title' => t('View name'),
    '#default_value' => $name,
    '#size' => 32,
    '#maxlength' => 255,
  );

  $form['name'] = array(
    '#title' => t('View name'),
    '#type' => 'machine_name',
    '#required' => TRUE,
    '#maxlength' => 128,
    '#size' => 128,
    '#machine_name' => array(
      'exists' => 'ctools_export_ui_edit_name_exists',
      'source' => array('human_name'),
    ),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Continue'),
  );

  return $form;
}
