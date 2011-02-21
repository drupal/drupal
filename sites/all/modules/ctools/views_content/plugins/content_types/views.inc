<?php
// $Id: views.inc,v 1.17 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Content type plugin to expose all views as content.
 */

if (variable_get('ctools_content_all_views', TRUE)) {
  $plugin = array(
    'title' => t('All views'),
    'defaults' => array(
      'override_pager_settings' => FALSE,
      'use_pager' => FALSE,
      'nodes_per_page' => 10,
      'pager_id' => 0,
      'offset' => 0,
      'more_link' => FALSE,
      'feed_icons' => FALSE,
      'panel_args' => FALSE,
      'link_to_view' => FALSE,
      'args' => '',
      'url' => '',
    ),
    'add form' => array(
      'views_content_views_select_display' => t('Select display'),
      'views_content_views_content_type_edit_form' => array(
        'default' => TRUE, // put wrapper here, not on the previous form.
        'title' => t('Configure view'),
      ),
    ),
    'all contexts' => TRUE,
  );
}

/**
 * Return all content types available.
 */
function views_content_views_content_type_content_types($plugin) {
  $types = array();
  // It can be fairly intensive to calculate this, so let's cache this in the
  // cache_views table. The nice thing there is that if views ever change, that
  // table will always be cleared. Except for the occasional default view, so
  // we must use the Views caching functions in order to respect Views caching
  // settings.
  views_include('cache');
  $data = views_cache_get('views_content_all', TRUE);
  if (!empty($data->data)) {
    $types = $data->data;
  }

  if (empty($types)) {
    $views = views_get_all_views();

    foreach ($views as $view) {
      if (empty($view->disabled)) {
        $types[$view->name] = _views_content_views_content_type($view);
      }
    }

    views_cache_set('views_content_all', $types, TRUE);
  }

  return $types;
}

/**
 * Return a single content type.
 */
function views_content_views_content_type_content_type($subtype, $plugin) {
  $view = views_get_view($name);
  if (empty($view)) {
    return;
  }

  return _views_content_views_content_type($view);
}

/**
 * Create the content type info array to give back to ctools for a given display.
 */
function _views_content_views_content_type($view) {
  $title = $view->name;

  $icon = 'icon_views_page_legacy.png';

  return array(
    'view' => $view->name,
    'title' => $title,
    'icon' => $icon,
    'description' => filter_xss_admin($view->description),
    'category' => t('Views'),
  );

}

/**
 * Output function for the 'views' content type.
 *
 * Outputs a view based on the module and delta supplied in the configuration.
 */
function views_content_views_content_type_render($subtype, $conf, $panel_args, $contexts) {
  if (!is_array($contexts)) {
    $contexts = array($contexts);
  }

  $view = _views_content_views_update_conf($conf, $subtype);

  if (empty($view) || !is_object($view) || empty($view->display_handler)) {
    return;
  }

  if (!$view->display_handler->access($GLOBALS['user'])) {
    return;
  }

  $arguments = explode('/', $_GET['q']);
  $args = $conf['args'];

  foreach ($arguments as $id => $arg) {
    $args = str_replace("%$id", $arg, $args);
  }

  foreach ($panel_args as $id => $arg) {
    if (is_string($arg)) {
      $args = str_replace("@$id", $arg, $args);
    }
  }

  $args = preg_replace(',/?(%\d|@\d),', '', $args);
  $args = $args ? explode('/', $args) : array();

  if ($conf['panel_args'] && is_array($panel_args)) {
    $args = array_merge($panel_args, $args);
  }

  if (isset($conf['context']) && is_array($conf['context'])) {
    foreach ($conf['context'] as $count => $context_info) {
      if (!strpos($context_info, '.')) {
        // old skool: support pre-converter contexts as well.
        $cid = $context_info;
        $converter = '';
      }
      else {
        list($cid, $converter) = explode('.', $context_info, 2);
      }
      if (!empty($contexts[$cid])) {
        $arg = ctools_context_convert_context($contexts[$cid], $converter);
        array_splice($args, $count, 0, array($arg));
      }
    }
  }

  $view->set_arguments($args);

  if ($conf['url']) {
    $view->override_path = $conf['url'];
  }

  $block = new stdClass();
  $block->module = 'views';
  $block->delta  = $view->name .'-'.  $view->current_display;

  if (!empty($conf['link_to_view'])) {
    $block->title_link = $view->get_url();
  }

  if (!empty($conf['more_link'])) {
    $block->more = array('href' => $view->get_url());
    $view->display_handler->set_option('use_more', FALSE);
  }

  // Only set use_pager if they differ, this way we can avoid overwriting the
  // pager type that Views uses.
  if ($conf['override_pager_settings']) {
    if (method_exists($view, 'init_pager')) {
      // Views 3 version
      $view->set_items_per_page($conf['nodes_per_page']);
      $view->set_offset($conf['offset']);

      $pager = $view->display_handler->get_option('pager');
      if ($conf['use_pager'] && ($pager['type'] == 'none' || $pager['type'] == 'some')) {
        $pager['type'] = 'full';
      }
      elseif (!$conf['use_pager'] && $pager['type'] != 'none' && $pager['type'] != 'some') {
        $pager['type'] = $view->get_items_per_page() ? 'some' : 'none';
      }

      if ($conf['use_pager']) {
        if (!isset($pager['options']['id']) || $pager['options']['id'] != $conf['pager_id']) {
          $pager['options']['id'] = $conf['pager_id'];
        }
      }

      $view->display_handler->set_option('pager', $pager);
    }
    else {
      if (!$view->display_handler->get_option('use_pager') || empty($conf['use_pager'])) {
        $view->display_handler->set_option('use_pager', $conf['use_pager']);
      }
      $view->display_handler->set_option('pager_element', $conf['pager_id']);
      $view->display_handler->set_option('items_per_page', $conf['nodes_per_page']);
      $view->display_handler->set_option('offset', $conf['offset']);
    }
  }

  $stored_feeds = drupal_add_feed();
  $block->content = $view->preview();
  $block->title = $view->get_title();

  if (empty($view->result) && !$view->display_handler->get_option('empty') && empty($view->style_plugin->definition['even empty'])) {
    return;
  }

  if (!empty($conf['feed_icons'])) {
    $new_feeds = drupal_add_feed();
    if ($diff = array_diff(array_keys($new_feeds), array_keys($stored_feeds))) {
      foreach ($diff as $url) {
        $block->feeds[$url] = $new_feeds[$url];
      }
    }
  }

  $view->destroy();
  return $block;
}

/**
 * Returns an edit form for a block.
 */
function views_content_views_select_display($form, &$form_state) {
  $view = views_get_view($form_state['subtype_name']);
  if (empty($view)) {
    return;
  }

  $displays = array();
  foreach ($view->display as $id => $display) {
    // Content pane views should never be used this way.
    if ($display->display_plugin != 'panel_pane') {
      $displays[$id] = $display->display_title;
    }
  }

  $form['display'] = array(
    '#type' => 'select',
    '#title' => t('Display'),
    '#options' => $displays,
    '#description' => t('Choose which display of this view you wish to use.')
  );

  return $form;
}

/**
 * Submit the basic view edit form.
 *
 * This just dumps everything into the $conf array.
 */
function views_content_views_select_display_submit(&$form, &$form_state) {
  $form_state['conf']['display'] = $form_state['values']['display'];
}

/**
 * Returns an edit form for a block.
 */
function views_content_views_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];
  $view = _views_content_views_update_conf($conf, $form_state['subtype_name']);

  if (empty($view) || !is_object($view)) {
    $form['markup'] = array('#value' => t('Broken/missing/deleted view.'));
    return;
  }

  $form_state['title'] = t('Configure view @view (@display)', array('@view' => $view->name, '@display' => $view->display[$conf['display']]->display_title));

  // @todo
  // If using the older format, just a context is listed. We should go through
  // and check for that and forcibly set them to the right converter so that
  // it doesn't get changed to some whacky default. Oooor just let it get changed
  // to 'no context', I suppose.

  $required = array();
  if (isset($view->display_handler) && $arguments = $view->display_handler->get_handlers('argument')) {
    foreach ($arguments as $arg) {
      $required[] = new ctools_context_optional($arg->ui_name(), 'any');
    }
  }

  if ($required) {
    $form['context'] = ctools_context_converter_selector($form_state['contexts'], $required, isset($conf['context']) ? $conf['context'] : array());
  }

  $form['link_to_view'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['link_to_view'],
    '#title' => t('Link title to view'),
  );

  $form['more_link'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['more_link'],
    '#title' => t('Provide a "more" link that links to the view'),
    '#description' => t('This is independent of any more link that may be provided by the view itself; if you see two more links, turn this one off. Views will only provide a more link if using the "block" type, however, so if using embed, use this one.'),
  );

  $form['feed_icons'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['feed_icons'],
    '#title' => t('Display feed icons'),
  );

  $form['pager_settings'] = array(
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
    '#title' => t('Custom pager settings'),
  );

  $form['pager_settings']['override_pager_settings'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use different pager settings from view settings'),
    '#default_value' => $conf['override_pager_settings'],
    '#id' => 'override-pager-checkbox',
  );

  if ($view->display_handler->get_option('use_ajax')) {
    $form['pager_settings']['warning'] = array(
      '#value' => '<div>' . t('<strong>Warning: </strong> This view has AJAX enabled. Overriding the pager settings will work initially, but when the view is updated via AJAX, the original settings will be used. You should not override pager settings on Views with the AJAX setting enabled.') . '</div>',
    );
  }

  $form['pager_settings']['use_pager'] = array(
    '#prefix' => '<div class="container-inline">',
    '#type' => 'checkbox',
    '#title' => t('Use pager'),
    '#default_value' => $conf['use_pager'],
    '#id' => 'use-pager-checkbox',
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('override-pager-checkbox' => array(1)),
  );
  $form['pager_settings']['pager_id'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['pager_id'],
    '#title' => t('Pager ID'),
    '#size' => 4,
    '#id' => 'use-pager-textfield',
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('override-pager-checkbox' => array(1), 'use-pager-checkbox' => array(1)),
    '#dependency_count' => 2,
    '#suffix' => '</div>',
  );

  $form['pager_settings']['nodes_per_page'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['nodes_per_page'],
    '#size' => 4,
    '#title' => t('Num posts'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('override-pager-checkbox' => array(1)),
  );

  $form['pager_settings']['offset'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['offset'],
    '#title' => t('Offset'),
    '#size' => 4,
    '#description' => t('The number of items to skip and not display.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('override-pager-checkbox' => array(1)),
  );

  $form['panel_args'] = array(
    '#type' => 'checkbox',
    '#title' => t('Send arguments'),
    '#default_value' => $conf['panel_args'],
    '#description' => t('Select this to send all arguments from the panel directly to the view. If checked, the panel arguments will come after any context arguments above and precede any additional arguments passed in through the Arguments field below. Note that arguments do not include the base URL; only values after the URL or set as placeholders are considered arguments.'),
  );

  $form['args'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['args'],
    '#title' => t('Arguments'),
    '#size' => 30,
    '#description' => t('Additional arguments to send to the view as if they were part of the URL in the form of arg1/arg2/arg3. You may use %0, %1, ..., %N to grab arguments from the URL. Or use @0, @1, @2, ..., @N to use arguments passed into the panel. Note: use these values only as a last resort. In future versions of Panels these may go away.'),
  );

  $form['url'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['url'],
    '#title' => t('Override URL'),
    '#size' => 30,
    '#description' => t('If this is set, override the View URL; this can sometimes be useful to set to the panel URL'),
  );

  $view->destroy();
  return $form;
}

/**
 * Store form values in $conf.
 */
function views_content_views_content_type_edit_form_submit(&$form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function views_content_views_content_type_admin_title($subtype, $conf) {
  $view = _views_content_views_update_conf($conf, $subtype);

  if (!is_object($view)) {
    return t('Deleted/missing view @view', array('@view' => $view));
  }

  $title = $view->display[$view->current_display]->display_title;
  return t('View: @name', array('@name' => $view->name . '-' . $title));
}

/**
 * Returns the administrative title for a type.
 */
function views_content_views_content_type_admin_info($subtype, $conf, $contexts) {
  $view = _views_content_views_update_conf($conf, $subtype);

  if (!is_object($view)) {
    return t('Deleted/missing view @view', array('@view' => $view));
  }

  $display = empty($conf['display']) ? $view->current_display : $conf['display'];
  $block->title = t('View information');

  $block->content = '<ul>';
  $block->content .= '<li>' . t('Using display @display.', array('@display' => $view->display[$display]->display_title)) . '</li>';

  if (!empty($conf['context']) && $arguments = $view->display_handler->get_handlers('argument')) {
    $argument = reset($arguments);
    foreach ($conf['context'] as $count => $context_info) {
      if (!$argument) {
        break;
      }

      if (!strpos($context_info, '.')) {
        // old skool: support pre-converter contexts as well.
        $cid = $context_info;
        $converter = '';
      }
      else {
        list($cid, $converter) = explode('.', $context_info, 2);
      }

      if (!empty($contexts[$cid])) {
        $converters = ctools_context_get_converters($cid . '.', $contexts[$cid]);
        $converter = !empty($converters[$context_info]) ? $converters[$context_info] : t('Default');
        $block->content .= '<li>' . t('Argument @arg using context @context converted into @converter', array(
          '@arg' => $argument->ui_name(), '@context' => $contexts[$cid]->get_identifier(),
          '@converter' => $converter)) . '</li>';
      }
      $argument = next($arguments);
    }
  }

  $block->content .= '<li>' . t('@count items displayed.', array('@count' => $conf['nodes_per_page'])) . '</li>';
  if ($conf['use_pager']) {
    $block->content .= '<li>' . t('With pager.') . '</li>';
  }
  else {
    $block->content .= '<li>' . t('Without pager.') . '</li>';
  }

  if ($conf['offset']) {
    $block->content .= '<li>' . t('Skipping first @count results', array('@count' => $conf['offset'])) . '</li>';
  }
  if ($conf['more_link']) {
    $block->content .= '<li>' . t('With more link.') . '</li>';
  }
  if ($conf['feed_icons']) {
    $block->content .= '<li>' . t('With feed icon.') . '</li>';
  }
  if ($conf['panel_args']) {
    $block->content .= '<li>' . t('Sending arguments.') . '</li>';
  }
  if ($conf['args']) {
    $block->content .= '<li>' . t('Using arguments: @args', array('@args' => $conf['args'])) . '</li>';
  }
  if ($conf['url']) {
    $block->content .= '<li>' . t('Using url: @url', array('@url' => $conf['url'])) . '</li>';
  }

  $view->destroy();
  return $block;
}

/**
 * Update the $conf to deal with updates from Drupal 5.
 *
 * @param &$conf
 *   The $conf array to modify.
 * @param $subtype
 *   The subtype in use. This should just be the view name, but in older
 *   versions it was the view name with a dash and the display ID.
 *   If this is the case, we can use it to correct the 'display' setting
 *   in the $conf.
 * @return
 *   The $view with the initialized display. If the $view could not be
 *   loaded, the name attempted will be loaded for use in errors.
 *   Correct error checking on this function checks against is_object().
 */
function _views_content_views_update_conf(&$conf, $subtype) {
  $plugin = ctools_get_content_type('views');

  // Special: Existing content types get a different default than new ones:
  if (!empty($conf) && !isset($conf['override_pager_settings'])) {
    $conf['override_pager_settings'] = TRUE;
  }

  // Make sure that our defaults are always set if there is no
  // previous setting. This helps updates go more smoothly.
  foreach ($plugin['defaults'] as $key => $value) {
    if (!isset($conf[$key])) {
      $conf[$key] = $value;
    }
  }

  if (strpos($subtype, '-')) {
    list($name, $display) = explode('-', $subtype);
    $view = views_get_view($name);
    if (!isset($conf['display'])) {
      $conf['display'] = $display;
    }
  }
  else {
    $name = $subtype;
    $view = views_get_view($subtype);
    $display = isset($conf['display']) ? $conf['display'] : 'default';
  }

  if (empty($view)) {
    return $name;
  }

  $view->set_display($display);
  // $view->current_display will now reflect this value.

  // If set NOT to override, go ahead and refresh from the view.
  if (empty($conf['override_pager_settings'])) {
    if (method_exists($view, 'init_pager')) {
      $pager = $view->display_handler->get_option('pager');
      $conf['use_pager'] = $pager['type'] != 'none' && $pager['type'] != 'some';
      $conf['pager_id'] = isset($pager['options']['id']) ? $pager['options']['id'] : 0;
      $conf['offset'] = isset($pager['options']['offset']) ? $pager['options']['offset'] : 0;
      $conf['nodes_per_page'] = isset($pager['options']['items_per_page']) ? $pager['options']['items_per_page'] : 0;
    }
    else {
      $conf['use_pager'] = $view->display_handler->get_option('use_pager');
      $conf['pager_id'] = $view->display_handler->get_option('element_id');
      $conf['nodes_per_page'] = $view->display_handler->get_option('items_per_page');
      $conf['offset'] = $view->display_handler->get_option('offset');
    }
  }

  return $view;
}
