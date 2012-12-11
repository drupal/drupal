<?php

/**
 * @file
 * Contains Drupal\views\Plugin\views\display\DisplayPluginBase.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\views\ViewExecutable;
use \Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_display_plugins Views display plugins
 * @{
 * Display plugins control how Views interact with the rest of Drupal.
 *
 * They can handle creating Views from a Drupal page hook; they can
 * handle creating Views from a Drupal block hook. They can also
 * handle creating Views from an external module source.
 */

/**
 * The default display plugin handler. Display plugins handle options and
 * basic mechanisms for different output methods.
 */
abstract class DisplayPluginBase extends PluginBase {

  /**
   * The top object of a view.
   *
   * @var Drupal\views\ViewExecutable
   */
  var $view = NULL;

  var $handlers = array();

  /**
   * An array of instantiated plugins used in this display.
   *
   * @var array
   */
  protected $plugins = array();

  /**
   * Stores all available display extenders.
   */
  var $extender = array();

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Stores the rendered output of the display.
   *
   * @see View::render
   * @var string
   */
  public $output = NULL;

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @var bool
   */
  protected $usesAJAX = TRUE;

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = TRUE;

  /**
   * Whether the display allows the use of a 'more' link or not.
   *
   * @var bool
   */
  protected $usesMore = TRUE;

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   *   TRUE if the display can use attachments, or FALSE otherwise.
   */
  protected $usesAttachments = FALSE;

  /**
   * Whether the display allows area plugins.
   *
   * @var bool
   */
  protected $usesAreas = TRUE;

  public function init(ViewExecutable $view, &$display, $options = NULL) {
    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->view = &$view;
    $this->display = &$display;

    // Load extenders as soon as possible.
    $this->extender = array();
    $extenders = views_get_enabled_display_extenders();
    if (!empty($extenders)) {
      $manager = drupal_container()->get('plugin.manager.views.display_extender');
      foreach ($extenders as $extender) {
        $plugin = $manager->createInstance($extender);
        if ($plugin) {
          $plugin->init($this->view, $this);
          $this->extender[$extender] = $plugin;
        }
      }
    }

    // Track changes that the user should know about.
    $changed = FALSE;

    // Make some modifications:
    if (!isset($options) && isset($display['display_options'])) {
      $options = $display['display_options'];
    }

    if ($this->isDefaultDisplay() && isset($options['defaults'])) {
      unset($options['defaults']);
    }

    views_include('cache');
    // Cache for unpackOptions, but not if we are in the ui.
    static $unpack_options = array();
    if (empty($view->editing)) {
      $cid = 'unpackOptions:' . md5(serialize(array($this->options, $options)));
      if (empty($unpack_options[$cid])) {
        $cache = views_cache_get($cid, TRUE);
        if (!empty($cache->data)) {
          $this->options = $cache->data;
        }
        else {
          $this->unpackOptions($this->options, $options);
          views_cache_set($cid, $this->options, TRUE);
        }
        $unpack_options[$cid] = $this->options;
      }
      else {
        $this->options = $unpack_options[$cid];
      }
    }
    else {
      $this->unpackOptions($this->options, $options);
    }

    // Convert the field_language and field_language_add_to_query settings.
    $field_language = $this->getOption('field_language');
    $field_language_add_to_query = $this->getOption('field_language_add_to_query');
    if (isset($field_langcode)) {
      $this->setOption('field_langcode', $field_language);
      $this->setOption('field_langcode_add_to_query', $field_language_add_to_query);
      $changed = TRUE;
    }

    // Mark the view as changed so the user has a chance to save it.
    if ($changed) {
      $this->view->changed = TRUE;
    }
  }

  public function destroy() {
    parent::destroy();

    foreach ($this->handlers as $type => $handlers) {
      foreach ($handlers as $id => $handler) {
        if (is_object($handler)) {
          $this->handlers[$type][$id]->destroy();
        }
      }
    }

    if (isset($this->default_display)) {
      unset($this->default_display);
    }

    foreach ($this->extender as $extender) {
      $extender->destroy();
    }
  }

  /**
   * Determine if this display is the 'default' display which contains
   * fallback settings
   */
  public function isDefaultDisplay() { return FALSE; }

  /**
   * Determine if this display uses exposed filters, so the view
   * will know whether or not to build them.
   */
  public function usesExposed() {
    if (!isset($this->has_exposed)) {
      foreach ($this->handlers as $type => $value) {
        foreach ($this->view->$type as $id => $handler) {
          if ($handler->canExpose() && $handler->isExposed()) {
            // one is all we need; if we find it, return true.
            $this->has_exposed = TRUE;
            return TRUE;
          }
        }
      }
      $pager = $this->getPlugin('pager');
      if (isset($pager) && $pager->uses_exposed()) {
        $this->has_exposed = TRUE;
        return TRUE;
      }
      $this->has_exposed = FALSE;
    }

    return $this->has_exposed;
  }

  /**
   * Determine if this display should display the exposed
   * filters widgets, so the view will know whether or not
   * to render them.
   *
   * Regardless of what this function
   * returns, exposed filters will not be used nor
   * displayed unless usesExposed() returns TRUE.
   */
  public function displaysExposed() {
    return TRUE;
  }

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @return bool
   */
  public function usesAJAX() {
    return $this->usesAJAX;
  }

  /**
   * Whether the display is actually using AJAX or not.
   *
   * @return bool
   */
  public function isAJAXEnabled() {
    if ($this->usesAJAX()) {
      return $this->getOption('use_ajax');
    }
    return FALSE;
  }

  /**
   * Whether the display is enabled.
   *
   * @return bool
   *   Returns TRUE if the display is marked as enabled, else FALSE.
   */
  public function isEnabled() {
    return (bool) $this->getOption('enabled');
  }

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @return bool
   */

  public function usesPager() {
    return $this->usesPager;
  }

  /**
   * Whether the display is using a pager or not.
   *
   * @return bool
   */
  public function isPagerEnabled() {
    if ($this->usesPager()) {
      $pager = $this->getPlugin('pager');
      if ($pager) {
        return $pager->use_pager();
      }
    }
    return FALSE;
  }

  /**
   * Whether the display allows the use of a 'more' link or not.
   *
   * @return bool
   */
  public function usesMore() {
    return $this->usesMore;
  }

  /**
   * Whether the display is using the 'more' link or not.
   *
   * @return bool
   */
  public function isMoreEnabled() {
    if ($this->usesMore()) {
      return $this->getOption('use_more');
    }
    return FALSE;
  }

  /**
   * Does the display have groupby enabled?
   */
  public function useGroupBy() {
    return $this->getOption('group_by');
  }

  /**
   * Should the enabled display more link be shown when no more items?
   */
  public function useMoreAlways() {
    if ($this->usesMore()) {
      return $this->getOption('useMoreAlways');
    }
    return FALSE;
  }

  /**
   * Does the display have custom link text?
   */
  public function useMoreText() {
    if ($this->usesMore()) {
      return $this->getOption('useMoreText');
    }
    return FALSE;
  }

  /**
   * Determines whether this display can use attachments.
   *
   * @return bool
   */
  public function acceptAttachments() {
    // To be able to accept attachments this display have to be able to use
    // attachments but at the same time, you cannot attach a display to itself.
    if (!$this->usesAttachments() || ($this->definition['id'] == $this->view->current_display)) {
      return FALSE;
    }

    if (!empty($this->view->argument) && $this->getOption('hide_attachment_summary')) {
      foreach ($this->view->argument as $argument_id => $argument) {
        if ($argument->needsStylePlugin() && empty($argument->argument_validated)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Returns whether the display can use attachments.
   *
   * @return bool
   */
  public function usesAttachments() {
    return $this->usesAttachments;
  }

  /**
   * Returns whether the display can use areas.
   *
   * @return bool
   *   TRUE if the display can use areas, or FALSE otherwise.
   */
  public function usesAreas() {
    return $this->usesAreas;
  }

  /**
   * Allow displays to attach to other views.
   */
  public function attachTo(ViewExecutable $view, $display_id) { }

  /**
   * Static member function to list which sections are defaultable
   * and what items each section contains.
   */
  public function defaultableSections($section = NULL) {
    $sections = array(
      'access' => array('access'),
      'cache' => array('cache'),
      'title' => array('title'),
      'css_class' => array('css_class'),
      'use_ajax' => array('use_ajax'),
      'hide_attachment_summary' => array('hide_attachment_summary'),
      'hide_admin_links' => array('hide_admin_links'),
      'group_by' => array('group_by'),
      'query' => array('query'),
      'use_more' => array('use_more', 'use_more_always', 'use_more_text'),
      'use_more_always' => array('use_more', 'use_more_always', 'use_more_text'),
      'use_more_text' => array('use_more', 'use_more_always', 'use_more_text'),
      'link_display' => array('link_display', 'link_url'),

      // Force these to cascade properly.
      'style' => array('style', 'row'),
      'row' => array('style', 'row'),

      'pager' => array('pager', 'pager_options'),
      'pager_options' => array('pager', 'pager_options'),

      'exposed_form' => array('exposed_form', 'exposed_form_options'),
      'exposed_form_options' => array('exposed_form', 'exposed_form_options'),

      // These guys are special
      'header' => array('header'),
      'footer' => array('footer'),
      'empty' => array('empty'),
      'relationships' => array('relationships'),
      'fields' => array('fields'),
      'sorts' => array('sorts'),
      'arguments' => array('arguments'),
      'filters' => array('filters', 'filter_groups'),
      'filter_groups' => array('filters', 'filter_groups'),
    );

    // If the display cannot use a pager, then we cannot default it.
    if (!$this->usesPager()) {
      unset($sections['pager']);
      unset($sections['items_per_page']);
    }

    foreach ($this->extender as $extender) {
      $extender->defaultableSections($sections, $section);
    }

    if ($section) {
      if (!empty($sections[$section])) {
        return $sections[$section];
      }
    }
    else {
      return $sections;
    }
  }

  protected function defineOptions() {
    $options = array(
      'defaults' => array(
        'default' => array(
          'access' => TRUE,
          'cache' => TRUE,
          'query' => TRUE,
          'title' => TRUE,
          'css_class' => TRUE,

          'display_description' => FALSE,
          'use_ajax' => TRUE,
          'hide_attachment_summary' => TRUE,
          'hide_admin_links' => FALSE,
          'pager' => TRUE,
          'use_more' => TRUE,
          'use_more_always' => TRUE,
          'use_more_text' => TRUE,
          'exposed_form' => TRUE,

          'link_display' => TRUE,
          'link_url' => '',
          'group_by' => TRUE,

          'style' => TRUE,
          'row' => TRUE,

          'header' => TRUE,
          'footer' => TRUE,
          'empty' => TRUE,

          'relationships' => TRUE,
          'fields' => TRUE,
          'sorts' => TRUE,
          'arguments' => TRUE,
          'filters' => TRUE,
          'filter_groups' => TRUE,
        ),
      ),

      'title' => array(
        'default' => '',
        'translatable' => TRUE,
      ),
      'enabled' => array(
        'default' => TRUE,
        'translatable' => FALSE,
        'bool' => TRUE,
      ),
      'display_comment' => array(
        'default' => '',
      ),
      'css_class' => array(
        'default' => '',
        'translatable' => FALSE,
      ),
      'display_description' => array(
        'default' => '',
        'translatable' => TRUE,
      ),
      'use_ajax' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'hide_attachment_summary' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'hide_admin_links' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'use_more' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'use_more_always' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'use_more_text' => array(
        'default' => 'more',
        'translatable' => TRUE,
      ),
      'link_display' => array(
        'default' => '',
      ),
      'link_url' => array(
        'default' => '',
      ),
      'group_by' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'field_langcode' => array(
        'default' => '***CURRENT_LANGUAGE***',
      ),
      'field_langcode_add_to_query' => array(
        'default' => TRUE,
        'bool' => TRUE,
      ),

      // These types are all plugins that can have individual settings
      // and therefore need special handling.
      'access' => array(
        'contains' => array(
          'type' => array('default' => 'none'),
          'options' => array('default' => array()),
        ),
      ),
      'cache' => array(
        'contains' => array(
          'type' => array('default' => 'none'),
          'options' => array('default' => array()),
        ),
      ),
      'query' => array(
        'contains' => array(
          'type' => array('default' => 'views_query'),
          'options' => array('default' => array()),
         ),
      ),
      'exposed_form' => array(
        'contains' => array(
          'type' => array('default' => 'basic'),
          'options' => array('default' => array()),
         ),
      ),
      'pager' => array(
        'contains' => array(
          'type' => array('default' => 'full'),
          'options' => array('default' => array()),
         ),
      ),
      'style' => array(
        'contains' => array(
          'type' => array('default' => 'default'),
          'options' => array('default' => array()),
        ),
      ),
      'row' => array(
        'contains' => array(
          'type' => array('default' => 'fields'),
          'options' => array('default' => array()),
        ),
      ),

      'exposed_block' => array(
        'default' => FALSE,
      ),

      'header' => array(
        'default' => array(),
      ),
      'footer' => array(
        'default' => array(),
      ),
      'empty' => array(
        'default' => array(),
      ),

      // We want these to export last.
      // These are the 5 handler types.
      'relationships' => array(
        'default' => array(),
      ),
      'fields' => array(
        'default' => array(),
      ),
      'sorts' => array(
        'default' => array(),
      ),
      'arguments' => array(
        'default' => array(),
      ),
      'filter_groups' => array(
        'contains' => array(
          'operator' => array('default' => 'AND'),
          'groups' => array('default' => array(1 => 'AND')),
        ),
      ),
      'filters' => array(
        'default' => array(),
      ),
    );

    if (!$this->usesPager()) {
      $options['defaults']['default']['use_pager'] = FALSE;
      $options['defaults']['default']['items_per_page'] = FALSE;
      $options['defaults']['default']['offset'] = FALSE;
      $options['defaults']['default']['pager'] = FALSE;
      $options['pager']['contains']['type']['default'] = 'some';
    }

    if ($this->isDefaultDisplay()) {
      unset($options['defaults']);
    }

    foreach ($this->extender as $extender) {
      $extender->defineOptionsAlter($options);
    }

    return $options;
  }

  /**
   * Check to see if the display has a 'path' field.
   *
   * This is a pure function and not just a setting on the definition
   * because some displays (such as a panel pane) may have a path based
   * upon configuration.
   *
   * By default, displays do not have a path.
   */
  public function hasPath() { return FALSE; }

  /**
   * Check to see if the display has some need to link to another display.
   *
   * For the most part, displays without a path will use a link display. However,
   * sometimes displays that have a path might also need to link to another display.
   * This is true for feeds.
   */
  public function usesLinkDisplay() { return !$this->hasPath(); }

  /**
   * Check to see if the display can put the exposed formin a block.
   *
   * By default, displays that do not have a path cannot disconnect
   * the exposed form and put it in a block, because the form has no
   * place to go and Views really wants the forms to go to a specific
   * page.
   */
  public function usesExposedFormInBlock() { return $this->hasPath(); }

  /**
   * Check to see which display to use when creating links within
   * a view using this display.
   */
  public function getLinkDisplay() {
    $display_id = $this->getOption('link_display');
    // If unknown, pick the first one.
    if (empty($display_id) || empty($this->view->displayHandlers[$display_id])) {
      foreach ($this->view->displayHandlers as $display_id => $display) {
        if (!empty($display) && $display->hasPath()) {
          return $display_id;
        }
      }
    }
    else {
      return $display_id;
    }
    // fall-through returns NULL
  }

  /**
   * Return the base path to use for this display.
   *
   * This can be overridden for displays that do strange things
   * with the path.
   */
  public function getPath() {
    if ($this->hasPath()) {
      return $this->getOption('path');
    }

    $display_id = $this->getLinkDisplay();
    if ($display_id && !empty($this->view->displayHandlers[$display_id]) && is_object($this->view->displayHandlers[$display_id])) {
      return $this->view->displayHandlers[$display_id]->getPath();
    }
  }

  public function getUrl() {
    return $this->view->getUrl();
  }

  /**
   * Check to see if the display needs a breadcrumb
   *
   * By default, displays do not need breadcrumbs
   */
  public function usesBreadcrumb() { return FALSE; }

  /**
   * Determine if a given option is set to use the default display or the
   * current display
   *
   * @return
   *   TRUE for the default display
   */
  public function isDefaulted($option) {
    return !$this->isDefaultDisplay() && !empty($this->default_display) && !empty($this->options['defaults'][$option]);
  }

  /**
   * Intelligently get an option either from this display or from the
   * default display, if directed to do so.
   */
  public function getOption($option) {
    if ($this->isDefaulted($option)) {
      return $this->default_display->getOption($option);
    }

    if (array_key_exists($option, $this->options)) {
      return $this->options[$option];
    }
  }

  /**
   * Determine if the display's style uses fields.
   *
   * @return bool
   */
  public function usesFields() {
    return $this->getPlugin('style')->usesFields();
  }

  /**
   * Get the instance of a plugin, for example style or row.
   *
   * @param string $type
   *   The type of the plugin.
   *
   * @return \Drupal\views\Plugin\views\PluginBase
   */
  public function getPlugin($type) {
    // Look up the plugin name to use for this instance.
    $options = $this->getOption($type);
    $name = $options['type'];

    // Query plugins allow specifying a specific query class per base table.
    if ($type == 'query') {
      $views_data = views_fetch_data($this->view->storage->get('base_table'));
      $name = isset($views_data['table']['base']['query_id']) ? $views_data['table']['base']['query_id'] : 'views_query';
    }

    // Plugin instances are stored on the display for re-use.
    if (!isset($this->plugins[$type][$name])) {
      $plugin = drupal_container()->get("plugin.manager.views.$type")->createInstance($name);

      // Initialize the plugin.
      $plugin->init($this->view, $this, $options['options']);

      $this->plugins[$type][$name] = $plugin;
    }

    return $this->plugins[$type][$name];
  }

  /**
   * Get the handler object for a single handler.
   */
  public function &getHandler($type, $id) {
    if (!isset($this->handlers[$type])) {
      $this->getHandlers($type);
    }

    if (isset($this->handlers[$type][$id])) {
      return $this->handlers[$type][$id];
    }

    // So we can return a reference.
    $null = NULL;
    return $null;
  }

  /**
   * Get a full array of handlers for $type. This caches them.
   */
  public function getHandlers($type) {
    if (!isset($this->handlers[$type])) {
      $this->handlers[$type] = array();
      $types = ViewExecutable::viewsHandlerTypes();
      $plural = $types[$type]['plural'];

      foreach ($this->getOption($plural) as $id => $info) {
        // If this is during form submission and there are temporary options
        // which can only appear if the view is in the edit cache, use those
        // options instead. This is used for AJAX multi-step stuff.
        if (isset($_POST['form_id']) && isset($this->view->temporary_options[$type][$id])) {
          $info = $this->view->temporary_options[$type][$id];
        }

        if ($info['id'] != $id) {
          $info['id'] = $id;
        }

        // If aggregation is on, the group type might override the actual
        // handler that is in use. This piece of code checks that and,
        // if necessary, sets the override handler.
        $override = NULL;
        if ($this->useGroupBy() && !empty($info['group_type'])) {
          if (empty($this->view->query)) {
            $this->view->initQuery();
          }
          $aggregate = $this->view->query->get_aggregation_info();
          if (!empty($aggregate[$info['group_type']]['handler'][$type])) {
            $override = $aggregate[$info['group_type']]['handler'][$type];
          }
        }

        if (!empty($types[$type]['type'])) {
          $handler_type = $types[$type]['type'];
        }
        else {
          $handler_type = $type;
        }

        $handler = views_get_handler($info['table'], $info['field'], $handler_type, $override);
        if ($handler) {
          // Special override for area types so they know where they come from.
          if ($handler_type == 'area') {
            $handler->handler_type = $type;
          }

          $handler->init($this->view, $info);
          $this->handlers[$type][$id] = &$handler;
        }

        // Prevent reference problems.
        unset($handler);
      }
    }

    return $this->handlers[$type];
  }

  /**
   * Retrieve a list of fields for the current display with the
   * relationship associated if it exists.
   *
   * @param $groupable_only
   *  Return only an array of field labels from handler that return TRUE
   *  from use_string_group_by method.
   */
  public function getFieldLabels() {
    // Use func_get_arg so the function signature isn't amended
    // but we can still pass TRUE into the function to filter
    // by groupable handlers.
    $args = func_get_args();
    $groupable_only = isset($args[0]) ? $args[0] : FALSE;

    $options = array();
    foreach ($this->getHandlers('relationship') as $relationship => $handler) {
      if ($label = $handler->label()) {
        $relationships[$relationship] = $label;
      }
      else {
        $relationships[$relationship] = $handler->adminLabel();
      }
    }

    foreach ($this->getHandlers('field') as $id => $handler) {
      if ($groupable_only && !$handler->use_string_group_by()) {
        // Continue to next handler if it's not groupable.
        continue;
      }
      if ($label = $handler->label()) {
        $options[$id] = $label;
      }
      else {
        $options[$id] = $handler->adminLabel();
      }
      if (!empty($handler->options['relationship']) && !empty($relationships[$handler->options['relationship']])) {
        $options[$id] = '(' . $relationships[$handler->options['relationship']] . ') ' . $options[$id];
      }
    }
    return $options;
  }

  /**
   * Intelligently set an option either from this display or from the
   * default display, if directed to do so.
   */
  public function setOption($option, $value) {
    if ($this->isDefaulted($option)) {
      return $this->default_display->setOption($option, $value);
    }

    // Set this in two places: On the handler where we'll notice it
    // but also on the display object so it gets saved. This should
    // only be a temporary fix.
    $this->display['display_options'][$option] = $value;
    return $this->options[$option] = $value;
  }

  /**
   * Set an option and force it to be an override.
   */
  public function overrideOption($option, $value) {
    $this->setOverride($option, FALSE);
    $this->setOption($option, $value);
  }

  /**
   * Because forms may be split up into sections, this provides
   * an easy URL to exactly the right section. Don't override this.
   */
  public function optionLink($text, $section, $class = '', $title = '') {
    if (!empty($class)) {
      $text = '<span>' . $text . '</span>';
    }

    if (!trim($text)) {
      $text = t('Broken field');
    }

    if (empty($title)) {
      $title = $text;
    }

    return l($text, 'admin/structure/views/nojs/display/' . $this->view->storage->get('name') . '/' . $this->display['id'] . '/' . $section, array('attributes' => array('class' => 'views-ajax-link ' . $class, 'title' => $title, 'id' => drupal_html_id('views-' . $this->display['id'] . '-' . $section)), 'html' => TRUE));
  }

  /**
   * Returns to tokens for arguments.
   *
   * This function is similar to views_handler_field::get_render_tokens()
   * but without fields tokens.
   */
  public function getArgumentsTokens() {
    $tokens = array();
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }
    $count = 0;
    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $token = '%' . ++$count;
      if (!isset($tokens[$token])) {
        $tokens[$token] = '';
      }

      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that
      // were removed by check_plain().
      $tokens['!' . $count] = isset($this->view->args[$count - 1]) ? strip_tags(decode_entities($this->view->args[$count - 1])) : '';
    }

    return $tokens;
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories = array(
      'title' => array(
        'title' => t('Title'),
        'column' => 'first',
      ),
      'format' => array(
        'title' => t('Format'),
        'column' => 'first',
      ),
      'filters' => array(
        'title' => t('Filters'),
        'column' => 'first',
      ),
      'fields' => array(
        'title' => t('Fields'),
        'column' => 'first',
      ),
      'pager' => array(
        'title' => t('Pager'),
        'column' => 'second',
      ),
      'exposed' => array(
        'title' => t('Exposed form'),
        'column' => 'third',
        'build' => array(
          '#weight' => 1,
        ),
      ),
      'access' => array(
        'title' => '',
        'column' => 'second',
        'build' => array(
          '#weight' => -5,
        ),
      ),
      'other' => array(
        'title' => t('Other'),
        'column' => 'third',
        'build' => array(
          '#weight' => 2,
        ),
      ),
    );

    if ($this->display['id'] != 'default') {
      $options['display_id'] = array(
        'category' => 'other',
        'title' => t('Machine Name'),
        'value' => !empty($this->display['new_id']) ? check_plain($this->display['new_id']) : check_plain($this->display['id']),
        'desc' => t('Change the machine name of this display.'),
      );
    }

    $display_comment = check_plain(drupal_substr($this->getOption('display_comment'), 0, 10));
    $options['display_comment'] = array(
      'category' => 'other',
      'title' => t('Comment'),
      'value' => !empty($display_comment) ? $display_comment : t('No comment'),
      'desc' => t('Comment or document this display.'),
    );

    $title = strip_tags($this->getOption('title'));
    if (!$title) {
      $title = t('None');
    }

    $options['title'] = array(
      'category' => 'title',
      'title' => t('Title'),
      'value' => $title,
      'desc' => t('Change the title that this display will use.'),
    );

    $style_plugin_instance = $this->getPlugin('style');
    $style_summary = empty($style_plugin_instance->definition['title']) ? t('Missing style plugin') : $style_plugin_instance->summaryTitle();
    $style_title = empty($style_plugin_instance->definition['title']) ? t('Missing style plugin') : $style_plugin_instance->pluginTitle();

    $style = '';

    $options['style'] = array(
      'category' => 'format',
      'title' => t('Format'),
      'value' => $style_title,
      'setting' => $style_summary,
      'desc' => t('Change the way content is formatted.'),
    );

    // This adds a 'Settings' link to the style_options setting if the style has options.
    if ($style_plugin_instance->usesOptions()) {
      $options['style']['links']['style_options'] = t('Change settings for this format');
    }

    if ($style_plugin_instance->usesRowPlugin()) {
      $row_plugin_instance = $this->getPlugin('row');
      $row_summary = empty($row_plugin_instance->definition['title']) ? t('Missing style plugin') : $row_plugin_instance->summaryTitle();
      $row_title = empty($row_plugin_instance->definition['title']) ? t('Missing style plugin') : $row_plugin_instance->pluginTitle();

      $options['row'] = array(
        'category' => 'format',
        'title' => t('Show'),
        'value' => $row_title,
        'setting' => $row_summary,
        'desc' => t('Change the way each row in the view is styled.'),
      );
      // This adds a 'Settings' link to the row_options setting if the row style has options.
      if ($row_plugin_instance->usesOptions()) {
        $options['row']['links']['row_options'] = t('Change settings for this style');
      }
    }
    if ($this->usesAJAX()) {
      $options['use_ajax'] = array(
        'category' => 'other',
        'title' => t('Use AJAX'),
        'value' => $this->getOption('use_ajax') ? t('Yes') : t('No'),
        'desc' => t('Change whether or not this display will use AJAX.'),
      );
    }
    if ($this->usesAttachments()) {
      $options['hide_attachment_summary'] = array(
        'category' => 'other',
        'title' => t('Hide attachments in summary'),
        'value' => $this->getOption('hide_attachment_summary') ? t('Yes') : t('No'),
        'desc' => t('Change whether or not to display attachments when displaying a contextual filter summary.'),
      );
    }
    if (!isset($this->definition['contextual links locations']) || !empty($this->definition['contextual links locations'])) {
      $options['hide_admin_links'] = array(
        'category' => 'other',
        'title' => t('Hide contextual links'),
        'value' => $this->getOption('hide_admin_links') ? t('Yes') : t('No'),
        'desc' => t('Change whether or not to display contextual links for this view.'),
      );
    }

    $pager_plugin = $this->getPlugin('pager');
    if (!$pager_plugin) {
      // default to the no pager plugin.
      $pager_plugin = views_get_plugin('pager', 'none');
    }

    $pager_str = $pager_plugin->summaryTitle();

    $options['pager'] = array(
      'category' => 'pager',
      'title' => t('Use pager'),
      'value' => $pager_plugin->pluginTitle(),
      'setting' => $pager_str,
      'desc' => t("Change this display's pager setting."),
    );

    // If pagers aren't allowed, change the text of the item:
    if (!$this->usesPager()) {
      $options['pager']['title'] = t('Items to display');
    }

    if ($pager_plugin->usesOptions()) {
      $options['pager']['links']['pager_options'] = t('Change settings for this pager type.');
    }

    if ($this->usesMore()) {
      $options['use_more'] = array(
        'category' => 'pager',
        'title' => t('More link'),
        'value' => $this->getOption('use_more') ? t('Yes') : t('No'),
        'desc' => t('Specify whether this display will provide a "more" link.'),
      );
    }

    $this->view->initQuery();
    if ($this->view->query->get_aggregation_info()) {
      $options['group_by'] = array(
        'category' => 'other',
        'title' => t('Use aggregation'),
        'value' => $this->getOption('group_by') ? t('Yes') : t('No'),
        'desc' => t('Allow grouping and aggregation (calculation) of fields.'),
      );
    }

    $options['query'] = array(
      'category' => 'other',
      'title' => t('Query settings'),
      'value' => t('Settings'),
      'desc' => t('Allow to set some advanced settings for the query plugin'),
    );

    $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
        LANGUAGE_NOT_SPECIFIED => t('Language neutral'),
    );
    if (module_exists('language')) {
      $languages = array_merge($languages, language_list());
    }
    $options['field_langcode'] = array(
      'category' => 'other',
      'title' => t('Field Language'),
      'value' => $languages[$this->getOption('field_langcode')],
      'desc' => t('All fields which support translations will be displayed in the selected language.'),
    );

    $access_plugin = $this->getPlugin('access');
    if (!$access_plugin) {
      // default to the no access control plugin.
      $access_plugin = views_get_plugin('access', 'none');
    }

    $access_str = $access_plugin->summaryTitle();

    $options['access'] = array(
      'category' => 'access',
      'title' => t('Access'),
      'value' => $access_plugin->pluginTitle(),
      'setting' => $access_str,
      'desc' => t('Specify access control type for this display.'),
    );

    if ($access_plugin->usesOptions()) {
      $options['access']['links']['access_options'] = t('Change settings for this access type.');
    }

    $cache_plugin = $this->getPlugin('cache');
    if (!$cache_plugin) {
      // default to the no cache control plugin.
      $cache_plugin = views_get_plugin('cache', 'none');
    }

    $cache_str = $cache_plugin->summaryTitle();

    $options['cache'] = array(
      'category' => 'other',
      'title' => t('Caching'),
      'value' => $cache_plugin->pluginTitle(),
      'setting' => $cache_str,
      'desc' => t('Specify caching type for this display.'),
    );

    if ($cache_plugin->usesOptions()) {
      $options['cache']['links']['cache_options'] = t('Change settings for this caching type.');
    }

    if ($access_plugin->usesOptions()) {
      $options['access']['links']['access_options'] = t('Change settings for this access type.');
    }

    if ($this->usesLinkDisplay()) {
      $display_id = $this->getLinkDisplay();
      $link_display = empty($this->view->display[$display_id]) ? t('None') : check_plain($this->view->display[$display_id]['display_title']);
      $link_display =  $this->getOption('link_display') == 'custom_url' ? t('Custom URL') : $link_display;
      $options['link_display'] = array(
        'category' => 'other',
        'title' => t('Link display'),
        'value' => $link_display,
        'desc' => t('Specify which display or custom url this display will link to.'),
      );
    }

    if ($this->usesExposedFormInBlock()) {
      $options['exposed_block'] = array(
        'category' => 'exposed',
        'title' => t('Exposed form in block'),
        'value' => $this->getOption('exposed_block') ? t('Yes') : t('No'),
        'desc' => t('Allow the exposed form to appear in a block instead of the view.'),
      );
    }

    $exposed_form_plugin = $this->getPlugin('exposed_form');
    if (!$exposed_form_plugin) {
      // default to the no cache control plugin.
      $exposed_form_plugin = views_get_plugin('exposed_form', 'basic');
    }

    $exposed_form_str = $exposed_form_plugin->summaryTitle();

    $options['exposed_form'] = array(
      'category' => 'exposed',
      'title' => t('Exposed form style'),
      'value' => $exposed_form_plugin->pluginTitle(),
      'setting' => $exposed_form_str,
      'desc' => t('Select the kind of exposed filter to use.'),
    );

    if ($exposed_form_plugin->usesOptions()) {
      $options['exposed_form']['links']['exposed_form_options'] = t('Exposed form settings for this exposed form style.');
    }

    $css_class = check_plain(trim($this->getOption('css_class')));
    if (!$css_class) {
      $css_class = t('None');
    }

    $options['css_class'] = array(
      'category' => 'other',
      'title' => t('CSS class'),
      'value' => $css_class,
      'desc' => t('Change the CSS class name(s) that will be added to this display.'),
    );

    $options['analyze-theme'] = array(
      'category' => 'other',
      'title' => t('Output'),
      'value' => t('Templates'),
      'desc' => t('Get information on how to theme this display'),
    );

    foreach ($this->extender as $extender) {
      $extender->optionsSummary($categories, $options);
    }
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->defaultableSections($form_state['section'])) {
      views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
    }
    $form['#title'] = check_plain($this->display['display_title']) . ': ';

    // Set the 'section' to hilite on the form.
    // If it's the item we're looking at is pulling from the default display,
    // reflect that. Don't use is_defaulted since we want it to show up even
    // on the default display.
    if (!empty($this->options['defaults'][$form_state['section']])) {
      $form['#section'] = 'default-' . $form_state['section'];
    }
    else {
      $form['#section'] = $this->display['id'] . '-' . $form_state['section'];
    }

    switch ($form_state['section']) {
      case 'display_id':
        $form['#title'] .= t('The machine name of this display');
        $form['display_id'] = array(
          '#type' => 'textfield',
          '#description' => t('This is machine name of the display.'),
          '#default_value' => !empty($this->display['new_id']) ? $this->display['new_id'] : $this->display['id'],
          '#required' => TRUE,
          '#size' => 64,
        );
        break;
      case 'display_title':
        $form['#title'] .= t('The name and the description of this display');
        $form['display_title'] = array(
          '#title' => t('Name'),
          '#type' => 'textfield',
          '#description' => t('This name will appear only in the administrative interface for the View.'),
          '#default_value' => $this->display['display_title'],
        );
        $form['display_description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#description' => t('This description will appear only in the administrative interface for the View.'),
          '#default_value' => $this->getOption('display_description'),
        );
        break;
      case 'display_comment':
        $form['#title'] .= t("This display's comments");
        $form['display_comment'] = array(
          '#type' => 'textarea',
          '#description' => t('This value will be seen and used only within the Views UI and can be used to document this display. You can use this to provide notes for other or future maintainers of your site about how or why this display is configured.'),
          '#default_value' => $this->getOption('display_comment'),
        );
        break;
      case 'title':
        $form['#title'] .= t('The title of this view');
        $form['title'] = array(
          '#type' => 'textfield',
          '#description' => t('This title will be displayed with the view, wherever titles are normally displayed; i.e, as the page title, block title, etc.'),
          '#default_value' => $this->getOption('title'),
        );
        break;
      case 'css_class':
        $form['#title'] .= t('CSS class');
        $form['css_class'] = array(
          '#type' => 'textfield',
          '#description' => t('The CSS class names will be added to the view. This enables you to use specific CSS code for each view. You may define multiples classes separated by spaces.'),
          '#default_value' => $this->getOption('css_class'),
        );
        break;
      case 'use_ajax':
        $form['#title'] .= t('Use AJAX when available to load this view');
        $form['description'] = array(
          '#markup' => '<div class="description form-item">' . t('If set, this view will use an AJAX mechanism for paging, table sorting and exposed filters. This means the entire page will not refresh. It is not recommended that you use this if this view is the main content of the page as it will prevent deep linking to specific pages, but it is very useful for side content.') . '</div>',
        );
        $form['use_ajax'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->getOption('use_ajax') ? 1 : 0,
        );
        break;
      case 'hide_attachment_summary':
        $form['#title'] .= t('Hide attachments when displaying a contextual filter summary');
        $form['hide_attachment_summary'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->getOption('hide_attachment_summary') ? 1 : 0,
        );
        break;
      case 'hide_admin_links':
        $form['#title'] .= t('Hide contextual links on this view.');
        $form['hide_admin_links'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->getOption('hide_admin_links') ? 1 : 0,
        );
      break;
      case 'use_more':
        $form['#title'] .= t('Add a more link to the bottom of the display.');
        $form['use_more'] = array(
          '#type' => 'checkbox',
          '#title' => t('Create more link'),
          '#description' => t("This will add a more link to the bottom of this view, which will link to the page view. If you have more than one page view, the link will point to the display specified in 'Link display' section under advanced. You can override the url at the link display setting."),
          '#default_value' => $this->getOption('use_more'),
        );
        $form['use_more_always'] = array(
          '#type' => 'checkbox',
          '#title' => t("Display 'more' link only if there is more content"),
          '#description' => t("Leave this unchecked to display the 'more' link even if there are no more items to display."),
          '#default_value' => !$this->getOption('use_more_always'),
          '#states' => array(
            'visible' => array(
              ':input[name="use_more"]' => array('checked' => TRUE),
            ),
          ),
        );
        $form['use_more_text'] = array(
          '#type' => 'textfield',
          '#title' => t('More link text'),
          '#description' => t("The text to display for the more link."),
          '#default_value' => $this->getOption('useMoreText'),
          '#states' => array(
            'visible' => array(
              ':input[name="use_more"]' => array('checked' => TRUE),
            ),
          ),
        );
        break;
      case 'group_by':
        $form['#title'] .= t('Allow grouping and aggregation (calculation) of fields.');
        $form['group_by'] = array(
          '#type' => 'checkbox',
          '#title' => t('Aggregate'),
          '#description' => t('If enabled, some fields may become unavailable. All fields that are selected for grouping will be collapsed to one record per distinct value. Other fields which are selected for aggregation will have the function run on them. For example, you can group nodes on title and count the number of nids in order to get a list of duplicate titles.'),
          '#default_value' => $this->getOption('group_by'),
        );
        break;
      case 'access':
        $form['#title'] .= t('Access restrictions');
        $form['access'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $access = $this->getOption('access');
        $form['access']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('access', NULL, array($this->view->storage->get('base_table'))),
          '#default_value' => $access['type'],
        );

        $access_plugin = $this->getPlugin('access');
        if ($access_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#markup' => t('You may also adjust the !settings for the currently selected access restriction.', array('!settings' => $this->optionLink(t('settings'), 'access_options'))),
            '#suffix' => '</div>',
          );
        }

        break;
      case 'access_options':
        $plugin = $this->getPlugin('access');
        $form['#title'] .= t('Access options');
        if ($plugin) {
          $form['access_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['access_options'], $form_state);
        }
        break;
      case 'cache':
        $form['#title'] .= t('Caching');
        $form['cache'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $cache = $this->getOption('cache');
        $form['cache']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('cache', NULL, array($this->view->storage->get('base_table'))),
          '#default_value' => $cache['type'],
        );

        $cache_plugin = $this->getPlugin('cache');
        if ($cache_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected cache mechanism.', array('!settings' => $this->optionLink(t('settings'), 'cache_options'))),
          );
        }
        break;
      case 'cache_options':
        $plugin = $this->getPlugin('cache');
        $form['#title'] .= t('Caching options');
        if ($plugin) {
          $form['cache_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['cache_options'], $form_state);
        }
        break;
      case 'query':
        $query_options = $this->getOption('query');
        $plugin_name = $query_options['type'];

        $form['#title'] .= t('Query options');
        $this->view->initQuery();
        if ($this->view->query) {
          $form['query'] = array(
            '#tree' => TRUE,
            'type' => array(
              '#type' => 'value',
              '#value' => $plugin_name,
            ),
            'options' => array(
              '#tree' => TRUE,
            ),
          );

          $this->view->query->buildOptionsForm($form['query']['options'], $form_state);
        }
        break;
      case 'field_language':
        $form['#title'] .= t('Field Language');

        $entities = entity_get_info();
        $entity_tables = array();
        $has_translation_handlers = FALSE;
        foreach ($entities as $type => $entity_info) {
          $entity_tables[] = $entity_info['base_table'];

          if (!empty($entity_info['translation'])) {
            $has_translation_handlers = TRUE;
          }
        }

        // Doesn't make sense to show a field setting here if we aren't querying
        // an entity base table. Also, we make sure that there's at least one
        // entity type with a translation handler attached.
        if (in_array($this->view->storage->get('base_table'), $entity_tables) && $has_translation_handlers) {
          $languages = array(
            '***CURRENT_LANGUAGE***' => t("Current user's language"),
            '***DEFAULT_LANGUAGE***' => t("Default site language"),
            LANGUAGE_NOT_SPECIFIED => t('Language neutral'),
          );
          $languages = array_merge($languages, views_language_list());

          $form['field_langcode'] = array(
            '#type' => 'select',
            '#title' => t('Field Language'),
            '#description' => t('All fields which support translations will be displayed in the selected language.'),
            '#options' => $languages,
            '#default_value' => $this->getOption('field_langcode'),
          );
          $form['field_langcode_add_to_query'] = array(
            '#type' => 'checkbox',
            '#title' => t('When needed, add the field language condition to the query'),
            '#default_value' => $this->getOption('field_langcode_add_to_query'),
          );
        }
        else {
          $form['field_language']['#markup'] = t("You don't have translatable entity types.");
        }
        break;
      case 'style':
        $form['#title'] .= t('How should this view be styled');
        $style_plugin = $this->getPlugin('style');
        $form['style'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('style', $this->getStyleType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $style_plugin->definition['id'],
          '#description' => t('If the style you choose has settings, be sure to click the settings button that will appear next to it in the View summary.'),
        );

        if ($style_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->optionLink(t('settings'), 'style_options'))),
          );
        }

        break;
      case 'style_options':
        $form['#title'] .= t('Style options');
        $style = TRUE;
        $style_plugin = $this->getOption('style');
        $name = $style_plugin['type'];

      case 'row_options':
        if (!isset($name)) {
          $row_plugin = $this->getOption('row');
          $name = $row_plugin['type'];
        }
        // if row, $style will be empty.
        if (empty($style)) {
          $form['#title'] .= t('Row style options');
        }
        $plugin = $this->getPlugin(empty($style) ? 'row' : 'style', $name);
        if ($plugin) {
          $form[$form_state['section']] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form[$form_state['section']], $form_state);
        }
        break;
      case 'row':
        $form['#title'] .= t('How should each row in this view be styled');
        $row_plugin_instance = $this->getPlugin('row');
        $form['row'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('row', $this->getStyleType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $row_plugin_instance->definition['id'],
        );

        if ($row_plugin_instance->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected row style.', array('!settings' => $this->optionLink(t('settings'), 'row_options'))),
          );
        }

        break;
      case 'link_display':
        $form['#title'] .= t('Which display to use for path');
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          if ($this->view->displayHandlers[$display_id]->hasPath()) {
            $options[$display_id] = $display['display_title'];
          }
        }
        $options['custom_url'] = t('Custom URL');
        if (count($options)) {
          $form['link_display'] = array(
            '#type' => 'radios',
            '#options' => $options,
            '#description' => t("Which display to use to get this display's path for things like summary links, rss feed links, more links, etc."),
            '#default_value' => $this->getOption('link_display'),
          );
        }

        $options = array();
        $count = 0; // This lets us prepare the key as we want it printed.
        foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
          $options[t('Arguments')]['%' . ++$count] = t('@argument title', array('@argument' => $handler->adminLabel()));
          $options[t('Arguments')]['!' . $count] = t('@argument input', array('@argument' => $handler->adminLabel()));
        }

        // Default text.
        // We have some options, so make a list.
        $output = '';
        if (!empty($options)) {
          $output = t('<p>The following tokens are available for this link.</p>');
          foreach (array_keys($options) as $type) {
            if (!empty($options[$type])) {
              $items = array();
              foreach ($options[$type] as $key => $value) {
                $items[] = $key . ' == ' . $value;
              }
              $output .= theme('item_list',
                array(
                  'items' => $items,
                  'type' => $type
                ));
            }
          }
        }

        $form['link_url'] = array(
          '#type' => 'textfield',
          '#title' => t('Custom URL'),
          '#default_value' => $this->getOption('link_url'),
          '#description' => t('A Drupal path or external URL the more link will point to. Note that this will override the link display setting above.') . $output,
          '#states' => array(
            'visible' => array(
              ':input[name="link_display"]' => array('value' => 'custom_url'),
            ),
          ),
        );
        break;
      case 'analyze-theme':
        $form['#title'] .= t('Theming information');
        if ($theme = drupal_container()->get('request')->request->get('theme')) {
          $this->theme = $theme;
        }
        elseif (empty($this->theme)) {
          $this->theme = variable_get('theme_default', 'bartik');
        }

        if (isset($GLOBALS['theme']) && $GLOBALS['theme'] == $this->theme) {
          $this->theme_registry = theme_get_registry();
          $theme_engine = $GLOBALS['theme_engine'];
        }
        else {
          $themes = list_themes();
          $theme = $themes[$this->theme];

          // Find all our ancestor themes and put them in an array.
          $base_theme = array();
          $ancestor = $this->theme;
          while ($ancestor && isset($themes[$ancestor]->base_theme)) {
            $ancestor = $themes[$ancestor]->base_theme;
            $base_theme[] = $themes[$ancestor];
          }

          // The base themes should be initialized in the right order.
          $base_theme = array_reverse($base_theme);

          // This code is copied directly from _drupal_theme_initialize()
          $theme_engine = NULL;

          // Initialize the theme.
          if (isset($theme->engine)) {
            // Include the engine.
            include_once DRUPAL_ROOT . '/' . $theme->owner;

            $theme_engine = $theme->engine;
            if (function_exists($theme_engine . '_init')) {
              foreach ($base_theme as $base) {
                call_user_func($theme_engine . '_init', $base);
              }
              call_user_func($theme_engine . '_init', $theme);
            }
          }
          else {
            // include non-engine theme files
            foreach ($base_theme as $base) {
              // Include the theme file or the engine.
              if (!empty($base->owner)) {
                include_once DRUPAL_ROOT . '/' . $base->owner;
              }
            }
            // and our theme gets one too.
            if (!empty($theme->owner)) {
              include_once DRUPAL_ROOT . '/' . $theme->owner;
            }
          }
          $this->theme_registry = _theme_load_registry($theme, $base_theme, $theme_engine);
        }

        // If there's a theme engine involved, we also need to know its extension
        // so we can give the proper filename.
        $this->theme_extension = '.tpl.php';
        if (isset($theme_engine)) {
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $this->theme_extension = $extension_function();
          }
        }

        $funcs = array();
        // Get theme functions for the display. Note that some displays may
        // not have themes. The 'feed' display, for example, completely
        // delegates to the style.
        if (!empty($this->definition['theme'])) {
          $funcs[] = $this->optionLink(t('Display output'), 'analyze-theme-display') . ': '  . $this->formatThemes($this->themeFunctions());
        }

        $plugin = $this->getPlugin('style');
        if ($plugin) {
          $funcs[] = $this->optionLink(t('Style output'), 'analyze-theme-style') . ': ' . $this->formatThemes($plugin->themeFunctions());

          if ($plugin->usesRowPlugin()) {
            $row_plugin = $this->getPlugin('row');
            if ($row_plugin) {
              $funcs[] = $this->optionLink(t('Row style output'), 'analyze-theme-row') . ': ' . $this->formatThemes($row_plugin->themeFunctions());
            }
          }

          if ($plugin->usesFields()) {
            foreach ($this->getHandlers('field') as $id => $handler) {
              $funcs[] = $this->optionLink(t('Field @field (ID: @id)', array('@field' => $handler->adminLabel(), '@id' => $id)), 'analyze-theme-field') . ': ' . $this->formatThemes($handler->themeFunctions());
            }
          }
        }

        $form['important'] = array(
          '#markup' => '<div class="form-item description"><p>' . t('This section lists all possible templates for the display plugin and for the style plugins, ordered roughly from the least specific to the most specific. The active template for each plugin -- which is the most specific template found on the system -- is highlighted in bold.') . '</p></div>',
        );

        if (isset($this->view->display_handler->new_id)) {
          $form['important']['new_id'] = array(
            '#prefix' => '<div class="description">',
            '#suffix' => '</div>',
            '#value' => t("<strong>Important!</strong> You have changed the display's machine name. Anything that attached to this display specifically, such as theming, may stop working until it is updated. To see theme suggestions for it, you need to save the view."),
          );
        }

        foreach (list_themes() as $key => $theme) {
          if (!empty($theme->info['hidden'])) {
            continue;
          }
          $options[$key] = $theme->info['name'];
        }

        $form['box'] = array(
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '</div>',
        );
        $form['box']['theme'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $this->theme,
        );

        $form['box']['change'] = array(
          '#type' => 'submit',
          '#value' => t('Change theme'),
          '#submit' => array('views_ui_edit_display_form_change_theme'),
        );

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . theme('item_list', array('items' => $funcs)) . '</div>',
        );

        $form['rescan_button'] = array(
          '#prefix' => '<div class="form-item">',
          '#suffix' => '</div>',
        );
        $form['rescan_button']['button'] = array(
          '#type' => 'submit',
          '#value' => t('Rescan template files'),
          '#submit' => array('views_ui_config_item_form_rescan'),
        );
        $form['rescan_button']['markup'] = array(
          '#markup' => '<div class="description">' . t("<strong>Important!</strong> When adding, removing, or renaming template files, it is necessary to make Drupal aware of the changes by making it rescan the files on your system. By clicking this button you clear Drupal's theme registry and thereby trigger this rescanning process. The highlighted templates above will then reflect the new state of your system.") . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-display':
        $form['#title'] .= t('Theming information (display)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->optionLink(t('theming information'), 'analyze-theme'))) . '</p>';

        if (empty($this->definition['theme'])) {
          $output .= t('This display has no theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this display.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $this->definition['theme_path'] . '/' . strtr($this->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-style':
        $form['#title'] .= t('Theming information (style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->optionLink(t('theming information'), 'analyze-theme'))) . '</p>';

        $plugin = $this->getPlugin('style');

        if (empty($plugin->definition['theme'])) {
          $output .= t('This display has no style theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this style.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme_path'] . '/' . strtr($plugin->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-row':
        $form['#title'] .= t('Theming information (row style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->optionLink(t('theming information'), 'analyze-theme'))) . '</p>';

        $plugin = $this->getPlugin('row');

        if (empty($plugin->definition['theme'])) {
          $output .= t('This display has no row style theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this row style.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme_path'] . '/' . strtr($plugin->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-field':
        $form['#title'] .= t('Theming information (row style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->optionLink(t('theming information'), 'analyze-theme'))) . '</p>';

        $output .= '<p>' . t('This is the default theme template used for this row style.') . '</p>';

        // Field templates aren't registered the normal way...and they're always
        // this one, anyhow.
        $output .= '<pre>' . check_plain(file_get_contents(drupal_get_path('module', 'views') . '/templates/views-view-field.tpl.php')) . '</pre>';

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );
        $form_state['ok_button'] = TRUE;
        break;

      case 'exposed_block':
        $form['#title'] .= t('Put the exposed form in a block');
        $form['description'] = array(
          '#markup' => '<div class="description form-item">' . t('If set, any exposed widgets will not appear with this view. Instead, a block will be made available to the Drupal block administration system, and the exposed form will appear there. Note that this block must be enabled manually, Views will not enable it for you.') . '</div>',
        );
        $form['exposed_block'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->getOption('exposed_block') ? 1 : 0,
        );
        break;
      case 'exposed_form':
        $form['#title'] .= t('Exposed Form');
        $form['exposed_form'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $exposed_form = $this->getOption('exposed_form');
        $form['exposed_form']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('exposed_form', NULL, array($this->view->storage->get('base_table'))),
          '#default_value' => $exposed_form['type'],
        );

        $exposed_form_plugin = $this->getPlugin('exposed_form');
        if ($exposed_form_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->optionLink(t('settings'), 'exposed_form_options'))),
          );
        }
        break;
      case 'exposed_form_options':
        $plugin = $this->getPlugin('exposed_form');
        $form['#title'] .= t('Exposed form options');
        if ($plugin) {
          $form['exposed_form_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['exposed_form_options'], $form_state);
        }
        break;
      case 'pager':
        $form['#title'] .= t('Select which pager, if any, to use for this view');
        $form['pager'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $pager = $this->getOption('pager');
        $form['pager']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('pager', !$this->usesPager() ? 'basic' : NULL, array($this->view->storage->get('base_table'))),
          '#default_value' => $pager['type'],
        );

        $pager_plugin = $this->getPlugin('pager');
        if ($pager_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected pager.', array('!settings' => $this->optionLink(t('settings'), 'pager_options'))),
          );
        }

        break;
      case 'pager_options':
        $plugin = $this->getPlugin('pager');
        $form['#title'] .= t('Pager options');
        if ($plugin) {
          $form['pager_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->buildOptionsForm($form, $form_state);
    }
  }

  /**
   * Format a list of theme templates for output by the theme info helper.
   */
  protected function formatThemes($themes) {
    $registry = $this->theme_registry;
    $extension = $this->theme_extension;

    $output = '';
    $picked = FALSE;
    foreach ($themes as $theme) {
      $template = strtr($theme, '_', '-') . $extension;
      if (!$picked && !empty($registry[$theme])) {
        $template_path = isset($registry[$theme]['path']) ? $registry[$theme]['path'] . '/' : './';
        if (file_exists($template_path . $template)) {
          $hint = t('File found in folder @template-path', array('@template-path' => $template_path));
          $template = '<strong title="'. $hint .'">' . $template . '</strong>';
        }
        else {
          $template = '<strong class="error">' . $template . ' ' . t('(File not found, in folder @template-path)', array('@template-path' => $template_path)) . '</strong>';
        }
        $picked = TRUE;
      }
      $fixed[] = $template;
    }

    return theme('item_list', array('items' => array_reverse($fixed)));
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, &$form_state) {
    switch ($form_state['section']) {
      case 'display_title':
        if (empty($form_state['values']['display_title'])) {
          form_error($form['display_title'], t('Display title may not be empty.'));
        }
        break;
      case 'css_class':
        $css_class = $form_state['values']['css_class'];
        if (preg_match('/[^a-zA-Z0-9-_ ]/', $css_class)) {
          form_error($form['css_class'], t('CSS classes must be alphanumeric or dashes only.'));
        }
      break;
      case 'display_id':
        if ($form_state['values']['display_id']) {
          if (preg_match('/[^a-z0-9_]/', $form_state['values']['display_id'])) {
            form_error($form['display_id'], t('Display name must be letters, numbers, or underscores only.'));
          }

          foreach ($this->view->display as $id => $display) {
            if ($id != $this->view->current_display && ($form_state['values']['display_id'] == $id || (isset($display->new_id) && $form_state['values']['display_id'] == $display->new_id))) {
              form_error($form['display_id'], t('Display id should be unique.'));
            }
          }
        }
        break;
      case 'style_options':
        $style = TRUE;
      case 'row_options':
        // if row, $style will be empty.
        $plugin = $this->getPlugin(empty($style) ? 'row' : 'style');
        if ($plugin) {
          $plugin->validateOptionsForm($form[$form_state['section']], $form_state);
        }
        break;
      case 'access_options':
        $plugin = $this->getPlugin('access');
        if ($plugin) {
          $plugin->validateOptionsForm($form['access_options'], $form_state);
        }
        break;
      case 'query':
        if ($this->view->query) {
          $this->view->query->validateOptionsForm($form['query'], $form_state);
        }
        break;
      case 'cache_options':
        $plugin = $this->getPlugin('cache');
        if ($plugin) {
          $plugin->validateOptionsForm($form['cache_options'], $form_state);
        }
        break;
      case 'exposed_form_options':
        $plugin = $this->getPlugin('exposed_form');
        if ($plugin) {
          $plugin->validateOptionsForm($form['exposed_form_options'], $form_state);
        }
        break;
      case 'pager_options':
        $plugin = $this->getPlugin('pager');
        if ($plugin) {
          $plugin->validateOptionsForm($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->validateOptionsForm($form, $form_state);
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, &$form_state) {
    // Not sure I like this being here, but it seems (?) like a logical place.
    $cache_plugin = $this->getPlugin('cache');
    if ($cache_plugin) {
      $cache_plugin->cache_flush();
    }

    $section = $form_state['section'];
    switch ($section) {
      case 'display_id':
        if (isset($form_state['values']['display_id'])) {
          $this->display['new_id'] = $form_state['values']['display_id'];
        }
        break;
      case 'display_title':
        $this->display['display_title'] = $form_state['values']['display_title'];
        $this->setOption('display_description', $form_state['values']['display_description']);
        break;
      case 'access':
        $access = $this->getOption('access');
        if ($access['type'] != $form_state['values']['access']['type']) {
          $plugin = views_get_plugin('access', $form_state['values']['access']['type']);
          if ($plugin) {
            $access = array('type' => $form_state['values']['access']['type']);
            $this->setOption('access', $access);
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('access_options'));
            }
          }
        }

        break;
      case 'access_options':
        $plugin = $this->getPlugin('access');
        if ($plugin) {
          $access = $this->getOption('access');
          $plugin->submitOptionsForm($form['access_options'], $form_state);
          $access['options'] = $form_state['values'][$section];
          $this->setOption('access', $access);
        }
        break;
      case 'cache':
        $cache = $this->getOption('cache');
        if ($cache['type'] != $form_state['values']['cache']['type']) {
          $plugin = views_get_plugin('cache', $form_state['values']['cache']['type']);
          if ($plugin) {
            $cache = array('type' => $form_state['values']['cache']['type']);
            $this->setOption('cache', $cache);
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('cache_options'));
            }
          }
        }

        break;
      case 'cache_options':
        $plugin = $this->getPlugin('cache');
        if ($plugin) {
          $cache = $this->getOption('cache');
          $plugin->submitOptionsForm($form['cache_options'], $form_state);
          $cache['options'] = $form_state['values'][$section];
          $this->setOption('cache', $cache);
        }
        break;
      case 'query':
        $plugin = $this->getPlugin('query');
        if ($plugin) {
          $plugin->submitOptionsForm($form['query']['options'], $form_state);
          $this->setOption('query', $form_state['values'][$section]);
        }
        break;

      case 'link_display':
        $this->setOption('link_url', $form_state['values']['link_url']);
      case 'title':
      case 'css_class':
      case 'display_comment':
        $this->setOption($section, $form_state['values'][$section]);
        break;
      case 'field_language':
        $this->setOption('field_langcode', $form_state['values']['field_langcode']);
        $this->setOption('field_langcode_add_to_query', $form_state['values']['field_langcode_add_to_query']);
        break;
      case 'use_ajax':
      case 'hide_attachment_summary':
      case 'hide_admin_links':
        $this->setOption($section, (bool)$form_state['values'][$section]);
        break;
      case 'use_more':
        $this->setOption($section, intval($form_state['values'][$section]));
        $this->setOption('use_more_always', !intval($form_state['values']['use_more_always']));
        $this->setOption('use_more_text', $form_state['values']['use_more_text']);
      case 'distinct':
        $this->setOption($section, $form_state['values'][$section]);
        break;
      case 'group_by':
        $this->setOption($section, $form_state['values'][$section]);
        break;
      case 'row':
        // This if prevents resetting options to default if they don't change
        // the plugin.
        $row = $this->getOption('row');
        if ($row['type'] != $form_state['values'][$section]) {
          $plugin = views_get_plugin('row', $form_state['values'][$section]);
          if ($plugin) {
            $row = array('type' => $form_state['values'][$section]);
            $this->setOption($section, $row);

            // send ajax form to options page if we use it.
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('row_options'));
            }
          }
        }
        break;
      case 'style':
        // This if prevents resetting options to default if they don't change
        // the plugin.
        $style = $this->getOption('style');
        if ($style['type'] != $form_state['values'][$section]) {
          $plugin = views_get_plugin('style', $form_state['values'][$section]);
          if ($plugin) {
            $row = array('type' => $form_state['values'][$section]);
            $this->setOption($section, $row);
            // send ajax form to options page if we use it.
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('style_options'));
            }
          }
        }
        break;
      case 'style_options':
        $plugin = $this->getPlugin('style');
        if ($plugin) {
          $style = $this->getOption('style');
          $plugin->submitOptionsForm($form['style_options'], $form_state);
          $style['options'] = $form_state['values'][$section];
          $this->setOption('style', $style);
        }
        break;
      case 'row_options':
        $plugin = $this->getPlugin('row');
        if ($plugin) {
          $row = $this->getOption('row');
          $plugin->submitOptionsForm($form['row_options'], $form_state);
          $row['options'] = $form_state['values'][$section];
          $this->setOption('row', $row);
        }
        break;
      case 'exposed_block':
        $this->setOption($section, (bool) $form_state['values'][$section]);
        break;
      case 'exposed_form':
        $exposed_form = $this->getOption('exposed_form');
        if ($exposed_form['type'] != $form_state['values']['exposed_form']['type']) {
          $plugin = views_get_plugin('exposed_form', $form_state['values']['exposed_form']['type']);
          if ($plugin) {
            $exposed_form = array('type' => $form_state['values']['exposed_form']['type'], 'options' => array());
            $this->setOption('exposed_form', $exposed_form);
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('exposed_form_options'));
            }
          }
        }

        break;
      case 'exposed_form_options':
        $plugin = $this->getPlugin('exposed_form');
        if ($plugin) {
          $exposed_form = $this->getOption('exposed_form');
          $plugin->submitOptionsForm($form['exposed_form_options'], $form_state);
          $exposed_form['options'] = $form_state['values'][$section];
          $this->setOption('exposed_form', $exposed_form);
        }
        break;
      case 'pager':
        $pager = $this->getOption('pager');
        if ($pager['type'] != $form_state['values']['pager']['type']) {
          $plugin = views_get_plugin('pager', $form_state['values']['pager']['type']);
          if ($plugin) {
            // Because pagers have very similar options, let's allow pagers to
            // try to carry the options over.
            $plugin->init($this->view, $this->display, $pager['options']);

            $pager = array('type' => $form_state['values']['pager']['type'], 'options' => $plugin->options);
            $this->setOption('pager', $pager);
            if ($plugin->usesOptions()) {
              $form_state['view']->addFormToStack('display', $this->display['id'], array('pager_options'));
            }
          }
        }

        break;
      case 'pager_options':
        $plugin = $this->getPlugin('pager');
        if ($plugin) {
          $pager = $this->getOption('pager');
          $plugin->submitOptionsForm($form['pager_options'], $form_state);
          $pager['options'] = $form_state['values'][$section];
          $this->setOption('pager', $pager);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->submitOptionsForm($form, $form_state);
    }
  }

  /**
   * If override/revert was clicked, perform the proper toggle.
   */
  public function optionsOverride($form, &$form_state) {
    $this->setOverride($form_state['section']);
  }

  /**
   * Flip the override setting for the given section.
   *
   * @param string $section
   *   Which option should be marked as overridden, for example "filters".
   * @param bool $new_state
   *   Select the new state of the option.
   *     - TRUE: Revert to default.
   *     - FALSE: Mark it as overridden.
   */
  public function setOverride($section, $new_state = NULL) {
    $options = $this->defaultableSections($section);
    if (!$options) {
      return;
    }

    if (!isset($new_state)) {
      $new_state = empty($this->options['defaults'][$section]);
    }

    // For each option that is part of this group, fix our settings.
    foreach ($options as $option) {
      if ($new_state) {
        // Revert to defaults.
        unset($this->options[$option]);
        unset($this->display['display_options'][$option]);
      }
      else {
        // copy existing values into our display.
        $this->options[$option] = $this->getOption($option);
        $this->display['display_options'][$option] = $this->options[$option];
      }
      $this->options['defaults'][$option] = $new_state;
      $this->display['display_options']['defaults'][$option] = $new_state;
    }
  }

  /**
   * Inject anything into the query that the display handler needs.
   */
  public function query() {
    foreach ($this->extender as $extender) {
      $extender->query();
    }
  }

  /**
   * Not all display plugins will support filtering
   *
   * @todo this doesn't seems to be used
   */
  public function renderFilters() { }

  /**
   * Not all display plugins will suppert pager rendering.
   */
  public function renderPager() {
    return TRUE;
  }

  /**
   * Render the 'more' link
   */
  public function renderMoreLink() {
    if ($this->usesMore() && ($this->useMoreAlways() || (!empty($this->view->pager) && $this->view->pager->has_more_records()))) {
      $path = $this->getPath();

      if ($this->getOption('link_display') == 'custom_url' && $override_path = $this->getOption('link_url')) {
        $tokens = $this->getArgumentsTokens();
        $path = strtr($override_path, $tokens);
      }

      if ($path) {
        if (empty($override_path)) {
          $path = $this->view->getUrl(NULL, $path);
        }
        $url_options = array();
        if (!empty($this->view->exposed_raw_input)) {
          $url_options['query'] = $this->view->exposed_raw_input;
        }
        $theme = views_theme_functions('views_more', $this->view, $this->view->display_handler->display);
        $path = check_url(url($path, $url_options));

        return theme($theme, array('more_url' => $path, 'link_text' => check_plain($this->useMoreText()), 'view' => $this->view));
      }
    }
  }


  /**
   * Legacy functions.
   */

  /**
   * Render the header of the view.
   */
  public function renderHeader() {
    $empty = empty($this->view->result);
    return $this->renderArea('header', $empty);
  }

  /**
   * Render the footer of the view.
   */
  public function renderFooter() {
    $empty = empty($this->view->result);
    return $this->renderArea('footer', $empty);
  }

  public function renderEmpty() {
    return $this->renderArea('empty', TRUE);
  }

  /**
   * If this display creates a block, implement one of these.
   */
  public function hookBlockList($delta = 0, $edit = array()) { return array(); }

  /**
   * If this display creates a page with a menu item, implement it here.
   */
  public function hookMenu() { return array(); }

  /**
   * Render this display.
   */
  public function render() {
    $element = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
    );
    $element['#attached'] = &$this->view->element['#attached'];

    return $element;
  }

  public function renderArea($area, $empty = FALSE) {
    $return = '';
    foreach ($this->getHandlers($area) as $area) {
      $return .= $area->render($empty);
    }
    return $return;
  }


  /**
   * Determine if the user has access to this display of the view.
   */
  public function access($account = NULL) {
    if (!isset($account)) {
      global $user;
      $account = $user;
    }

    // Full override.
    if (user_access('access all views', $account)) {
      return TRUE;
    }

    $plugin = $this->getPlugin('access');
    if ($plugin) {
      return $plugin->access($account);
    }

    // fallback to all access if no plugin.
    return TRUE;
  }

  /**
   * Set up any variables on the view prior to execution. These are separated
   * from execute because they are extremely common and unlikely to be
   * overridden on an individual display.
   */
  public function preExecute() {
    $this->view->setUseAJAX($this->isAJAXEnabled());
    if ($this->usesMore() && !$this->useMoreAlways()) {
      $this->view->get_total_rows = TRUE;
    }
    $this->view->initHandlers();
    if ($this->usesExposed()) {
      $exposed_form = $this->getPlugin('exposed_form');
      $exposed_form->pre_execute();
    }

    foreach ($this->extender as $extender) {
      $extender->pre_execute();
    }

    if ($this->getOption('hide_admin_links')) {
      $this->view->hide_admin_links = TRUE;
    }
  }

  /**
   * When used externally, this is how a view gets run and returns
   * data in the format required.
   *
   * The base class cannot be executed.
   */
  public function execute() { }

  /**
   * Fully render the display for the purposes of a live preview or
   * some other AJAXy reason.
   */
  function preview() {
    $element = $this->view->render();
    return drupal_render($element);
  }

  /**
   * Displays can require a certain type of style plugin. By default, they will
   * be 'normal'.
   */
  protected function getStyleType() { return 'normal'; }

  /**
   * Make sure the display and all associated handlers are valid.
   *
   * @return
   *   Empty array if the display is valid; an array of error strings if it is not.
   */
  public function validate() {
    $errors = array();
    // Make sure displays that use fields HAVE fields.
    if ($this->usesFields()) {
      $fields = FALSE;
      foreach ($this->getHandlers('field') as $field) {
        if (empty($field->options['exclude'])) {
          $fields = TRUE;
        }
      }

      if (!$fields) {
        $errors[] = t('Display "@display" uses fields but there are none defined for it or all are excluded.', array('@display' => $this->display['display_title']));
      }
    }

    if ($this->hasPath() && !$this->getOption('path')) {
      $errors[] = t('Display "@display" uses a path but the path is undefined.', array('@display' => $this->display['display_title']));
    }

    // Validate style plugin
    $style = $this->getPlugin('style');
    if (empty($style)) {
      $errors[] = t('Display "@display" has an invalid style plugin.', array('@display' => $this->display['display_title']));
    }
    else {
      $result = $style->validate();
      if (!empty($result) && is_array($result)) {
        $errors = array_merge($errors, $result);
      }
    }

    // Validate query plugin.
    $query = $this->getPlugin('query');
    $result = $query->validate();
    if (!empty($result) && is_array($result)) {
      $errors = array_merge($errors, $result);
    }

    // Validate handlers
    foreach (ViewExecutable::viewsHandlerTypes() as $type => $info) {
      foreach ($this->getHandlers($type) as $handler) {
        $result = $handler->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }

    return $errors;
  }

  /**
   * Check if the provided identifier is unique.
   *
   * @param string $id
   *   The id of the handler which is checked.
   * @param string $identifier
   *   The actual get identifier configured in the exposed settings.
   *
   * @return bool
   *   Returns whether the identifier is unique on all handlers.
   *
   */
  public function isIdentifierUnique($id, $identifier) {
    foreach (ViewExecutable::viewsHandlerTypes() as $type => $info) {
      foreach ($this->getHandlers($type) as $key => $handler) {
        if ($handler->canExpose() && $handler->isExposed()) {
          if ($handler->isAGroup()) {
            if ($id != $key && $identifier == $handler->options['group_info']['identifier']) {
              return FALSE;
            }
          }
          else {
            if ($id != $key && $identifier == $handler->options['expose']['identifier']) {
              return FALSE;
            }
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Provide the block system with any exposed widget blocks for this display.
   */
  public function getSpecialBlocks() {
    $blocks = array();

    if ($this->usesExposedFormInBlock()) {
      $delta = '-exp-' . $this->view->storage->get('name') . '-' . $this->display['id'];
      $desc = t('Exposed form: @view-@display_id', array('@view' => $this->view->storage->get('name'), '@display_id' => $this->display['id']));

      $blocks[$delta] = array(
        'info' => $desc,
        'cache' => DRUPAL_NO_CACHE,
      );
    }

    return $blocks;
  }

  /**
   * Render any special blocks provided for this display.
   */
  public function viewSpecialBlocks($type) {
    if ($type == '-exp') {
      // avoid interfering with the admin forms.
      if (arg(0) == 'admin' && arg(1) == 'structure' && arg(2) == 'views') {
        return;
      }
      $this->view->initHandlers();

      if ($this->usesExposed() && $this->getOption('exposed_block')) {
        $exposed_form = $this->getPlugin('exposed_form');
        return array(
          'content' => $exposed_form->render_exposed_form(TRUE),
        );
      }
    }
  }

  /**
   * Provide some helpful text for the arguments.
   * The result should contain of an array with
   *   - filter value present: The title of the fieldset in the argument
   *     where you can configure what should be done with a given argument.
   *   - filter value not present: The tiel of the fieldset in the argument
   *     where you can configure what should be done if the argument does not
   *     exist.
   *   - description: A description about how arguments comes to the display.
   *     For example blocks don't get it from url.
   */
  public function getArgumentText() {
    return array(
      'filter value not present' => t('When the filter value is <em>NOT</em> available'),
      'filter value present' => t('When the filter value <em>IS</em> available or a default is provided'),
      'description' => t("This display does not have a source for contextual filters, so no contextual filter value will be available unless you select 'Provide default'."),
    );
  }

  /**
   * Provide some helpful text for pagers.
   *
   * The result should contain of an array within
   *   - items per page title
   */
  public function getPagerText() {
    return array(
      'items per page title' => t('Items to display'),
      'items per page description' => t('The number of items to display. Enter 0 for no limit.')
    );
  }

}

/**
 * @}
 */
