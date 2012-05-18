<?php

/**
 * @file
 * Contains the page display plugin.
 */

/**
 * The plugin that handles a full page.
 *
 * @ingroup views_display_plugins
 */
class views_plugin_display_page extends views_plugin_display {
  /**
   * The page display has a path.
   */
  function has_path() { return TRUE; }
  function uses_breadcrumb() { return TRUE; }

  function option_definition() {
    $options = parent::option_definition();

    $options['path'] = array('default' => '');
    $options['menu'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'name' => array('default' => variable_get('menu_default_node_menu', 'navigation')),
        'context' => array('default' => ''),
       ),
    );
    $options['tab_options'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'name' => array('default' => 'navigation'),
       ),
    );

    return $options;
  }

  /**
   * Add this display's path information to Drupal's menu system.
   */
  function execute_hook_menu($callbacks) {
    $items = array();
    // Replace % with the link to our standard views argument loader
    // views_arg_load -- which lives in views.module

    $bits = explode('/', $this->get_option('path'));
    $page_arguments = array($this->view->name, $this->display->id);
    $this->view->init_handlers();
    $view_arguments = $this->view->argument;

    // Replace % with %views_arg for menu autoloading and add to the
    // page arguments so the argument actually comes through.
    foreach ($bits as $pos => $bit) {
      if ($bit == '%') {
        $argument = array_shift($view_arguments);
        if (!empty($argument->options['specify_validation']) && $argument->options['validate']['type'] != 'none') {
          $bits[$pos] = '%views_arg';
        }
        $page_arguments[] = $pos;
      }
    }

    $path = implode('/', $bits);

    $access_plugin = $this->get_plugin('access');
    if (!isset($access_plugin)) {
      $access_plugin = views_get_plugin('access', 'none');
    }

    // Get access callback might return an array of the callback + the dynamic arguments.
    $access_plugin_callback = $access_plugin->get_access_callback();

    if (is_array($access_plugin_callback)) {
      $access_arguments = array();

      // Find the plugin arguments.
      $access_plugin_method = array_shift($access_plugin_callback);
      $access_plugin_arguments = array_shift($access_plugin_callback);
      if (!is_array($access_plugin_arguments)) {
        $access_plugin_arguments = array();
      }

      $access_arguments[0] = array($access_plugin_method, &$access_plugin_arguments);

      // Move the plugin arguments to the access arguments array.
      $i = 1;
      foreach ($access_plugin_arguments as $key => $value) {
        if (is_int($value)) {
          $access_arguments[$i] = $value;
          $access_plugin_arguments[$key] = $i;
          $i++;
        }
      }
    }
    else {
      $access_arguments = array($access_plugin_callback);
    }

    if ($path) {
      $items[$path] = array(
        // default views page entry
        'page callback' => 'views_page',
        'page arguments' => $page_arguments,
        // Default access check (per display)
        'access callback' => 'views_access',
        'access arguments' => $access_arguments,
        // Identify URL embedded arguments and correlate them to a handler
        'load arguments'  => array($this->view->name, $this->display->id, '%index'),
      );
      $menu = $this->get_option('menu');
      if (empty($menu)) {
        $menu = array('type' => 'none');
      }
      // Set the title and description if we have one.
      if ($menu['type'] != 'none') {
        $items[$path]['title'] = $menu['title'];
        $items[$path]['description'] = $menu['description'];
      }

      if (isset($menu['weight'])) {
        $items[$path]['weight'] = intval($menu['weight']);
      }

      switch ($menu['type']) {
        case 'none':
        default:
          $items[$path]['type'] = MENU_CALLBACK;
          break;
        case 'normal':
          $items[$path]['type'] = MENU_NORMAL_ITEM;
          // Insert item into the proper menu
          $items[$path]['menu_name'] = $menu['name'];
          break;
        case 'tab':
          $items[$path]['type'] = MENU_LOCAL_TASK;
          break;
        case 'default tab':
          $items[$path]['type'] = MENU_DEFAULT_LOCAL_TASK;
          break;
      }

      // Add context for contextual links.
      // @see menu_contextual_links()
      if (!empty($menu['context'])) {
        $items[$path]['context'] = MENU_CONTEXT_INLINE;
      }

      // If this is a 'default' tab, check to see if we have to create teh
      // parent menu item.
      if ($menu['type'] == 'default tab') {
        $tab_options = $this->get_option('tab_options');
        if (!empty($tab_options['type']) && $tab_options['type'] != 'none') {
          $bits = explode('/', $path);
          // Remove the last piece.
          $bit = array_pop($bits);

          // we can't do this if they tried to make the last path bit variable.
          // @todo: We can validate this.
          if ($bit != '%views_arg' && !empty($bits)) {
            $default_path = implode('/', $bits);
            $items[$default_path] = array(
              // default views page entry
              'page callback' => 'views_page',
              'page arguments' => $page_arguments,
              // Default access check (per display)
              'access callback' => 'views_access',
              'access arguments' => $access_arguments,
              // Identify URL embedded arguments and correlate them to a handler
              'load arguments'  => array($this->view->name, $this->display->id, '%index'),
              'title' => $tab_options['title'],
              'description' => $tab_options['description'],
              'menu_name' => $tab_options['name'],
            );
            switch ($tab_options['type']) {
              default:
              case 'normal':
                $items[$default_path]['type'] = MENU_NORMAL_ITEM;
                break;
              case 'tab':
                $items[$default_path]['type'] = MENU_LOCAL_TASK;
                break;
            }
            if (isset($tab_options['weight'])) {
              $items[$default_path]['weight'] = intval($tab_options['weight']);
            }
          }
        }
      }
    }

    return $items;
  }

  /**
   * The display page handler returns a normal view, but it also does
   * a drupal_set_title for the page, and does a views_set_page_view
   * on the view.
   */
  function execute() {
    // Let the world know that this is the page view we're using.
    views_set_page_view($this->view);

    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $this->view->build();
    if (!empty($this->view->build_info['fail'])) {
      return drupal_not_found();
    }

    if (!empty($this->view->build_info['denied'])) {
      return drupal_access_denied();
    }

    $this->view->get_breadcrumb(TRUE);


    // And now render the view.
    $render = $this->view->render();

    // First execute the view so it's possible to get tokens for the title.
    // And the title, which is much easier.
    drupal_set_title(filter_xss_admin($this->view->get_title()), PASS_THROUGH);
    return $render;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  function options_summary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::options_summary($categories, $options);

    $categories['page'] = array(
      'title' => t('Page settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $path = strip_tags('/' . $this->get_option('path'));
    if (empty($path)) {
      $path = t('None');
    }

    $options['path'] = array(
      'category' => 'page',
      'title' => t('Path'),
      'value' => views_ui_truncate($path, 24),
    );

    $menu = $this->get_option('menu');
    if (!is_array($menu)) {
      $menu = array('type' => 'none');
    }
    switch($menu['type']) {
      case 'none':
      default:
        $menu_str = t('No menu');
        break;
      case 'normal':
        $menu_str = t('Normal: @title', array('@title' => $menu['title']));
        break;
      case 'tab':
      case 'default tab':
        $menu_str = t('Tab: @title', array('@title' => $menu['title']));
        break;
    }

    $options['menu'] = array(
      'category' => 'page',
      'title' => t('Menu'),
      'value' => views_ui_truncate($menu_str, 24),
    );

    // This adds a 'Settings' link to the style_options setting if the style has options.
    if ($menu['type'] == 'default tab') {
      $options['menu']['setting'] = t('Parent menu item');
      $options['menu']['links']['tab_options'] = t('Change settings for the parent menu');
    }
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    // It is very important to call the parent function here:
    parent::options_form($form, $form_state);

    switch ($form_state['section']) {
      case 'path':
        $form['#title'] .= t('The menu path or URL of this view');
        $form['#help_topic'] = 'path';
        $form['path'] = array(
          '#type' => 'textfield',
          '#description' => t('This view will be displayed by visiting this path on your site. You may use "%" in your URL to represent values that will be used for contextual filters: For example, "node/%/feed".'),
          '#default_value' => $this->get_option('path'),
	  '#field_prefix' => '<span dir="ltr">' . url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
	  '#field_suffix' => '</span>&lrm;',
	  '#attributes' => array('dir'=>'ltr'),
        );
        break;
      case 'menu':
        $form['#title'] .= t('Menu item entry');
        $form['#help_topic'] = 'menu';
        $form['menu'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $menu = $this->get_option('menu');
        if (empty($menu)) {
          $menu = array('type' => 'none', 'title' => '', 'weight' => 0);
        }
        $form['menu']['type'] = array(
          '#prefix' => '<div class="views-left-30">',
          '#suffix' => '</div>',
          '#title' => t('Type'),
          '#type' => 'radios',
          '#options' => array(
            'none' => t('No menu entry'),
            'normal' => t('Normal menu entry'),
            'tab' => t('Menu tab'),
            'default tab' => t('Default menu tab')
          ),
          '#default_value' => $menu['type'],
        );
        $form['menu']['title'] = array(
          '#prefix' => '<div class="views-left-50">',
          '#title' => t('Title'),
          '#type' => 'textfield',
          '#default_value' => $menu['title'],
          '#description' => t('If set to normal or tab, enter the text to use for the menu item.'),
          '#dependency' => array('radio:menu[type]' => array('normal', 'tab', 'default tab')),
        );
        $form['menu']['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#default_value' => $menu['description'],
          '#description' => t("If set to normal or tab, enter the text to use for the menu item's description."),
          '#dependency' => array('radio:menu[type]' => array('normal', 'tab', 'default tab')),
        );

        // Only display the menu selector if menu module is enabled.
        if (module_exists('menu')) {
          $form['menu']['name'] = array(
            '#title' => t('Menu'),
            '#type' => 'select',
            '#options' => menu_get_menus(),
            '#default_value' => $menu['name'],
            '#description' => t('Insert item into an available menu.'),
            '#dependency' => array('radio:menu[type]' => array('normal', 'tab')),
          );
        }
        else {
          $form['menu']['name'] = array(
            '#type' => 'value',
            '#value' => $menu['name'],
          );
          $form['menu']['markup'] = array(
            '#markup' => t('Menu selection requires the activation of menu module.'),
          );
        }
        $form['menu']['weight'] = array(
          '#title' => t('Weight'),
          '#type' => 'textfield',
          '#default_value' => isset($menu['weight']) ? $menu['weight'] : 0,
          '#description' => t('The lower the weight the higher/further left it will appear.'),
          '#dependency' => array('radio:menu[type]' => array('normal', 'tab', 'default tab')),
        );
        $form['menu']['context'] = array(
          '#title' => t('Context'),
          '#suffix' => '</div>',
          '#type' => 'checkbox',
          '#default_value' => !empty($menu['context']),
          '#description' => t('Displays the link in contextual links'),
          '#dependency' => array('radio:menu[type]' => array('tab')),
        );
        break;
      case 'tab_options':
        $form['#title'] .= t('Default tab options');
        $tab_options = $this->get_option('tab_options');
        if (empty($tab_options)) {
          $tab_options = array('type' => 'none', 'title' => '', 'weight' => 0);
        }

        $form['tab_markup'] = array(
          '#markup' => '<div class="form-item description">' . t('When providing a menu item as a tab, Drupal needs to know what the parent menu item of that tab will be. Sometimes the parent will already exist, but other times you will need to have one created. The path of a parent item will always be the same path with the last part left off. i.e, if the path to this view is <em>foo/bar/baz</em>, the parent path would be <em>foo/bar</em>.') . '</div>',
        );

        $form['tab_options'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $form['tab_options']['type'] = array(
          '#prefix' => '<div class="views-left-25">',
          '#suffix' => '</div>',
          '#title' => t('Parent menu item'),
          '#type' => 'radios',
          '#options' => array('none' => t('Already exists'), 'normal' => t('Normal menu item'), 'tab' => t('Menu tab')),
          '#default_value' => $tab_options['type'],
        );
        $form['tab_options']['title'] = array(
          '#prefix' => '<div class="views-left-75">',
          '#title' => t('Title'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['title'],
          '#description' => t('If creating a parent menu item, enter the title of the item.'),
          '#dependency' => array('radio:tab_options[type]' => array('normal', 'tab')),
        );
        $form['tab_options']['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['description'],
          '#description' => t('If creating a parent menu item, enter the description of the item.'),
          '#dependency' => array('radio:tab_options[type]' => array('normal', 'tab')),
        );
        // Only display the menu selector if menu module is enabled.
        if (module_exists('menu')) {
          $form['tab_options']['name'] = array(
            '#title' => t('Menu'),
            '#type' => 'select',
            '#options' => menu_get_menus(),
            '#default_value' => $tab_options['name'],
            '#description' => t('Insert item into an available menu.'),
            '#dependency' => array('radio:tab_options[type]' => array('normal')),
          );
        }
        else {
          $form['tab_options']['name'] = array(
            '#type' => 'value',
            '#value' => $tab_options['name'],
          );
          $form['tab_options']['markup'] = array(
            '#markup' => t('Menu selection requires the activation of menu module.'),
          );
        }
        $form['tab_options']['weight'] = array(
          '#suffix' => '</div>',
          '#title' => t('Tab weight'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['weight'],
          '#size' => 5,
          '#description' => t('If the parent menu item is a tab, enter the weight of the tab. The lower the number, the more to the left it will be.'),
          '#dependency' => array('radio:tab_options[type]' => array('tab')),
        );
        break;
    }
  }

  function options_validate(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_validate($form, $form_state);
    switch ($form_state['section']) {
      case 'path':
        if (strpos($form_state['values']['path'], '$arg') !== FALSE) {
          form_error($form['path'], t('"$arg" is no longer supported. Use % instead.'));
        }

        if (strpos($form_state['values']['path'], '%') === 0) {
          form_error($form['path'], t('"%" may not be used for the first segment of a path.'));
        }

        // automatically remove '/' from path.
        $form_state['values']['path'] = trim($form_state['values']['path'], '/');

        break;
      case 'menu':
        $path = $this->get_option('path');
        if ($form_state['values']['menu']['type'] == 'normal' && strpos($path, '%') !== FALSE) {
          form_error($form['menu']['type'], t('Views cannot create normal menu items for paths with a % in them.'));
        }

        if ($form_state['values']['menu']['type'] == 'default tab' || $form_state['values']['menu']['type'] == 'tab') {
          $bits = explode('/', $path);
          $last = array_pop($bits);
          if ($last == '%') {
            form_error($form['menu']['type'], t('A display whose path ends with a % cannot be a tab.'));
          }
        }

        if ($form_state['values']['menu']['type'] != 'none' && empty($form_state['values']['menu']['title'])) {
          form_error($form['menu']['title'], t('Title is required for this menu type.'));
        }
        break;
    }
  }

  function options_submit(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_submit($form, $form_state);
    switch ($form_state['section']) {
      case 'path':
        $this->set_option('path', $form_state['values']['path']);
        break;
      case 'menu':
        $this->set_option('menu', $form_state['values']['menu']);
        // send ajax form to options page if we use it.
        if ($form_state['values']['menu']['type'] == 'default tab') {
          views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('tab_options'));
        }
        break;
      case 'tab_options':
        $this->set_option('tab_options', $form_state['values']['tab_options']);
        break;
    }
  }

  function validate() {
    $errors = parent::validate();

    $menu = $this->get_option('menu');
    if (!empty($menu['type']) && $menu['type'] != 'none' && empty($menu['title'])) {
      $errors[] = t('Display @display is set to use a menu but the menu link text is not set.', array('@display' => $this->display->display_title));
    }

    if ($menu['type'] == 'default tab') {
      $tab_options = $this->get_option('tab_options');
      if (!empty($tab_options['type']) && $tab_options['type'] != 'none' && empty($tab_options['title'])) {
        $errors[] = t('Display @display is set to use a parent menu but the parent menu link text is not set.', array('@display' => $this->display->display_title));
      }
    }

    return $errors;
  }

  function get_argument_text() {
    return array(
      'filter value not present' => t('When the filter value is <em>NOT</em> in the URL'),
      'filter value present' => t('When the filter value <em>IS</em> in the URL or a default is provided'),
      'description' => t('The contextual filter values is provided by the URL.'),
    );
  }

  function get_pager_text() {
    return array(
      'items per page title' => t('Items per page'),
      'items per page description' => t('The number of items to display per page. Enter 0 for no limit.')
    );
  }
}
