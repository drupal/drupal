<?php
// $Id: block.inc,v 1.17 2011/01/06 00:42:03 merlinofchaos Exp $

/**
 * @file
 * Provide Drupal blocks as content.
 *
 * Since blocks don't provide all of the features we do, we have to do a little
 * extra work, including providing icons and categories for core blocks. Blocks
 * from contrib modules get to provide their own stuff, or get relegated to
 * the old "Miscellaneous" category.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  // And this is just the administrative title.
  // All our callbacks are named according to the standard pattern and can be deduced.
  'title' => t('Block'),
  'content type' => 'ctools_block_content_type_content_type',
);

/**
 * Return the block content types with the specified $subtype_id.
 */
function ctools_block_content_type_content_type($subtype_id) {
  list($module, $delta) = explode('-', $subtype_id, 2);
  $module_blocks = module_invoke($module, 'block_info');
  if (isset($module_blocks[$delta])) {
    return _ctools_block_content_type_content_type($module, $delta, $module_blocks[$delta]);
  }
}

/**
 * Return all block content types available.
 *
 * Modules wanting to make special adjustments the way that CTools handles their blocks
 * can implement an extension to the hook_block() family, where the function name is
 * of the form "$module . '_ctools_block_info'".
 */
function ctools_block_content_type_content_types() {
  $types = array();
  foreach (module_implements('block_info') as $module) {
    $module_blocks = module_invoke($module, 'block_info');
    if ($module_blocks) {
      foreach ($module_blocks as $delta => $block) {
        $info = _ctools_block_content_type_content_type($module, $delta, $block);
        // this check means modules can remove their blocks; particularly useful
        // if they offer the block some other way (like we do for views)
        if ($info) {
          $types["$module-$delta"] = $info;
        }
      }
    }
  }
  return $types;
}

/**
 * Return an info array for a specific block.
 */
function _ctools_block_content_type_content_type($module, $delta, $block) {
  // strip_tags used because it goes through check_plain and that
  // just looks bad.
  $info = array(
    'title' => strip_tags($block['info']),
  );

  // Ask around for further information by invoking the hook_block() extension.
  $function = $module . '_ctools_block_info';
  if (!function_exists($function)) {
    $function = 'ctools_default_block_info';
  }
  $function($module, $delta, $info);

  return $info;
}

/**
 * Output function for the 'block' content type. Outputs a block
 * based on the module and delta supplied in the configuration.
 */
function ctools_block_content_type_render($subtype, $conf) {
  list($module, $delta) = _ctools_block_get_module_delta($subtype, $conf);
  $info = (object) array(
    'module' => $module,
    'delta' => $delta,
  );
  $block = module_invoke($module, 'block_view', $delta);
  // Allow modules to modify the block before it is viewed, via either
  // hook_block_view_alter() or hook_block_view_MODULE_DELTA_alter().
  drupal_alter(array('block_view', "block_view_{$module}_{$delta}"), $block, $info);
  $block = (object) $block;

  if (empty($block)) {
    return;
  }

  $block->module = $module;
  $block->delta = $delta;

  // $block->title is not set for the blocks returned by block_block() (the
  // Block module adds the title in block_list() instead), so we look it up
  // manually, unless the title is overridden and does not use the %title
  // placeholder.
  if ($module == 'block' && (empty($conf['override_title']) || strpos($conf['override_title_text'], '%title') !== FALSE)) {
    // {block}.title is plain text, but this hook should return an HTML title.
    global $theme_key;
    $result = db_query("SELECT title FROM {block}
      WHERE module = :module AND delta = :delta AND theme = :theme", array(
          ':module' => 'block', ':delta' => $delta, ':theme' => $theme_key
      )
    );
    $block->subject = check_plain($result->fetchObject()->title);
  }

  if (isset($block->subject)) {
    $block->title = $block->subject;
  }
  else {
    $block->title = NULL;
  }

  if (user_access('administer blocks')) {
    $block->admin_links = array(
      array(
        'title' => t('Configure block'),
        'href' => "admin/structure/block/manage/$module/$delta/configure",
        'query' => drupal_get_destination(),
      ),
    );
  }

  return $block;
}

/**
 * Empty form so we can have the default override title.
 */
function ctools_block_content_type_edit_form($form, &$form_state) {
  // Does nothing!
  return $form;
}

/**
 * Submit function to fix the subtype for really old panel panes.
 */
function ctools_block_content_type_edit_form_submit($form, &$form_state) {
  if (empty($form_state['subtype']) && isset($form_state['pane'])) {
    $form_state['pane']->subtype = $form_state['conf']['module'] . '-' . $form_state['conf']['delta'];
    unset($form_state['conf']['module']);
    unset($form_state['conf']['delta']);
  }
}

/**
 * Returns an edit form for a block.
 */
//function ctools_block_content_type_edit_form($id, $parents, $conf) {
//  if (user_access('administer advanced pane settings')) {
//    $form['block_visibility'] = array(
//      '#type' => 'checkbox',
//      '#title' => t('Use block visibility settings (see block config)'),
//      '#default_value' => !empty($conf['block_visibility']),
//      '#description' => t('If checked, the block visibility settings for this block will apply to this block.'),
//    );
//    // Module-specific block configurations.
//    if ($settings = module_invoke($module, 'block', 'configure', $delta)) {
//      // Specifically modify a couple of core block forms.
//      if ($module == 'block') {
//        unset($settings['submit']);
//        $settings['info']['#type'] = 'value';
//        $settings['info']['#value'] = $settings['info']['#default_value'];
//      }
//      ctools_admin_fix_block_tree($settings);
//      $form['block_settings'] = array(
//        '#type' => 'fieldset',
//        '#title' => t('Block settings'),
//        '#description' => t('Settings in this section are global and are for all blocks of this type, anywhere in the system.'),
//        '#tree' => FALSE,
//      );
//
//
//      $form['block_settings'] += $settings;
//    }
//  }
//
//  return $form;
//}

//function ctools_admin_submit_block(&$form_values) {
//  if (!empty($form_values['block_settings'])) {
//    module_invoke($form_values['module'], 'block', 'save', $form_values['delta'], $form_values['block_settings']);
//  }
//}
//
///**
// * Because form api cannot collapse just part of a tree, and the block settings
// * assume no tree, we have to collapse the tree ourselves.
// */
//function ctools_admin_fix_block_tree(&$form, $key = NULL) {
//  if ($key) {
//    if (!empty($form['#parents'])) {
//      $form['#parents'] = array_merge(array('configuration', 'block_settings'), $form['#parents']);
//    }
//    else if (empty($form['#tree'])) {
//      $form['#parents'] = array('configuration', 'block_settings', $key);
//    }
//  }
//
//  if (isset($form['#type']) && $form['#type'] == 'textarea' && !empty($form['#rows']) && $form['#rows'] > 10) {
//    $form['#rows'] = 10;
//  }
//
//  foreach (element_children($form) as $key) {
//    ctools_admin_fix_block_tree($form[$key], $key);
//  }
//}

/**
 * Returns the administrative title for a type.
 */
function ctools_block_content_type_admin_title($subtype, $conf) {
  list($module, $delta) = _ctools_block_get_module_delta($subtype, $conf);
  $block = module_invoke($module, 'block_info');
  if (empty($block) || empty($block[$delta])) {
    return t('Deleted/missing block @module-@delta', array('@module' => $module, '@delta' => $delta));
  }

  // The block description reported by hook_block() is plain text, but the title
  // reported by this hook should be HTML.
  $title = check_plain($block[$delta]['info']);
  return $title;
}

/**
 * Output function for the 'block' content type. Outputs a block
 * based on the module and delta supplied in the configuration.
 */
function ctools_block_content_type_admin_info($subtype, $conf) {
  list($module, $delta) = _ctools_block_get_module_delta($subtype, $conf);
  $block = (object) module_invoke($module, 'block', 'view', $delta);

  // Sanitize the block because <script> tags can hose javascript up:
  if (!empty($block->content)) {
    $block->content = filter_xss_admin($block->content);
  }

  if (!empty($block) && !empty($block->subject)) {
    $block->title = $block->subject;
    return $block;
  }
}

function _ctools_block_get_module_delta($subtype, $conf) {
  if (strpos($subtype, '-')) {
    return explode('-', $subtype, 2);
  }
  else {
    return array($conf['module'], $conf['delta']);
  }
}

/**
 * Provide default icon and categories for blocks when modules don't do this
 * for us.
 */
function ctools_default_block_info($module, $delta, &$info) {
  $core_modules = array('aggregator', 'block', 'blog', 'blogapi', 'book', 'color', 'comment', 'contact', 'drupal', 'filter', 'forum', 'help', 'legacy', 'locale', 'menu', 'node', 'path', 'ping', 'poll', 'profile', 'search', 'statistics', 'taxonomy', 'throttle', 'tracker', 'upload', 'user', 'watchdog', 'system');

  if (in_array($module, $core_modules)) {
    $info['icon'] = 'icon_core_block.png';
    $info['category'] = t('Miscellaneous');
  }
  else {
    $info['icon'] = 'icon_contrib_block.png';
    $info['category'] = t('Miscellaneous');
  }
}

// These are all on behalf of modules that don't implement ctools but that
// we care about.
function menu_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_block_menu.png';
  $info['category'] = t('Menus');
  if ($delta == 'primary-links' || $delta == 'secondary-links') {
    $info['icon'] = 'icon_core_primarylinks.png';
  }
}

function forum_ctools_block_info($module, $delta, &$info) {
  $info['category'] = t('Activity');
  switch ($delta) {
    case 'active':
      $info['icon'] = 'icon_core_activeforumtopics.png';
      break;

    case 'new':
      $info['icon'] = 'icon_core_newforumtopics.png';
      break;

    default:
      // safety net
      ctools_default_block_info($module, $delta, $info);
  }
}

function profile_ctools_block_info($module, $delta, &$info) {
  // Hide the author information block which isn't as rich as what we can
  // do with context.
  $info = NULL;
}

function book_ctools_block_info($module, $delta, &$info) {
  // Hide the book navigation block which isn't as rich as what we can
  // do with context.
  $info = NULL;
}

function blog_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_recentblogposts.png';
  $info['category'] = t('Activity');
}

function poll_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_recentpoll.png';
  $info['category'] = t('Activity');
}

function comment_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_recentcomments.png';
  $info['category'] = t('Activity');
}

function search_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_searchform.png';
  $info['category'] = t('Widgets');
}

function node_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_syndicate.png';
  $info['category'] = t('Widgets');
}

function aggregator_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_syndicate.png';
  $info['category'] = t('Feeds');
}

function block_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_block_empty.png';
  $info['category'] = t('Custom blocks');
}

function user_ctools_block_info($module, $delta, &$info) {
  $info['category'] = t('Activity');
  switch ($delta) {
    case 'login':
      $info['icon'] = 'icon_core_userlogin.png';
      $info['category'] = t('Widgets');
      break;

    case 'new':
      $info['icon'] = 'icon_core_whosnew.png';
      break;

    case 'online':
      $info['icon'] = 'icon_core_whosonline.png';
      break;

    default:
      // safety net
      ctools_default_block_info($module, $delta, $info);
  }
}

function locale_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_languageswitcher.png';
  $info['category'] = t('Widgets');
}

function statistics_ctools_block_info($module, $delta, &$info) {
  $info['icon'] = 'icon_core_popularcontent.png';
  $info['category'] = t('Activity');
}

function system_ctools_block_info($module, $delta, &$info) {
  // Remove the main content fake block.
  if ($delta == 'main') {
    $info = NULL;
    return;
  }

  $menus = array('main-menu', 'management', 'navigation', 'user-menu');

  if (in_array($delta, $menus)) {
    $info['icon'] = 'icon_core_block_menu.png';
    $info['category'] = t('Menus');

    if ($delta == 'navigation') {
      $info['icon'] = 'icon_core_navigation.png';
    }

    return;
  }

  $info['icon'] = 'icon_core_drupal.png';
  if ($delta == 'help') {
    $info['category'] = t('Page elements');
    return;
  }

  $info['category'] = t('Widgets');
}
