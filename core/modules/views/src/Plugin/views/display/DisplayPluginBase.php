<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\DisplayPluginBase.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Url;
use Drupal\views\Form\ViewsForm;
use Drupal\views\Plugin\CacheablePluginInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Views;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException as DependencyInjectionRuntimeException;

/**
 * @defgroup views_display_plugins Views display plugins
 * @{
 * Plugins to handle the overall display of views.
 *
 * Display plugins are responsible for controlling where a view is rendered;
 * that is, how it is exposed to other parts of Drupal. 'Page' and 'block' are
 * the most commonly used display plugins. Each view also has a 'master' (or
 * 'default') display that includes information shared between all its
 * displays (see \Drupal\views\Plugin\views\display\DefaultDisplay).
 *
 * Display plugins extend \Drupal\views\Plugin\views\display\DisplayPluginBase.
 * They must be annotated with \Drupal\views\Annotation\ViewsDisplay
 * annotation, and they must be in namespace directory Plugin\views\display.
 *
 * @ingroup views_plugins
 *
 * @see plugin_api
 * @see views_display_extender_plugins
 */

/**
 * Base class for views display plugins.
 */
abstract class DisplayPluginBase extends PluginBase {

  /**
   * The top object of a view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  var $view = NULL;

  /**
   * An array of instantiated handlers used in this display.
   *
   * @var \Drupal\views\Plugin\views\ViewsHandlerInterface[]
   */
   public $handlers = [];

  /**
   * An array of instantiated plugins used in this display.
   *
   * @var \Drupal\views\Plugin\views\ViewsPluginInterface[]
   */
  protected $plugins = array();

  /**
   * Stores all available display extenders.
   *
   * @var \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase[]
   */
  protected $extenders = [];

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

  /**
   * Static cache for unpackOptions, but not if we are in the UI.
   *
   * @var array
   */
  protected static $unpackOptions = array();

  /**
   * Constructs a new DisplayPluginBase object.
   *
   * Because DisplayPluginBase::initDisplay() takes the display configuration by
   * reference and handles it differently than usual plugin configuration, pass
   * an empty array of configuration to the parent. This prevents our
   * configuration from being duplicated.
   *
   * @todo Replace DisplayPluginBase::$display with
   *   DisplayPluginBase::$configuration to standardize with other plugins.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct(array(), $plugin_id, $plugin_definition);
  }

  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL) {
    $this->view = $view;

    // Load extenders as soon as possible.
    $display['display_options'] += ['display_extenders' => []];
    $this->extenders = array();
    if ($extenders = Views::getEnabledDisplayExtenders()) {
      $manager = Views::pluginManager('display_extender');
      $display_extender_options = $display['display_options']['display_extenders'];
      foreach ($extenders as $extender) {
        /** @var \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase $plugin */
        if ($plugin = $manager->createInstance($extender)) {
          $extender_options = isset($display_extender_options[$plugin->getPluginId()]) ? $display_extender_options[$plugin->getPluginId()] : [];
          $plugin->init($this->view, $this, $extender_options);
          $this->extenders[$extender] = $plugin;
        }
      }
    }


    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->display = &$display;

    // Track changes that the user should know about.
    $changed = FALSE;

    // Make some modifications:
    if (!isset($options) && isset($display['display_options'])) {
      $options = $display['display_options'];
    }

    if ($this->isDefaultDisplay() && isset($options['defaults'])) {
      unset($options['defaults']);
    }

    $skip_cache = \Drupal::config('views.settings')->get('skip_cache');

    if (empty($view->editing) || !$skip_cache) {
      $cid = 'views:unpack_options:' . hash('sha256', serialize(array($this->options, $options))) . ':' . \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (empty(static::$unpackOptions[$cid])) {
        $cache = \Drupal::cache('data')->get($cid);
        if (!empty($cache->data)) {
          $this->options = $cache->data;
        }
        else {
          $this->unpackOptions($this->options, $options);
          $id = $this->view->storage->id();
          \Drupal::cache('data')->set($cid, $this->options, Cache::PERMANENT, array('extension', 'extension:views', 'view:' . $id));
        }
        static::$unpackOptions[$cid] = $this->options;
      }
      else {
        $this->options = static::$unpackOptions[$cid];
      }
    }
    else {
      $this->unpackOptions($this->options, $options);
    }

    // Convert the field_langcode and field_language_add_to_query settings.
    $field_langcode = $this->getOption('field_langcode');
    $field_language_add_to_query = $this->getOption('field_language_add_to_query');
    if (isset($field_langcode)) {
      $this->setOption('field_langcode', $field_langcode);
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

    foreach ($this->extenders as $extender) {
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
        foreach ($this->view->$type as $handler) {
          if ($handler->canExpose() && $handler->isExposed()) {
            // one is all we need; if we find it, return true.
            $this->has_exposed = TRUE;
            return TRUE;
          }
        }
      }
      $pager = $this->getPlugin('pager');
      if (isset($pager) && $pager->usesExposed()) {
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
  public function ajaxEnabled() {
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
        return $pager->usePager();
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
      return $this->getOption('use_more_always');
    }
    return FALSE;
  }

  /**
   * Does the display have custom link text?
   */
  public function useMoreText() {
    if ($this->usesMore()) {
      return $this->getOption('use_more_text');
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
      foreach ($this->view->argument as $argument) {
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
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The views executable.
   * @param string $display_id
   *   The display to attach to.
   * @param array $build
   *   The parent view render array.
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) { }

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
      'show_admin_links' => array('show_admin_links'),
      'group_by' => array('group_by'),
      'query' => array('query'),
      'use_more' => array('use_more', 'use_more_always', 'use_more_text'),
      'use_more_always' => array('use_more', 'use_more_always', 'use_more_text'),
      'use_more_text' => array('use_more', 'use_more_always', 'use_more_text'),
      'link_display' => array('link_display', 'link_url'),

      // Force these to cascade properly.
      'style' => array('style', 'row'),
      'row' => array('style', 'row'),

      'pager' => array('pager'),

      'exposed_form' => array('exposed_form'),

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

    foreach ($this->extenders as $extender) {
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
          'show_admin_links' => TRUE,
          'pager' => TRUE,
          'use_more' => TRUE,
          'use_more_always' => TRUE,
          'use_more_text' => TRUE,
          'exposed_form' => TRUE,

          'link_display' => TRUE,
          'link_url' => TRUE,
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
      ),
      'enabled' => array(
        'default' => TRUE,
      ),
      'display_comment' => array(
        'default' => '',
      ),
      'css_class' => array(
        'default' => '',
      ),
      'display_description' => array(
        'default' => '',
      ),
      'use_ajax' => array(
        'default' => FALSE,
      ),
      'hide_attachment_summary' => array(
        'default' => FALSE,
      ),
      'show_admin_links' => array(
        'default' => TRUE,
      ),
      'use_more' => array(
        'default' => FALSE,
      ),
      'use_more_always' => array(
        'default' => TRUE,
      ),
      'use_more_text' => array(
        'default' => 'more',
      ),
      'link_display' => array(
        'default' => '',
      ),
      'link_url' => array(
        'default' => '',
      ),
      'group_by' => array(
        'default' => FALSE,
      ),
      'field_langcode' => array(
        'default' => '***LANGUAGE_language_content***',
      ),
      'field_langcode_add_to_query' => array(
        'default' => TRUE,
      ),
      'rendering_language' => array(
        'default' => 'translation_language_renderer',
      ),

      // These types are all plugins that can have individual settings
      // and therefore need special handling.
      'access' => array(
        'contains' => array(
          'type' => array('default' => 'none'),
          'options' => array('default' => array()),
        ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'cache' => array(
        'contains' => array(
          'type' => array('default' => 'none'),
          'options' => array('default' => array()),
        ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'query' => array(
        'contains' => array(
          'type' => array('default' => 'views_query'),
          'options' => array('default' => array()),
         ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'exposed_form' => array(
        'contains' => array(
          'type' => array('default' => 'basic'),
          'options' => array('default' => array()),
         ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'pager' => array(
        'contains' => array(
          'type' => array('default' => 'mini'),
          'options' => array('default' => array()),
         ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'style' => array(
        'contains' => array(
          'type' => array('default' => 'default'),
          'options' => array('default' => array()),
        ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),
      'row' => array(
        'contains' => array(
          'type' => array('default' => 'fields'),
          'options' => array('default' => array()),
        ),
        'merge_defaults' => array($this, 'mergePlugin'),
      ),

      'exposed_block' => array(
        'default' => FALSE,
      ),

      'header' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),
      'footer' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),
      'empty' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),

      // We want these to export last.
      // These are the 5 handler types.
      'relationships' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),
      'fields' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),
      'sorts' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
      ),
      'arguments' => array(
        'default' => array(),
        'merge_defaults' => array($this, 'mergeHandler'),
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
      $options['defaults']['default']['pager'] = FALSE;
      $options['pager']['contains']['type']['default'] = 'some';
    }

    if ($this->isDefaultDisplay()) {
      unset($options['defaults']);
    }

    $options['display_extenders'] = ['default' => []];
    // First allow display extenders to provide new options.
    foreach ($this->extenders as $extender_id => $extender) {
      $options['display_extenders']['contains'][$extender_id]['contains'] = $extender->defineOptions();
    }

    // Then allow display extenders to alter existing default values.
    foreach ($this->extenders as $extender) {
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
   * Find out all displays which are attached to this display.
   *
   * The method is just using the pure storage object to avoid loading of the
   * sub displays which would kill lazy loading.
   */
  public function getAttachedDisplays() {
    $current_display_id = $this->display['id'];
    $attached_displays = array();

    // Go through all displays and search displays which link to this one.
    foreach ($this->view->storage->get('display') as $display_id => $display) {
      if (isset($display['display_options']['displays'])) {
        $displays = $display['display_options']['displays'];
        if (isset($displays[$current_display_id])) {
          $attached_displays[] = $display_id;
        }
      }
    }

    return $attached_displays;
  }

  /**
   * Check to see which display to use when creating links within
   * a view using this display.
   */
  public function getLinkDisplay() {
    $display_id = $this->getOption('link_display');
    // If unknown, pick the first one.
    if (empty($display_id) || !$this->view->displayHandlers->has($display_id)) {
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
    if ($display_id && $this->view->displayHandlers->has($display_id) && is_object($this->view->displayHandlers->get($display_id))) {
      return $this->view->displayHandlers->get($display_id)->getPath();
    }
  }

  public function getUrl() {
    return $this->view->getUrl();
  }

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
   * @return \Drupal\views\Plugin\views\ViewsPluginInterface
   */
  public function getPlugin($type) {
    // Look up the plugin name to use for this instance.
    $options = $this->getOption($type);

    // Return now if no options have been loaded.
    if (empty($options) || !isset($options['type'])) {
      return;
    }

    // Query plugins allow specifying a specific query class per base table.
    if ($type == 'query') {
      $views_data = Views::viewsData()->get($this->view->storage->get('base_table'));
      $name = isset($views_data['table']['base']['query_id']) ? $views_data['table']['base']['query_id'] : 'views_query';
    }
    else {
      $name = $options['type'];
    }

    // Plugin instances are stored on the display for re-use.
    if (!isset($this->plugins[$type][$name])) {
      $plugin = Views::pluginManager($type)->createInstance($name);

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
   *
   * @return \Drupal\views\Plugin\views\ViewsHandlerInterface[]
   */
  public function &getHandlers($type) {
    if (!isset($this->handlers[$type])) {
      $this->handlers[$type] = array();
      $types = ViewExecutable::getHandlerTypes();
      $plural = $types[$type]['plural'];

      // Cast to an array so that if the display does not have any handlers of
      // this type there is no PHP error.
      foreach ((array) $this->getOption($plural) as $id => $info) {
        // If this is during form submission and there are temporary options
        // which can only appear if the view is in the edit cache, use those
        // options instead. This is used for AJAX multi-step stuff.
        if ($this->view->getRequest()->request->get('form_id') && isset($this->view->temporary_options[$type][$id])) {
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
          $aggregate = $this->view->query->getAggregationInfo();
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

        if ($handler = Views::handlerManager($handler_type)->getHandler($info, $override)) {
          // Special override for area types so they know where they come from.
          if ($handler instanceof AreaPluginBase) {
            $handler->areaType = $type;
          }

          $handler->init($this->view, $this, $info);
          $this->handlers[$type][$id] = &$handler;
        }

        // Prevent reference problems.
        unset($handler);
      }
    }

    return $this->handlers[$type];
  }

  /**
   * Retrieves a list of fields for the current display.
   *
   * This also takes into account any associated relationships, if they exist.
   *
   * @param bool $groupable_only
   *   (optional) TRUE to only return an array of field labels from handlers
   *   that support the useStringGroupBy method, defaults to FALSE.
   *
   * @return array
   *   An array of applicable field options, keyed by ID.
   */
  public function getFieldLabels($groupable_only = FALSE) {
    $options = array();
    foreach ($this->getHandlers('relationship') as $relationship => $handler) {
      $relationships[$relationship] = $handler->adminLabel();
    }

    foreach ($this->getHandlers('field') as $id => $handler) {
      if ($groupable_only && !$handler->useStringGroupBy()) {
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
      $text = $this->t('Broken field');
    }

    if (empty($title)) {
      $title = $text;
    }

    return \Drupal::l($text, new Url('views_ui.form_display', ['js' => 'nojs', 'view' => $this->view->storage->id(), 'display_id' => $this->display['id'], 'type' => $section], array('attributes' => array('class' => array('views-ajax-link', $class), 'title' => $title, 'id' => drupal_html_id('views-' . $this->display['id'] . '-' . $section)), 'html' => TRUE)));
  }

  /**
   * Returns to tokens for arguments.
   *
   * This function is similar to views_handler_field::getRenderTokens()
   * but without fields tokens.
   */
  public function getArgumentsTokens() {
    $tokens = array();
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }

    // Add tokens for every argument (contextual filter) and path arg.
    $handlers = count($this->view->display_handler->getHandlers('argument'));
    for ($count = 1; $count <= $handlers; $count++) {
      if (!isset($tokens["%$count"])) {
        $tokens["%$count"] = '';
      }
       // Use strip tags as there should never be HTML in the path.
       // However, we need to preserve special characters like " that
       // were removed by String::checkPlain().
      $tokens["!$count"] = isset($this->view->args[$count - 1]) ? strip_tags(String::decodeEntities($this->view->args[$count - 1])) : '';
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
        'title' => $this->t('Title'),
        'column' => 'first',
      ),
      'format' => array(
        'title' => $this->t('Format'),
        'column' => 'first',
      ),
      'filters' => array(
        'title' => $this->t('Filters'),
        'column' => 'first',
      ),
      'fields' => array(
        'title' => $this->t('Fields'),
        'column' => 'first',
      ),
      'pager' => array(
        'title' => $this->t('Pager'),
        'column' => 'second',
      ),
      'language' => array(
        'title' => $this->t('Language'),
        'column' => 'second',
      ),
      'exposed' => array(
        'title' => $this->t('Exposed form'),
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
        'title' => $this->t('Other'),
        'column' => 'third',
        'build' => array(
          '#weight' => 2,
        ),
      ),
    );

    if ($this->display['id'] != 'default') {
      $options['display_id'] = array(
        'category' => 'other',
        'title' => $this->t('Machine Name'),
        'value' => !empty($this->display['new_id']) ? String::checkPlain($this->display['new_id']) : String::checkPlain($this->display['id']),
        'desc' => $this->t('Change the machine name of this display.'),
      );
    }

    $display_comment = String::checkPlain(Unicode::substr($this->getOption('display_comment'), 0, 10));
    $options['display_comment'] = array(
      'category' => 'other',
      'title' => $this->t('Administrative comment'),
      'value' => !empty($display_comment) ? $display_comment : $this->t('None'),
      'desc' => $this->t('Comment or document this display.'),
    );

    $title = strip_tags($this->getOption('title'));
    if (!$title) {
      $title = $this->t('None');
    }

    $options['title'] = array(
      'category' => 'title',
      'title' => $this->t('Title'),
      'value' => views_ui_truncate($title, 32),
      'desc' => $this->t('Change the title that this display will use.'),
    );

    $style_plugin_instance = $this->getPlugin('style');
    $style_summary = empty($style_plugin_instance->definition['title']) ? $this->t('Missing style plugin') : $style_plugin_instance->summaryTitle();
    $style_title = empty($style_plugin_instance->definition['title']) ? $this->t('Missing style plugin') : $style_plugin_instance->pluginTitle();

    $options['style'] = array(
      'category' => 'format',
      'title' => $this->t('Format'),
      'value' => $style_title,
      'setting' => $style_summary,
      'desc' => $this->t('Change the way content is formatted.'),
    );

    // This adds a 'Settings' link to the style_options setting if the style has options.
    if ($style_plugin_instance->usesOptions()) {
      $options['style']['links']['style_options'] = $this->t('Change settings for this format');
    }

    if ($style_plugin_instance->usesRowPlugin()) {
      $row_plugin_instance = $this->getPlugin('row');
      $row_summary = empty($row_plugin_instance->definition['title']) ? $this->t('Missing row plugin') : $row_plugin_instance->summaryTitle();
      $row_title = empty($row_plugin_instance->definition['title']) ? $this->t('Missing row plugin') : $row_plugin_instance->pluginTitle();

      $options['row'] = array(
        'category' => 'format',
        'title' => $this->t('Show'),
        'value' => $row_title,
        'setting' => $row_summary,
        'desc' => $this->t('Change the way each row in the view is styled.'),
      );
      // This adds a 'Settings' link to the row_options setting if the row style has options.
      if ($row_plugin_instance->usesOptions()) {
        $options['row']['links']['row_options'] = $this->t('Change settings for this style');
      }
    }
    if ($this->usesAJAX()) {
      $options['use_ajax'] = array(
        'category' => 'other',
        'title' => $this->t('Use AJAX'),
        'value' => $this->getOption('use_ajax') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Change whether or not this display will use AJAX.'),
      );
    }
    if ($this->usesAttachments()) {
      $options['hide_attachment_summary'] = array(
        'category' => 'other',
        'title' => $this->t('Hide attachments in summary'),
        'value' => $this->getOption('hide_attachment_summary') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Change whether or not to display attachments when displaying a contextual filter summary.'),
      );
    }
    if (!isset($this->definition['contextual links locations']) || !empty($this->definition['contextual links locations'])) {
      $options['show_admin_links'] = array(
        'category' => 'other',
        'title' => $this->t('Contextual links'),
        'value' => $this->getOption('show_admin_links') ? $this->t('Shown') : $this->t('Hidden'),
        'desc' => $this->t('Change whether or not to display contextual links for this view.'),
      );
    }

    $pager_plugin = $this->getPlugin('pager');
    if (!$pager_plugin) {
      // default to the no pager plugin.
      $pager_plugin = Views::pluginManager('pager')->createInstance('none');
    }

    $pager_str = $pager_plugin->summaryTitle();

    $options['pager'] = array(
      'category' => 'pager',
      'title' => $this->t('Use pager'),
      'value' => $pager_plugin->pluginTitle(),
      'setting' => $pager_str,
      'desc' => $this->t("Change this display's pager setting."),
    );

    // If pagers aren't allowed, change the text of the item:
    if (!$this->usesPager()) {
      $options['pager']['title'] = $this->t('Items to display');
    }

    if ($pager_plugin->usesOptions()) {
      $options['pager']['links']['pager_options'] = $this->t('Change settings for this pager type.');
    }

    if ($this->usesMore()) {
      $options['use_more'] = array(
        'category' => 'pager',
        'title' => $this->t('More link'),
        'value' => $this->getOption('use_more') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Specify whether this display will provide a "more" link.'),
      );
    }

    $this->view->initQuery();
    if ($this->view->query->getAggregationInfo()) {
      $options['group_by'] = array(
        'category' => 'other',
        'title' => $this->t('Use aggregation'),
        'value' => $this->getOption('group_by') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Allow grouping and aggregation (calculation) of fields.'),
      );
    }

    $options['query'] = array(
      'category' => 'other',
      'title' => $this->t('Query settings'),
      'value' => $this->t('Settings'),
      'desc' => $this->t('Allow to set some advanced settings for the query plugin'),
    );

    if (\Drupal::languageManager()->isMultilingual() && $this->isBaseTableTranslatable()) {
      $rendering_language_options = $this->buildRenderingLanguageOptions();
      $options['rendering_language'] = array(
        'category' => 'language',
        'title' => $this->t('Entity Language'),
        'value' => $rendering_language_options[$this->getOption('rendering_language')],
      );
      $language_options = $this->listLanguages(LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED);
      $options['field_langcode'] = array(
        'category' => 'language',
        'title' => $this->t('Field Language'),
        'value' => $language_options[$this->getOption('field_langcode')],
        'desc' => $this->t('All fields that support translations will be displayed in the selected language.'),
      );
    }

    $access_plugin = $this->getPlugin('access');
    if (!$access_plugin) {
      // default to the no access control plugin.
      $access_plugin = Views::pluginManager('access')->createInstance('none');
    }

    $access_str = $access_plugin->summaryTitle();

    $options['access'] = array(
      'category' => 'access',
      'title' => $this->t('Access'),
      'value' => $access_plugin->pluginTitle(),
      'setting' => $access_str,
      'desc' => $this->t('Specify access control type for this display.'),
    );

    if ($access_plugin->usesOptions()) {
      $options['access']['links']['access_options'] = $this->t('Change settings for this access type.');
    }

    $cache_plugin = $this->getPlugin('cache');
    if (!$cache_plugin) {
      // default to the no cache control plugin.
      $cache_plugin = Views::pluginManager('cache')->createInstance('none');
    }

    $cache_str = $cache_plugin->summaryTitle();

    $options['cache'] = array(
      'category' => 'other',
      'title' => $this->t('Caching'),
      'value' => $cache_plugin->pluginTitle(),
      'setting' => $cache_str,
      'desc' => $this->t('Specify caching type for this display.'),
    );

    if ($cache_plugin->usesOptions()) {
      $options['cache']['links']['cache_options'] = $this->t('Change settings for this caching type.');
    }

    if ($access_plugin->usesOptions()) {
      $options['access']['links']['access_options'] = $this->t('Change settings for this access type.');
    }

    if ($this->usesLinkDisplay()) {
      $link_display_option = $this->getOption('link_display');
      $link_display = $this->t('None');

      if ($link_display_option == 'custom_url') {
        $link_display = $this->t('Custom URL');
      }
      elseif (!empty($link_display_option)) {
        $display_id = $this->getLinkDisplay();
        $displays = $this->view->storage->get('display');
        if (!empty($displays[$display_id])) {
          $link_display = String::checkPlain($displays[$display_id]['display_title']);
        }
      }

      $options['link_display'] = array(
        'category' => 'pager',
        'title' => $this->t('Link display'),
        'value' => $link_display,
        'desc' => $this->t('Specify which display or custom url this display will link to.'),
      );
    }

    if ($this->usesExposedFormInBlock()) {
      $options['exposed_block'] = array(
        'category' => 'exposed',
        'title' => $this->t('Exposed form in block'),
        'value' => $this->getOption('exposed_block') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Allow the exposed form to appear in a block instead of the view.'),
      );
    }

    $exposed_form_plugin = $this->getPlugin('exposed_form');
    if (!$exposed_form_plugin) {
      // default to the no cache control plugin.
      $exposed_form_plugin = Views::pluginManager('exposed_form')->createInstance('basic');
    }

    $exposed_form_str = $exposed_form_plugin->summaryTitle();

    $options['exposed_form'] = array(
      'category' => 'exposed',
      'title' => $this->t('Exposed form style'),
      'value' => $exposed_form_plugin->pluginTitle(),
      'setting' => $exposed_form_str,
      'desc' => $this->t('Select the kind of exposed filter to use.'),
    );

    if ($exposed_form_plugin->usesOptions()) {
      $options['exposed_form']['links']['exposed_form_options'] = $this->t('Exposed form settings for this exposed form style.');
    }

    $css_class = String::checkPlain(trim($this->getOption('css_class')));
    if (!$css_class) {
      $css_class = $this->t('None');
    }

    $options['css_class'] = array(
      'category' => 'other',
      'title' => $this->t('CSS class'),
      'value' => $css_class,
      'desc' => $this->t('Change the CSS class name(s) that will be added to this display.'),
    );

    foreach ($this->extenders as $extender) {
      $extender->optionsSummary($categories, $options);
    }
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    if ($this->defaultableSections($section)) {
      views_ui_standard_display_dropdown($form, $form_state, $section);
    }
    $form['#title'] = String::checkPlain($this->display['display_title']) . ': ';

    // Set the 'section' to hilite on the form.
    // If it's the item we're looking at is pulling from the default display,
    // reflect that. Don't use is_defaulted since we want it to show up even
    // on the default display.
    if (!empty($this->options['defaults'][$section])) {
      $form['#section'] = 'default-' . $section;
    }
    else {
      $form['#section'] = $this->display['id'] . '-' . $section;
    }

    switch ($section) {
      case 'display_id':
        $form['#title'] .= $this->t('The machine name of this display');
        $form['display_id'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Machine name of the display'),
          '#default_value' => !empty($this->display['new_id']) ? $this->display['new_id'] : $this->display['id'],
          '#required' => TRUE,
          '#size' => 64,
        );
        break;
      case 'display_title':
        $form['#title'] .= $this->t('The name and the description of this display');
        $form['display_title'] = array(
          '#title' => $this->t('Administrative name'),
          '#type' => 'textfield',
          '#default_value' => $this->display['display_title'],
        );
        $form['display_description'] = array(
          '#title' => $this->t('Administrative description'),
          '#type' => 'textfield',
          '#default_value' => $this->getOption('display_description'),
        );
        break;
      case 'display_comment':
        $form['#title'] .= $this->t('Administrative comment');
        $form['display_comment'] = array(
          '#type' => 'textarea',
          '#title' => $this->t('Administrative comment'),
          '#description' => $this->t('This description will only be seen within the administrative interface and can be used to document this display.'),
          '#default_value' => $this->getOption('display_comment'),
        );
        break;
      case 'title':
        $form['#title'] .= $this->t('The title of this view');
        $form['title'] = array(
          '#title' => $this->t('Title'),
          '#type' => 'textfield',
          '#description' => $this->t('This title will be displayed with the view, wherever titles are normally displayed; i.e, as the page title, block title, etc.'),
          '#default_value' => $this->getOption('title'),
          '#maxlength' => 255,
        );
        break;
      case 'css_class':
        $form['#title'] .= $this->t('CSS class');
        $form['css_class'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('CSS class name(s)'),
          '#description' => $this->t('Multiples classes should be separated by spaces.'),
          '#default_value' => $this->getOption('css_class'),
        );
        break;
      case 'use_ajax':
        $form['#title'] .= $this->t('Use AJAX when available to load this view');
        $form['use_ajax'] = array(
          '#description' => $this->t('When viewing a view, things like paging, table sorting, and exposed filters will not trigger a page refresh.'),
          '#type' => 'checkbox',
          '#title' => $this->t('Use AJAX'),
          '#default_value' => $this->getOption('use_ajax') ? 1 : 0,
        );
        break;
      case 'hide_attachment_summary':
        $form['#title'] .= $this->t('Hide attachments when displaying a contextual filter summary');
        $form['hide_attachment_summary'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Hide attachments in summary'),
          '#default_value' => $this->getOption('hide_attachment_summary') ? 1 : 0,
        );
        break;
      case 'show_admin_links':
        $form['#title'] .= $this->t('Show contextual links on this view.');
        $form['show_admin_links'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Show contextual links'),
          '#default_value' => $this->getOption('show_admin_links'),
        );
      break;
      case 'use_more':
        $form['#title'] .= $this->t('Add a more link to the bottom of the display.');
        $form['use_more'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Create more link'),
          '#description' => $this->t("This will add a more link to the bottom of this view, which will link to the page view. If you have more than one page view, the link will point to the display specified in 'Link display' section under pager. You can override the url at the link display setting."),
          '#default_value' => $this->getOption('use_more'),
        );
        $form['use_more_always'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Always display the more link'),
          '#description' => $this->t('Check this to display the more link even if there are no more items to display.'),
          '#default_value' => $this->getOption('use_more_always'),
          '#states' => array(
            'visible' => array(
              ':input[name="use_more"]' => array('checked' => TRUE),
            ),
          ),
        );
        $form['use_more_text'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('More link text'),
          '#description' => $this->t('The text to display for the more link.'),
          '#default_value' => $this->getOption('use_more_text'),
          '#states' => array(
            'visible' => array(
              ':input[name="use_more"]' => array('checked' => TRUE),
            ),
          ),
        );
        break;
      case 'group_by':
        $form['#title'] .= $this->t('Allow grouping and aggregation (calculation) of fields.');
        $form['group_by'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Aggregate'),
          '#description' => $this->t('If enabled, some fields may become unavailable. All fields that are selected for grouping will be collapsed to one record per distinct value. Other fields which are selected for aggregation will have the function run on them. For example, you can group nodes on title and count the number of nids in order to get a list of duplicate titles.'),
          '#default_value' => $this->getOption('group_by'),
        );
        break;
      case 'access':
        $form['#title'] .= $this->t('Access restrictions');
        $form['access'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $access = $this->getOption('access');
        $form['access']['type'] =  array(
          '#title' => $this->t('Access'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('access', $this->getType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $access['type'],
        );

        $access_plugin = $this->getPlugin('access');
        if ($access_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected access restriction.', array('!settings' => $this->optionLink(t('settings'), 'access_options'))),
            '#suffix' => '</div>',
          );
        }

        break;
      case 'access_options':
        $plugin = $this->getPlugin('access');
        $form['#title'] .= $this->t('Access options');
        if ($plugin) {
          $form['access_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['access_options'], $form_state);
        }
        break;
      case 'cache':
        $form['#title'] .= $this->t('Caching');
        $form['cache'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $cache = $this->getOption('cache');
        $form['cache']['type'] =  array(
          '#title' => $this->t('Caching'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('cache', $this->getType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $cache['type'],
        );

        $cache_plugin = $this->getPlugin('cache');
        if ($cache_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected cache mechanism.', array('!settings' => $this->optionLink(t('settings'), 'cache_options'))),
          );
        }
        break;
      case 'cache_options':
        $plugin = $this->getPlugin('cache');
        $form['#title'] .= $this->t('Caching options');
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

        $form['#title'] .= $this->t('Query options');
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
      case 'field_langcode':
        $form['#title'] .= $this->t('Field Language');
        if ($this->isBaseTableTranslatable()) {
          $languages = $this->listLanguages(LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED);

          $form['field_langcode'] = array(
            '#type' => 'select',
            '#title' => $this->t('Field Language'),
            '#description' => $this->t('All fields which support translations will be displayed in the selected language.'),
            '#options' => $languages,
            '#default_value' => $this->getOption('field_langcode'),
          );
          $form['field_langcode_add_to_query'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('When needed, add the field language condition to the query'),
            '#default_value' => $this->getOption('field_langcode_add_to_query'),
          );
        }
        else {
          $form['field_language']['#markup'] = $this->t("You don't have translatable entity types.");
        }
        break;
      case 'rendering_language':
        $form['#title'] .= $this->t('Entity Language');
        if ($this->isBaseTableTranslatable()) {
          $options = $this->buildRenderingLanguageOptions();
          $form['rendering_language'] = array(
            '#type' => 'select',
            '#options' => $options,
            '#title' => $this->t('Entity language'),
            '#default_value' => $this->getOption('rendering_language'),
          );
        }
        else {
          $form['rendering_language']['#markup'] = $this->t("You don't have translatable entity types.");
        }
        break;
      case 'style':
        $form['#title'] .= $this->t('How should this view be styled');
        $style_plugin = $this->getPlugin('style');
        $form['style'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $form['style']['type'] = array(
          '#title' => $this->t('Style'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('style', $this->getType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $style_plugin->definition['id'],
          '#description' => $this->t('If the style you choose has settings, be sure to click the settings button that will appear next to it in the View summary.'),
        );

        if ($style_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->optionLink(t('settings'), 'style_options'))),
          );
        }

        break;
      case 'style_options':
        $form['#title'] .= $this->t('Style options');
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
          $form['#title'] .= $this->t('Row style options');
        }
        $plugin = $this->getPlugin(empty($style) ? 'row' : 'style', $name);
        if ($plugin) {
          $form[$section] = [
            '#tree' => TRUE,
          ];
          $plugin->buildOptionsForm($form[$section], $form_state);
        }
        break;
      case 'row':
        $form['#title'] .= $this->t('How should each row in this view be styled');
        $row_plugin_instance = $this->getPlugin('row');
        $form['row'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $form['row']['type'] = array(
          '#title' => $this->t('Row'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('row', $this->getType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $row_plugin_instance->definition['id'],
        );

        if ($row_plugin_instance->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected row style.', array('!settings' => $this->optionLink(t('settings'), 'row_options'))),
          );
        }

        break;
      case 'link_display':
        $form['#title'] .= $this->t('Which display to use for path');
        $options = array(FALSE => $this->t('None'), 'custom_url' => $this->t('Custom URL'));

        foreach ($this->view->storage->get('display') as $display_id => $display) {
          if ($this->view->displayHandlers->get($display_id)->hasPath()) {
            $options[$display_id] = $display['display_title'];
          }
        }

        $form['link_display'] = array(
          '#type' => 'radios',
          '#options' => $options,
          '#description' => $this->t("Which display to use to get this display's path for things like summary links, rss feed links, more links, etc."),
          '#default_value' => $this->getOption('link_display'),
        );

        $options = array();
        $count = 0; // This lets us prepare the key as we want it printed.
        foreach ($this->view->display_handler->getHandlers('argument') as $handler) {
          $options[t('Arguments')]['%' . ++$count] = $this->t('@argument title', array('@argument' => $handler->adminLabel()));
          $options[t('Arguments')]['!' . $count] = $this->t('@argument input', array('@argument' => $handler->adminLabel()));
        }

        // Default text.
        // We have some options, so make a list.
        $output = '';
        if (!empty($options)) {
          $output = $this->t('<p>The following tokens are available for this link.</p>');
          foreach (array_keys($options) as $type) {
            if (!empty($options[$type])) {
              $items = array();
              foreach ($options[$type] as $key => $value) {
                $items[] = $key . ' == ' . $value;
              }
              $item_list = array(
                '#theme' => 'item_list',
                '#items' => $items,
                '#list_type' => $type,
              );
              $output .= drupal_render($item_list);
            }
          }
        }

        $form['link_url'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Custom URL'),
          '#default_value' => $this->getOption('link_url'),
          '#description' => $this->t('A Drupal path or external URL the more link will point to. Note that this will override the link display setting above.') . $output,
          '#states' => array(
            'visible' => array(
              ':input[name="link_display"]' => array('value' => 'custom_url'),
            ),
          ),
        );
        break;
      case 'exposed_block':
        $form['#title'] .= $this->t('Put the exposed form in a block');
        $form['description'] = array(
          '#markup' => '<div class="description form-item">' . $this->t('If set, any exposed widgets will not appear with this view. Instead, a block will be made available to the Drupal block administration system, and the exposed form will appear there. Note that this block must be enabled manually, Views will not enable it for you.') . '</div>',
        );
        $form['exposed_block'] = array(
          '#type' => 'radios',
          '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
          '#default_value' => $this->getOption('exposed_block') ? 1 : 0,
        );
        break;
      case 'exposed_form':
        $form['#title'] .= $this->t('Exposed Form');
        $form['exposed_form'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $exposed_form = $this->getOption('exposed_form');
        $form['exposed_form']['type'] =  array(
          '#title' => $this->t('Exposed form'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('exposed_form', $this->getType(), array($this->view->storage->get('base_table'))),
          '#default_value' => $exposed_form['type'],
        );

        $exposed_form_plugin = $this->getPlugin('exposed_form');
        if ($exposed_form_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->optionLink(t('settings'), 'exposed_form_options'))),
          );
        }
        break;
      case 'exposed_form_options':
        $plugin = $this->getPlugin('exposed_form');
        $form['#title'] .= $this->t('Exposed form options');
        if ($plugin) {
          $form['exposed_form_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['exposed_form_options'], $form_state);
        }
        break;
      case 'pager':
        $form['#title'] .= $this->t('Select which pager, if any, to use for this view');
        $form['pager'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $pager = $this->getOption('pager');
        $form['pager']['type'] =  array(
          '#title' => $this->t('Pager'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('pager', !$this->usesPager() ? 'basic' : NULL, array($this->view->storage->get('base_table'))),
          '#default_value' => $pager['type'],
        );

        $pager_plugin = $this->getPlugin('pager');
        if ($pager_plugin->usesOptions()) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the !settings for the currently selected pager.', array('!settings' => $this->optionLink(t('settings'), 'pager_options'))),
          );
        }

        break;
      case 'pager_options':
        $plugin = $this->getPlugin('pager');
        $form['#title'] .= $this->t('Pager options');
        if ($plugin) {
          $form['pager_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->buildOptionsForm($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extenders as $extender) {
      $extender->buildOptionsForm($form, $form_state);
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    switch ($section) {
      case 'display_title':
        if ($form_state->isValueEmpty('display_title')) {
          $form_state->setError($form['display_title'], $this->t('Display title may not be empty.'));
        }
        break;
      case 'css_class':
        $css_class = $form_state->getValue('css_class');
        if (preg_match('/[^a-zA-Z0-9-_ ]/', $css_class)) {
          $form_state->setError($form['css_class'], $this->t('CSS classes must be alphanumeric or dashes only.'));
        }
      break;
      case 'display_id':
        if ($form_state->getValue('display_id')) {
          if (preg_match('/[^a-z0-9_]/', $form_state->getValue('display_id'))) {
            $form_state->setError($form['display_id'], $this->t('Display name must be letters, numbers, or underscores only.'));
          }

          foreach ($this->view->displayHandlers as $id => $display) {
            if ($id != $this->view->current_display && ($form_state->getValue('display_id') == $id || (isset($display->new_id) && $form_state->getValue('display_id') == $display->new_id))) {
              $form_state->setError($form['display_id'], $this->t('Display id should be unique.'));
            }
          }
        }
        break;
      case 'query':
        if ($this->view->query) {
          $this->view->query->validateOptionsForm($form['query'], $form_state);
        }
        break;
    }

    // Validate plugin options. Every section with "_options" in it, belongs to
    // a plugin type, like "style_options".
    if (strpos($section, '_options') !== FALSE) {
      $plugin_type = str_replace('_options', '', $section);
      // Load the plugin and let it handle the validation.
      if ($plugin = $this->getPlugin($plugin_type)) {
        $plugin->validateOptionsForm($form[$section], $form_state);
      }
    }

    foreach ($this->extenders as $extender) {
      $extender->validateOptionsForm($form, $form_state);
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Not sure I like this being here, but it seems (?) like a logical place.
    $cache_plugin = $this->getPlugin('cache');
    if ($cache_plugin) {
      $cache_plugin->cacheFlush();
    }

    $section = $form_state->get('section');
    switch ($section) {
      case 'display_id':
        if ($form_state->hasValue('display_id')) {
          $this->display['new_id'] = $form_state->getValue('display_id');
        }
        break;
      case 'display_title':
        $this->display['display_title'] = $form_state->getValue('display_title');
        $this->setOption('display_description', $form_state->getValue('display_description'));
        break;
      case 'query':
        $plugin = $this->getPlugin('query');
        if ($plugin) {
          $plugin->submitOptionsForm($form['query']['options'], $form_state);
          $this->setOption('query', $form_state->getValue($section));
        }
        break;

      case 'link_display':
        $this->setOption('link_url', $form_state->getValue('link_url'));
      case 'title':
      case 'css_class':
      case 'display_comment':
      case 'distinct':
      case 'group_by':
        $this->setOption($section, $form_state->getValue($section));
        break;
      case 'field_langcode':
        $this->setOption('field_langcode', $form_state->getValue('field_langcode'));
        $this->setOption('field_langcode_add_to_query', $form_state->getValue('field_langcode_add_to_query'));
        break;
      case 'rendering_language':
        $this->setOption('rendering_language', $form_state->getValue('rendering_language'));
        break;
      case 'use_ajax':
      case 'hide_attachment_summary':
      case 'show_admin_links':
      case 'exposed_block':
        $this->setOption($section, (bool) $form_state->getValue($section));
        break;
      case 'use_more':
        $this->setOption($section, intval($form_state->getValue($section)));
        $this->setOption('use_more_always', intval($form_state->getValue('use_more_always')));
        $this->setOption('use_more_text', $form_state->getValue('use_more_text'));
        break;

      case 'access':
      case 'cache':
      case 'exposed_form':
      case 'pager':
      case 'row':
      case 'style':
        $plugin_type = $section;
        $plugin_options = $this->getOption($plugin_type);
        $type = $form_state->getValue(array($plugin_type, 'type'));
        if ($plugin_options['type'] != $type) {
          /** @var \Drupal\views\Plugin\views\ViewsPluginInterface $plugin */
          $plugin = Views::pluginManager($plugin_type)->createInstance($type);
          if ($plugin) {
            $plugin->init($this->view, $this, $plugin_options['options']);
            $plugin_options = array(
              'type' => $type,
              'options' => $plugin->options,
            );
            $plugin->filterByDefinedOptions($plugin_options['options']);
            $this->setOption($plugin_type, $plugin_options);
            if ($plugin->usesOptions()) {
              $form_state->get('view')->addFormToStack('display', $this->display['id'], $plugin_type . '_options');
            }
          }
        }
        break;

      case 'access_options':
      case 'cache_options':
      case 'exposed_form_options':
      case 'pager_options':
      case 'row_options':
      case 'style_options':
        // Submit plugin options. Every section with "_options" in it, belongs to
        // a plugin type, like "style_options".
        $plugin_type = str_replace('_options', '', $section);
        if ($plugin = $this->getPlugin($plugin_type)) {
          $plugin_options = $this->getOption($plugin_type);
          $plugin->submitOptionsForm($form[$plugin_type . '_options'], $form_state);
          $plugin_options['options'] = $form_state->getValue($section);
          $this->setOption($plugin_type, $plugin_options);
        }
        break;
    }

    $extender_options = $this->getOption('display_extenders');
    foreach ($this->extenders as $extender) {
      $extender->submitOptionsForm($form, $form_state);

      $plugin_id = $extender->getPluginId();
      $extender_options[$plugin_id] = $extender->options;
    }
    $this->setOption('display_extenders', $extender_options);
  }

  /**
   * If override/revert was clicked, perform the proper toggle.
   */
  public function optionsOverride($form, FormStateInterface $form_state) {
    $this->setOverride($form_state->get('section'));
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
    foreach ($this->extenders as $extender) {
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
    if ($this->isMoreEnabled() && ($this->useMoreAlways() || (!empty($this->view->pager) && $this->view->pager->hasMoreRecords()))) {
      $path = $this->getPath();

      if ($this->getOption('link_display') == 'custom_url' && $override_path = $this->getOption('link_url')) {
        $tokens = $this->getArgumentsTokens();
        $path = $this->viewsTokenReplace($override_path, $tokens);
      }

      if ($path) {
        if (empty($override_path)) {
          $path = $this->view->getUrl(NULL, $path);
        }
        $url_options = array();
        if (!empty($this->view->exposed_raw_input)) {
          $url_options['query'] = $this->view->exposed_raw_input;
        }
        $theme = $this->view->buildThemeFunctions('views_more');
        $path = check_url(_url($path, $url_options));

        return array(
          '#theme' => $theme,
          '#more_url' => $path,
          '#link_text' => String::checkPlain($this->useMoreText()),
          '#view' => $this->view,
        );
      }
    }
  }

  /**
   * Gets menu links, if this display provides some.
   *
   * @return array
   *   The menu links registers for this display.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsMenuLink
   */
  public function getMenuLinks() {
    return array();
  }

  /**
   * Render this display.
   */
  public function render() {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : array();

    $element = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      // Assigned by reference so anything added in $element['#attached'] will
      // be available on the view.
      '#attached' => &$this->view->element['#attached'],
      '#pre_render' => [[$this, 'elementPreRender']],
      '#rows' => $rows,
    );

    return $element;
  }

  /**
   * #pre_render callback for view display rendering.
   *
   * @see self::render()
   *
   * @param array $element
   *   The element to #pre_render
   *
   * @return array
   *   The processed element.
   */
  public function elementPreRender(array $element) {
    $view = $element['#view'];
    $empty = empty($view->result);

    // Force a render array so CSS/JS can be attached.
    if (!is_array($element['#rows'])) {
      $element['#rows'] = array('#markup' => $element['#rows']);
    }

    $element['#header'] = $view->display_handler->renderArea('header', $empty);
    $element['#footer'] = $view->display_handler->renderArea('footer', $empty);
    $element['#empty'] = $empty ? $view->display_handler->renderArea('empty', $empty) : array();
    $element['#exposed'] = !empty($view->exposed_widgets) ? $view->exposed_widgets : array();
    $element['#more'] = $view->display_handler->renderMoreLink();
    $element['#feed_icons'] = !empty($view->feedIcons) ? $view->feedIcons : array();

    if ($view->display_handler->renderPager()) {
      $exposed_input = isset($view->exposed_raw_input) ? $view->exposed_raw_input : NULL;
      $element['#pager'] = $view->renderPager($exposed_input);
    }

    if (!empty($view->attachment_before)) {
      $element['#attachment_before'] = $view->attachment_before;
    }
    if (!empty($view->attachment_after)) {
      $element['#attachment_after'] = $view->attachment_after;
    }

    // If form fields were found in the view, reformat the view output as a form.
    if ($view->hasFormElements()) {
      // Only render row output if there are rows. Otherwise, render the empty
      // region.
      if (!empty($element['#rows'])) {
        $output = $element['#rows'];
      }
      else {
        $output = $element['#empty'];
      }

      $form_object = ViewsForm::create(\Drupal::getContainer(), $view->storage->id(), $view->current_display);
      $form = \Drupal::formBuilder()->getForm($form_object, $view, $output);
      // The form is requesting that all non-essential views elements be hidden,
      // usually because the rendered step is not a view result.
      if ($form['show_view_elements']['#value'] == FALSE) {
        $element['#header'] = array();
        $element['#exposed'] = array();
        $element['#pager'] = array();
        $element['#footer'] = array();
        $element['#more'] = array();
        $element['#feed_icons'] = array();
      }

      $element['#rows'] = $form;
    }

    return $element;
  }

  /**
   * Render one of the available areas.
   *
   * @param string $area
   *   Identifier of the specific area to render.
   * @param bool $empty
   *   (optional) Indicator whether or not the view result is empty. Defaults to
   *   FALSE
   *
   * @return array
   *   A render array for the given area.
   */
  public function renderArea($area, $empty = FALSE) {
    $return = array();
    foreach ($this->getHandlers($area) as $key => $area_handler) {
      $return[$key] = $area_handler->render($empty);
    }
    return $return;
  }


  /**
   * Determine if the user has access to this display of the view.
   */
  public function access(AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    $plugin = $this->getPlugin('access');
      /** @var \Drupal\views\Plugin\views\access\AccessPluginBase $plugin */
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
    $this->view->setAjaxEnabled($this->ajaxEnabled());
    if ($this->isMoreEnabled() && !$this->useMoreAlways()) {
      $this->view->get_total_rows = TRUE;
    }
    $this->view->initHandlers();
    if ($this->usesExposed()) {
      $exposed_form = $this->getPlugin('exposed_form');
      $exposed_form->preExecute();
    }

    foreach ($this->extenders as $extender) {
      $extender->preExecute();
    }
  }

  /**
   * Calculates the display's cache metadata by inspecting each handler/plugin.
   *
   * @return array
   *   Returns an array:
   *     - first value: (boolean) Whether the display is cacheable.
   *     - second value: (string[]) The cache contexts the display varies by.
   */
  public function calculateCacheMetadata () {
    $is_cacheable = TRUE;
    $cache_contexts = [];

    // Iterate over ordinary views plugins.
    foreach (Views::getPluginTypes('plugin') as $plugin_type) {
      $plugin = $this->getPlugin($plugin_type);
      if ($plugin instanceof CacheablePluginInterface) {
        $cache_contexts = array_merge($cache_contexts, $plugin->getCacheContexts());
        $is_cacheable &= $plugin->isCacheable();
      }
      else {
        $is_cacheable = FALSE;
      }
    }

    // Iterate over all handlers. Note that at least the argument handler will
    // need to ask all its subplugins.
    foreach (array_keys(Views::getHandlerTypes()) as $handler_type) {
      $handlers = $this->getHandlers($handler_type);
      foreach ($handlers as $handler) {
        if ($handler instanceof CacheablePluginInterface) {
          $cache_contexts = array_merge($cache_contexts, $handler->getCacheContexts());
          $is_cacheable &= $handler->isCacheable();
        }
      }
    }

    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
    if ($cache_plugin = $this->getPlugin('cache')) {
      $cache_plugin->alterCacheMetadata($is_cacheable, $cache_contexts);
    }

    return [$is_cacheable, $cache_contexts];
  }

  /**
   * When used externally, this is how a view gets run and returns
   * data in the format required.
   *
   * The base class cannot be executed.
   */
  public function execute() { }

  /**
   * Builds a renderable array of the view.
   *
   * Note: This does not yet contain the executed view, but just the loaded view
   * executable.
   *
   * @return array
   *   The render array of a view.
   */
  public function buildRenderable(array $args = []) {
    return [
      '#type' => 'view',
      '#name' => $this->view->storage->id(),
      '#display_id' => $this->display['id'],
      '#arguments' => $args,
      '#embed' => FALSE,
      '#pre_render' => [['\Drupal\views\Element\View', 'preRenderViewElement'], [$this, 'elementPreRender']],
      '#view' => $this->view,
    ];
  }

  /**
   * Fully render the display for the purposes of a live preview or
   * some other AJAXy reason.
   */
  function preview() {
    return $this->view->render();
  }

  /**
   * Returns the display type that this display requires.
   *
   * This can be used for filtering views plugins. E.g. if a plugin category of
   * 'foo' is specified, only plugins with no 'types' declared or 'types'
   * containing 'foo'. If you have a type of bar, this plugin will not be used.
   * This is applicable for style, row, access, cache, and exposed_form plugins.
   *
   * @return string
   *   The required display type. Defaults to 'normal'.
   *
   * @see \Drupal\views\Views::fetchPluginNames()
   */
  protected function getType() {
    return 'normal';
  }

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
        $errors[] = $this->t('Display "@display" uses fields but there are none defined for it or all are excluded.', array('@display' => $this->display['display_title']));
      }
    }

    if ($this->hasPath() && !$this->getOption('path')) {
      $errors[] = $this->t('Display "@display" uses a path but the path is undefined.', array('@display' => $this->display['display_title']));
    }

    // Validate style plugin
    $style = $this->getPlugin('style');
    if (empty($style)) {
      $errors[] = $this->t('Display "@display" has an invalid style plugin.', array('@display' => $this->display['display_title']));
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
    foreach (ViewExecutable::getHandlerTypes() as $type => $info) {
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
   * Reacts on adding a display.
   *
   * @see \Drupal\views\Entity\View::newDisplay()
   */
  public function newDisplay() {
  }

  /**
   * Reacts on deleting a display.
   */
  public function remove() {
    $menu_links = $this->getMenuLinks();
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    foreach ($menu_links as $menu_link_id => $menu_link) {
      $menu_link_manager->removeDefinition("views_view:$menu_link_id");
    }
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
    foreach (ViewExecutable::getHandlerTypes() as $type => $info) {
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
   * Is the output of the view empty.
   *
   * If a view has no result and neither the empty, nor the footer nor the header
   * does show anything return FALSE.
   *
   * @return bool
   *   Returns TRUE if the output is empty, else FALSE.
   */
  public function outputIsEmpty() {
    if (!empty($this->view->result)) {
      return FALSE;
    }

    // Check whether all of the area handlers are empty.
    foreach (array('empty', 'footer', 'header') as $type) {
      $handlers = $this->getHandlers($type);
      foreach ($handlers as $handler) {
        // If one is not empty, return FALSE now.
        if (!$handler->isEmpty()) {
          return FALSE;
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
      $delta = '-exp-' . $this->view->storage->id() . '-' . $this->display['id'];
      $desc = $this->t('Exposed form: @view-@display_id', array('@view' => $this->view->storage->id(), '@display_id' => $this->display['id']));

      $blocks[$delta] = array(
        'info' => $desc,
      );
    }

    return $blocks;
  }

  /**
   * Render the exposed form as block.
   *
   * @return string|null
   *  The rendered exposed form as string or NULL otherwise.
   */
  public function viewExposedFormBlocks() {
    // Avoid interfering with the admin forms.
    $route_name = \Drupal::routeMatch()->getRouteName();
    if (strpos($route_name, 'views_ui.') === 0) {
      return;
    }
    $this->view->initHandlers();

    if ($this->usesExposed() && $this->getOption('exposed_block')) {
      $exposed_form = $this->getPlugin('exposed_form');
      return $exposed_form->renderExposedForm(TRUE);
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
      'filter value not present' => $this->t('When the filter value is <em>NOT</em> available'),
      'filter value present' => $this->t('When the filter value <em>IS</em> available or a default is provided'),
      'description' => $this->t("This display does not have a source for contextual filters, so no contextual filter value will be available unless you select 'Provide default'."),
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
      'items per page title' => $this->t('Items to display'),
      'items per page description' => $this->t('Enter 0 for no limit.')
    );
  }

  /**
   * Merges default values for all plugin types.
   */
  public function mergeDefaults() {
    $defined_options = $this->defineOptions();

    // Build a map of plural => singular for handler types.
    $type_map = array();
    foreach (ViewExecutable::getHandlerTypes() as $type => $info) {
      $type_map[$info['plural']] = $type;
    }

    // Find all defined options, that have specified a merge_defaults callback.
    foreach ($defined_options as $type => $definition) {
      if (!isset($definition['merge_defaults']) || !is_callable($definition['merge_defaults'])) {
        continue;
      }
      // Switch the type to singular, if it's a plural handler.
      if (isset($type_map[$type])) {
        $type = $type_map[$type];
      }

      call_user_func($definition['merge_defaults'], $type);
    }
  }

  /**
   * Merges plugins default values.
   *
   * @param string $type
   *   The name of the plugin type option.
   */
  protected function mergePlugin($type) {
    if (($options = $this->getOption($type)) && isset($options['options'])) {
      $plugin = $this->getPlugin($type);
      $options['options'] = $options['options'] + $plugin->options;
      $this->setOption($type, $options);
    }
  }

  /**
   * Merges handlers default values.
   *
   * @param string $type
   *   The name of the handler type option.
   */
  protected function mergeHandler($type) {
    $types = ViewExecutable::getHandlerTypes();

    $options = $this->getOption($types[$type]['plural']);
    foreach ($this->getHandlers($type) as $id => $handler) {
      if (isset($options[$id])) {
        $options[$id] = $options[$id] + $handler->options;
      }
    }

    $this->setOption($types[$type]['plural'], $options);
  }

  /**
   * Gets the display extenders.
   *
   * @return \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase[]
   */
  public function getExtenders() {
    return $this->extenders;
  }

  /**
   * Returns the available rendering strategies for language-aware entities.
   *
   * @return array
   *   An array of available entity row renderers keyed by renderer identifiers.
   */
  protected function buildRenderingLanguageOptions() {
    // @todo Consider making these plugins. See https://drupal.org/node/2173811.
    return array(
      'current_language_renderer' => $this->t('Current language'),
      'default_language_renderer' => $this->t('Default language'),
      'translation_language_renderer' => $this->t('Translation language'),
    );
  }

  /**
   * Returns whether the base table is of a translatable entity type.
   *
   * @return bool
   *   TRUE if the base table is of a translatable entity type, FALSE otherwise.
   */
  protected function isBaseTableTranslatable() {
    $view_base_table = $this->view->storage->get('base_table');
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type) {
      if ($entity_type->isTranslatable() && $base_table = $entity_type->getBaseTable()) {
        if ($base_table === $view_base_table) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }
}

/**
 * @}
 */
