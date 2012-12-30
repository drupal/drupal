<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\PathPluginBase.
 */

namespace Drupal\views\Plugin\views\display;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The base display plugin for path/callbacks. This is used for pages and feeds.
 */
abstract class PathPluginBase extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::hasPath().
   */
  public function hasPath() {
    return TRUE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase:defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = array('default' => '');

    return $options;
  }

  /**
   * Add this display's path information to Drupal's menu system.
   */
  public function executeHookMenu($callbacks) {
    $items = array();
    // Replace % with the link to our standard views argument loader
    // views_arg_load -- which lives in views.module.

    $bits = explode('/', $this->getOption('path'));
    $page_arguments = array($this->view->storage->name, $this->display['id']);
    $this->view->initHandlers();
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

    $access_plugin = $this->getPlugin('access');
    if (!isset($access_plugin)) {
      $access_plugin = drupal_container()->get("plugin.manager.views.access")->createInstance('none');
    }

    // Get access callback might return an array of the callback + the dynamic
    // arguments.
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
        // Default views page entry.
        'page callback' => 'views_page',
        'page arguments' => $page_arguments,
        // Default access check (per display).
        'access callback' => 'views_access',
        'access arguments' => $access_arguments,
        // Identify URL embedded arguments and correlate them to a handler.
        'load arguments'  => array($this->view->storage->name, $this->display['id'], '%index'),
      );
      $menu = $this->getOption('menu');
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
          // Insert item into the proper menu.
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

      // If this is a 'default' tab, check to see if we have to create the
      // parent menu item.
      if ($menu['type'] == 'default tab') {
        $tab_options = $this->getOption('tab_options');
        if (!empty($tab_options['type']) && $tab_options['type'] != 'none') {
          $bits = explode('/', $path);
          // Remove the last piece.
          $bit = array_pop($bits);

          // we can't do this if they tried to make the last path bit variable.
          // @todo: We can validate this.
          if ($bit != '%views_arg' && !empty($bits)) {
            $default_path = implode('/', $bits);
            $items[$default_path] = array(
              // Default views page entry.
              'page callback' => 'views_page',
              'page arguments' => $page_arguments,
              // Default access check (per display).
              'access callback' => 'views_access',
              'access arguments' => $access_arguments,
              // Identify URL embedded arguments and correlate them to a
              // handler.
              'load arguments'  => array($this->view->storage->name, $this->display['id'], '%index'),
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
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::execute().
   */
  public function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $this->view->build();

    if (!empty($this->view->build_info['fail'])) {
      throw new NotFoundHttpException();
    }

    if (!empty($this->view->build_info['denied'])) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['page'] = array(
      'title' => t('Page settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $path = strip_tags($this->getOption('path'));

    if (empty($path)) {
      $path = t('No path is set');
    }
    else {
      $path = '/' . $path;
    }

    $options['path'] = array(
      'category' => 'page',
      'title' => t('Path'),
      'value' => views_ui_truncate($path, 24),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'path':
        $form['#title'] .= t('The menu path or URL of this view');
        $form['path'] = array(
          '#type' => 'textfield',
          '#description' => t('This view will be displayed by visiting this path on your site. You may use "%" in your URL to represent values that will be used for contextual filters: For example, "node/%/feed".'),
          '#default_value' => $this->getOption('path'),
          '#field_prefix' => '<span dir="ltr">' . url(NULL, array('absolute' => TRUE)),
          '#field_suffix' => '</span>&lrm;',
          '#attributes' => array('dir' => 'ltr'),
        );
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, &$form_state) {
    parent::validateOptionsForm($form, $form_state);

    if ($form_state['section'] == 'path') {
      if (strpos($form_state['values']['path'], '%') === 0) {
        form_error($form['path'], t('"%" may not be used for the first segment of a path.'));
      }

      // Automatically remove '/' and trailing whitespace from path.
      $form_state['values']['path'] = trim($form_state['values']['path'], '/ ');
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, &$form_state) {
    parent::submitOptionsForm($form, $form_state);

    if ($form_state['section'] == 'path') {
      $this->setOption('path', $form_state['values']['path']);
    }
  }

}
