<?php

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Form\ViewsForm;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Views;

/**
 * Base class for views display plugins.
 */
abstract class DisplayPluginBase extends PluginBase implements DisplayPluginInterface, DependentPluginInterface {
  use PluginDependencyTrait;

  /**
   * The top object of a view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  public $view = NULL;

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
  protected $plugins = [];

  /**
   * Stores all available display extenders.
   *
   * @var \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase[]
   */
  protected $extenders = [];

  /**
   * {@inheritdoc}
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
  protected static $unpackOptions = [];

  /**
   * The display information coming directly from the view entity.
   *
   * @see \Drupal\views\Entity\View::getDisplay()
   *
   * @todo \Drupal\views\Entity\View::duplicateDisplayAsType directly access it.
   *
   * @var array
   */
  public $display;

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
    parent::__construct([], $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL) {
    $this->view = $view;

    // Load extenders as soon as possible.
    $display['display_options'] += ['display_extenders' => []];
    $this->extenders = [];
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

    if (!isset($options) && isset($display['display_options'])) {
      $options = $display['display_options'];
    }

    if ($this->isDefaultDisplay() && isset($options['defaults'])) {
      unset($options['defaults']);
    }

    $skip_cache = \Drupal::config('views.settings')->get('skip_cache');

    if (empty($view->editing) || !$skip_cache) {
      $cid = 'views:unpack_options:' . hash('sha256', serialize([$this->options, $options])) . ':' . \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (empty(static::$unpackOptions[$cid])) {
        $cache = \Drupal::cache('data')->get($cid);
        if (!empty($cache->data)) {
          $this->options = $cache->data;
        }
        else {
          $this->unpackOptions($this->options, $options);
          \Drupal::cache('data')->set($cid, $this->options, Cache::PERMANENT, $this->view->storage->getCacheTags());
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

    // Mark the view as changed so the user has a chance to save it.
    if ($changed) {
      $this->view->changed = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
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
   * {@inheritdoc}
   */
  public function isDefaultDisplay() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    if (!isset($this->has_exposed)) {
      foreach ($this->handlers as $type => $value) {
        foreach ($this->view->$type as $handler) {
          if ($handler->canExpose() && $handler->isExposed()) {
            // One is all we need; if we find it, return TRUE.
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
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesAJAX() {
    return $this->usesAJAX;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxEnabled() {
    if ($this->usesAJAX()) {
      return $this->getOption('use_ajax');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->getOption('enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function usesPager() {
    return $this->usesPager;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function usesMore() {
    return $this->usesMore;
  }

  /**
   * {@inheritdoc}
   */
  public function isMoreEnabled() {
    if ($this->usesMore()) {
      return $this->getOption('use_more');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function useGroupBy() {
    return $this->getOption('group_by');
  }

  /**
   * {@inheritdoc}
   */
  public function useMoreAlways() {
    if ($this->usesMore()) {
      return $this->getOption('use_more_always');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function useMoreText() {
    if ($this->usesMore()) {
      return $this->getOption('use_more_text');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptAttachments() {
    if (!$this->usesAttachments()) {
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
   * {@inheritdoc}
   */
  public function usesAttachments() {
    return $this->usesAttachments;
  }

  /**
   * {@inheritdoc}
   */
  public function usesAreas() {
    return $this->usesAreas;
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) {}

  /**
   * {@inheritdoc}
   */
  public function defaultableSections($section = NULL) {
    $sections = [
      'access' => ['access'],
      'cache' => ['cache'],
      'title' => ['title'],
      'css_class' => ['css_class'],
      'use_ajax' => ['use_ajax'],
      'hide_attachment_summary' => ['hide_attachment_summary'],
      'show_admin_links' => ['show_admin_links'],
      'group_by' => ['group_by'],
      'query' => ['query'],
      'use_more' => ['use_more', 'use_more_always', 'use_more_text'],
      'use_more_always' => ['use_more', 'use_more_always', 'use_more_text'],
      'use_more_text' => ['use_more', 'use_more_always', 'use_more_text'],
      'link_display' => ['link_display', 'link_url'],

      // Force these to cascade properly.
      'style' => ['style', 'row'],
      'row' => ['style', 'row'],

      'pager' => ['pager'],

      'exposed_form' => ['exposed_form'],

      // These sections are special.
      'header' => ['header'],
      'footer' => ['footer'],
      'empty' => ['empty'],
      'relationships' => ['relationships'],
      'fields' => ['fields'],
      'sorts' => ['sorts'],
      'arguments' => ['arguments'],
      'filters' => ['filters', 'filter_groups'],
      'filter_groups' => ['filters', 'filter_groups'],
    ];

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
    $options = [
      'defaults' => [
        'default' => [
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
        ],
      ],

      'title' => [
        'default' => '',
      ],
      'enabled' => [
        'default' => TRUE,
      ],
      'display_comment' => [
        'default' => '',
      ],
      'css_class' => [
        'default' => '',
      ],
      'display_description' => [
        'default' => '',
      ],
      'use_ajax' => [
        'default' => FALSE,
      ],
      'hide_attachment_summary' => [
        'default' => FALSE,
      ],
      'show_admin_links' => [
        'default' => TRUE,
      ],
      'use_more' => [
        'default' => FALSE,
      ],
      'use_more_always' => [
        'default' => TRUE,
      ],
      'use_more_text' => [
        'default' => 'more',
      ],
      'link_display' => [
        'default' => '',
      ],
      'link_url' => [
        'default' => '',
      ],
      'group_by' => [
        'default' => FALSE,
      ],
      'rendering_language' => [
        'default' => '***LANGUAGE_entity_translation***',
      ],

      // These types are all plugins that can have individual settings
      // and therefore need special handling.
      'access' => [
        'contains' => [
          'type' => ['default' => 'none'],
          'options' => ['default' => []],
        ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'cache' => [
        'contains' => [
          'type' => ['default' => 'tag'],
          'options' => ['default' => []],
        ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'query' => [
        'contains' => [
          'type' => ['default' => 'views_query'],
          'options' => ['default' => []],
         ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'exposed_form' => [
        'contains' => [
          'type' => ['default' => 'basic'],
          'options' => ['default' => []],
         ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'pager' => [
        'contains' => [
          'type' => ['default' => 'mini'],
          'options' => ['default' => []],
         ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'style' => [
        'contains' => [
          'type' => ['default' => 'default'],
          'options' => ['default' => []],
        ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],
      'row' => [
        'contains' => [
          'type' => ['default' => 'fields'],
          'options' => ['default' => []],
        ],
        'merge_defaults' => [$this, 'mergePlugin'],
      ],

      'exposed_block' => [
        'default' => FALSE,
      ],

      'header' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'footer' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'empty' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],

      // We want these to export last.
      // These are the 5 handler types.
      'relationships' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'fields' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'sorts' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'arguments' => [
        'default' => [],
        'merge_defaults' => [$this, 'mergeHandler'],
      ],
      'filter_groups' => [
        'contains' => [
          'operator' => ['default' => 'AND'],
          'groups' => ['default' => [1 => 'AND']],
        ],
      ],
      'filters' => [
        'default' => [],
      ],
    ];

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
   * {@inheritdoc}
   */
  public function hasPath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return !$this->hasPath();
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposedFormInBlock() {
    return $this->hasPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedDisplays() {
    $current_display_id = $this->display['id'];
    $attached_displays = [];

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
   * {@inheritdoc}
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
    // Fall-through returns NULL.
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  public function getRoutedDisplay() {
    // If this display has a route, return this display.
    if ($this instanceof DisplayRouterInterface) {
      return $this;
    }

    // If the display does not have a route (e.g. a block display), get the
    // route for the linked display.
    $display_id = $this->getLinkDisplay();
    if ($display_id && $this->view->displayHandlers->has($display_id) && is_object($this->view->displayHandlers->get($display_id))) {
      return $this->view->displayHandlers->get($display_id)->getRoutedDisplay();
    }

    // No routed display exists, so return NULL
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->view->getUrl(NULL, $this->display['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaulted($option) {
    return !$this->isDefaultDisplay() && !empty($this->default_display) && !empty($this->options['defaults'][$option]);
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option) {
    if ($this->isDefaulted($option)) {
      return $this->default_display->getOption($option);
    }

    if (isset($this->options[$option]) || array_key_exists($option, $this->options)) {
      return $this->options[$option];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function usesFields() {
    return $this->getPlugin('style')->usesFields();
  }

  /**
   * {@inheritdoc}
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
      $name = $views_data['table']['base']['query_id'] ?? 'views_query';
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function &getHandlers($type) {
    if (!isset($this->handlers[$type])) {
      $this->handlers[$type] = [];
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
   * Gets all the handlers used by the display.
   *
   * @param bool $only_overrides
   *   Whether to include only overridden handlers.
   *
   * @return \Drupal\views\Plugin\views\ViewsHandlerInterface[]
   */
  protected function getAllHandlers($only_overrides = FALSE) {
    $handler_types = Views::getHandlerTypes();
    $handlers = [];
    // Collect all dependencies of all handlers.
    foreach ($handler_types as $handler_type => $handler_type_info) {
      if ($only_overrides && $this->isDefaulted($handler_type_info['plural'])) {
        continue;
      }
      $handlers = array_merge($handlers, array_values($this->getHandlers($handler_type)));
    }
    return $handlers;
  }

  /**
   * Gets all the plugins used by the display.
   *
   * @param bool $only_overrides
   *   Whether to include only overridden plugins.
   *
   * @return \Drupal\views\Plugin\views\ViewsPluginInterface[]
   */
  protected function getAllPlugins($only_overrides = FALSE) {
    $plugins = [];
    // Collect all dependencies of plugins.
    foreach (Views::getPluginTypes('plugin') as $plugin_type) {
      $plugin = $this->getPlugin($plugin_type);
      if (!$plugin) {
        continue;
      }
      if ($only_overrides && $this->isDefaulted($plugin_type)) {
        continue;
      }
      $plugins[] = $plugin;
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();
    // Collect all the dependencies of handlers and plugins. Only calculate
    // their dependencies if they are configured by this display.
    $plugins = array_merge($this->getAllHandlers(TRUE), $this->getAllPlugins(TRUE));
    array_walk($plugins, [$this, 'calculatePluginDependencies']);

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabels($groupable_only = FALSE) {
    $options = [];
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function overrideOption($option, $value) {
    $this->setOverride($option, FALSE);
    $this->setOption($option, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function optionLink($text, $section, $class = '', $title = '') {
    if (!trim($text)) {
      $text = $this->t('Broken field');
    }

    if (!empty($class)) {
      $text = new FormattableMarkup('<span>@text</span>', ['@text' => $text]);
    }

    if (empty($title)) {
      $title = $text;
    }

    return Link::fromTextAndUrl($text, Url::fromRoute('views_ui.form_display', [
        'js' => 'nojs',
        'view' => $this->view->storage->id(),
        'display_id' => $this->display['id'],
        'type' => $section,
      ], [
        'attributes' => [
          'class' => ['views-ajax-link', $class],
          'title' => $title,
          'id' => Html::getUniqueId('views-' . $this->display['id'] . '-' . $section),
        ],
    ]))->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentsTokens() {
    $tokens = [];
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }

    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories = [
      'title' => [
        'title' => $this->t('Title'),
        'column' => 'first',
      ],
      'format' => [
        'title' => $this->t('Format'),
        'column' => 'first',
      ],
      'filters' => [
        'title' => $this->t('Filters'),
        'column' => 'first',
      ],
      'fields' => [
        'title' => $this->t('Fields'),
        'column' => 'first',
      ],
      'pager' => [
        'title' => $this->t('Pager'),
        'column' => 'second',
      ],
      'language' => [
        'title' => $this->t('Language'),
        'column' => 'second',
      ],
      'exposed' => [
        'title' => $this->t('Exposed form'),
        'column' => 'third',
        'build' => [
          '#weight' => 1,
        ],
      ],
      'access' => [
        'title' => '',
        'column' => 'second',
        'build' => [
          '#weight' => -5,
        ],
      ],
      'other' => [
        'title' => $this->t('Other'),
        'column' => 'third',
        'build' => [
          '#weight' => 2,
        ],
      ],
    ];

    if ($this->display['id'] != 'default') {
      $options['display_id'] = [
        'category' => 'other',
        'title' => $this->t('Machine Name'),
        'value' => !empty($this->display['new_id']) ? $this->display['new_id'] : $this->display['id'],
        'desc' => $this->t('Change the machine name of this display.'),
      ];
    }

    $display_comment = views_ui_truncate($this->getOption('display_comment'), 80);
    $options['display_comment'] = [
      'category' => 'other',
      'title' => $this->t('Administrative comment'),
      'value' => !empty($display_comment) ? $display_comment : $this->t('None'),
      'desc' => $this->t('Comment or document this display.'),
    ];

    $title = strip_tags($this->getOption('title'));
    if (!$title) {
      $title = $this->t('None');
    }

    $options['title'] = [
      'category' => 'title',
      'title' => $this->t('Title'),
      'value' => views_ui_truncate($title, 32),
      'desc' => $this->t('Change the title that this display will use.'),
    ];

    $style_plugin_instance = $this->getPlugin('style');
    $style_summary = empty($style_plugin_instance->definition['title']) ? $this->t('Missing style plugin') : $style_plugin_instance->summaryTitle();
    $style_title = empty($style_plugin_instance->definition['title']) ? $this->t('Missing style plugin') : $style_plugin_instance->pluginTitle();

    $options['style'] = [
      'category' => 'format',
      'title' => $this->t('Format'),
      'value' => $style_title,
      'setting' => $style_summary,
      'desc' => $this->t('Change the way content is formatted.'),
    ];

    // This adds a 'Settings' link to the style_options setting if the style has
    // options.
    if ($style_plugin_instance->usesOptions()) {
      $options['style']['links']['style_options'] = $this->t('Change settings for this format');
    }

    if ($style_plugin_instance->usesRowPlugin()) {
      $row_plugin_instance = $this->getPlugin('row');
      $row_summary = empty($row_plugin_instance->definition['title']) ? $this->t('Missing row plugin') : $row_plugin_instance->summaryTitle();
      $row_title = empty($row_plugin_instance->definition['title']) ? $this->t('Missing row plugin') : $row_plugin_instance->pluginTitle();

      $options['row'] = [
        'category' => 'format',
        'title' => $this->t('Show'),
        'value' => $row_title,
        'setting' => $row_summary,
        'desc' => $this->t('Change the way each row in the view is styled.'),
      ];
      // This adds a 'Settings' link to the row_options setting if the row style
      // has options.
      if ($row_plugin_instance->usesOptions()) {
        $options['row']['links']['row_options'] = $this->t('Change settings for this style');
      }
    }
    if ($this->usesAJAX()) {
      $options['use_ajax'] = [
        'category' => 'other',
        'title' => $this->t('Use AJAX'),
        'value' => $this->getOption('use_ajax') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Change whether or not this display will use AJAX.'),
      ];
    }
    if ($this->usesAttachments()) {
      $options['hide_attachment_summary'] = [
        'category' => 'other',
        'title' => $this->t('Hide attachments in summary'),
        'value' => $this->getOption('hide_attachment_summary') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Change whether or not to display attachments when displaying a contextual filter summary.'),
      ];
    }
    if (!isset($this->definition['contextual links locations']) || !empty($this->definition['contextual links locations'])) {
      $options['show_admin_links'] = [
        'category' => 'other',
        'title' => $this->t('Contextual links'),
        'value' => $this->getOption('show_admin_links') ? $this->t('Shown') : $this->t('Hidden'),
        'desc' => $this->t('Change whether or not to display contextual links for this view.'),
      ];
    }

    $pager_plugin = $this->getPlugin('pager');
    if (!$pager_plugin) {
      // Default to the no pager plugin.
      $pager_plugin = Views::pluginManager('pager')->createInstance('none');
    }

    $pager_str = $pager_plugin->summaryTitle();

    $options['pager'] = [
      'category' => 'pager',
      'title' => $this->t('Use pager'),
      'value' => $pager_plugin->pluginTitle(),
      'setting' => $pager_str,
      'desc' => $this->t("Change this display's pager setting."),
    ];

    // If pagers aren't allowed, change the text of the item.
    if (!$this->usesPager()) {
      $options['pager']['title'] = $this->t('Items to display');
    }

    if ($pager_plugin->usesOptions()) {
      $options['pager']['links']['pager_options'] = $this->t('Change settings for this pager type.');
    }

    if ($this->usesMore()) {
      $options['use_more'] = [
        'category' => 'pager',
        'title' => $this->t('More link'),
        'value' => $this->getOption('use_more') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Specify whether this display will provide a "more" link.'),
      ];
    }

    $this->view->initQuery();
    if ($this->view->query->getAggregationInfo()) {
      $options['group_by'] = [
        'category' => 'other',
        'title' => $this->t('Use aggregation'),
        'value' => $this->getOption('group_by') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Allow grouping and aggregation (calculation) of fields.'),
      ];
    }

    $options['query'] = [
      'category' => 'other',
      'title' => $this->t('Query settings'),
      'value' => $this->t('Settings'),
      'desc' => $this->t('Allow to set some advanced settings for the query plugin'),
    ];

    if (\Drupal::languageManager()->isMultilingual() && $this->isBaseTableTranslatable()) {
      $rendering_language_options = $this->buildRenderingLanguageOptions();
      $options['rendering_language'] = [
        'category' => 'language',
        'title' => $this->t('Rendering Language'),
        'value' => $rendering_language_options[$this->getOption('rendering_language')],
        'desc' => $this->t('All content that supports translations will be displayed in the selected language.'),
      ];
    }

    $access_plugin = $this->getPlugin('access');
    if (!$access_plugin) {
      // Default to the no access control plugin.
      $access_plugin = Views::pluginManager('access')->createInstance('none');
    }

    $access_str = $access_plugin->summaryTitle();

    $options['access'] = [
      'category' => 'access',
      'title' => $this->t('Access'),
      'value' => $access_plugin->pluginTitle(),
      'setting' => $access_str,
      'desc' => $this->t('Specify access control type for this display.'),
    ];

    if ($access_plugin->usesOptions()) {
      $options['access']['links']['access_options'] = $this->t('Change settings for this access type.');
    }

    $cache_plugin = $this->getPlugin('cache');
    if (!$cache_plugin) {
      // Default to the no cache control plugin.
      $cache_plugin = Views::pluginManager('cache')->createInstance('none');
    }

    $cache_str = $cache_plugin->summaryTitle();

    $options['cache'] = [
      'category' => 'other',
      'title' => $this->t('Caching'),
      'value' => $cache_plugin->pluginTitle(),
      'setting' => $cache_str,
      'desc' => $this->t('Specify caching type for this display.'),
    ];

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
          $link_display = $displays[$display_id]['display_title'];
        }
      }

      $options['link_display'] = [
        'category' => 'pager',
        'title' => $this->t('Link display'),
        'value' => $link_display,
        'desc' => $this->t('Specify which display or custom URL this display will link to.'),
      ];
    }

    if ($this->usesExposedFormInBlock()) {
      $options['exposed_block'] = [
        'category' => 'exposed',
        'title' => $this->t('Exposed form in block'),
        'value' => $this->getOption('exposed_block') ? $this->t('Yes') : $this->t('No'),
        'desc' => $this->t('Allow the exposed form to appear in a block instead of the view.'),
      ];
    }

    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface $exposed_form_plugin */
    $exposed_form_plugin = $this->getPlugin('exposed_form');
    if (!$exposed_form_plugin) {
      // Default to the no cache control plugin.
      $exposed_form_plugin = Views::pluginManager('exposed_form')->createInstance('basic');
    }

    $exposed_form_str = $exposed_form_plugin->summaryTitle();

    $options['exposed_form'] = [
      'category' => 'exposed',
      'title' => $this->t('Exposed form style'),
      'value' => $exposed_form_plugin->pluginTitle(),
      'setting' => $exposed_form_str,
      'desc' => $this->t('Select the kind of exposed filter to use.'),
    ];

    if ($exposed_form_plugin->usesOptions()) {
      $options['exposed_form']['links']['exposed_form_options'] = $this->t('Exposed form settings for this exposed form style.');
    }

    $css_class = trim($this->getOption('css_class'));
    if (!$css_class) {
      $css_class = $this->t('None');
    }

    $options['css_class'] = [
      'category' => 'other',
      'title' => $this->t('CSS class'),
      'value' => $css_class,
      'desc' => $this->t('Change the CSS class name(s) that will be added to this display.'),
    ];

    foreach ($this->extenders as $extender) {
      $extender->optionsSummary($categories, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    if ($this->defaultableSections($section)) {
      views_ui_standard_display_dropdown($form, $form_state, $section);
    }
    $form['#title'] = $this->display['display_title'] . ': ';

    // Set the 'section' to highlight on the form.
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
        $form['display_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Machine name of the display'),
          '#default_value' => !empty($this->display['new_id']) ? $this->display['new_id'] : $this->display['id'],
          '#required' => TRUE,
          '#size' => 64,
        ];
        break;

      case 'display_title':
        $form['#title'] .= $this->t('The name and the description of this display');
        $form['display_title'] = [
          '#title' => $this->t('Administrative name'),
          '#type' => 'textfield',
          '#default_value' => $this->display['display_title'],
        ];
        $form['display_description'] = [
          '#title' => $this->t('Administrative description'),
          '#type' => 'textfield',
          '#default_value' => $this->getOption('display_description'),
        ];
        break;

      case 'display_comment':
        $form['#title'] .= $this->t('Administrative comment');
        $form['display_comment'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Administrative comment'),
          '#description' => $this->t('This description will only be seen within the administrative interface and can be used to document this display.'),
          '#default_value' => $this->getOption('display_comment'),
        ];
        break;

      case 'title':
        $form['#title'] .= $this->t('The title of this view');
        $form['title'] = [
          '#title' => $this->t('Title'),
          '#type' => 'textfield',
          '#description' => $this->t('This title will be displayed with the view, wherever titles are normally displayed; i.e, as the page title, block title, etc.'),
          '#default_value' => $this->getOption('title'),
          '#maxlength' => 255,
        ];
        break;

      case 'css_class':
        $form['#title'] .= $this->t('CSS class');
        $form['css_class'] = [
          '#type' => 'textfield',
          '#title' => $this->t('CSS class name(s)'),
          '#description' => $this->t('Separate multiple classes by spaces.'),
          '#default_value' => $this->getOption('css_class'),
        ];
        break;

      case 'use_ajax':
        $form['#title'] .= $this->t('AJAX');
        $form['use_ajax'] = [
          '#description' => $this->t('Options such as paging, table sorting, and exposed filters will not initiate a page refresh.'),
          '#type' => 'checkbox',
          '#title' => $this->t('Use AJAX'),
          '#default_value' => $this->getOption('use_ajax') ? 1 : 0,
        ];
        break;

      case 'hide_attachment_summary':
        $form['#title'] .= $this->t('Hide attachments when displaying a contextual filter summary');
        $form['hide_attachment_summary'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide attachments in summary'),
          '#default_value' => $this->getOption('hide_attachment_summary') ? 1 : 0,
        ];
        break;

      case 'show_admin_links':
        $form['#title'] .= $this->t('Show contextual links on this view.');
        $form['show_admin_links'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show contextual links'),
          '#default_value' => $this->getOption('show_admin_links'),
        ];
        break;

      case 'use_more':
        $form['#title'] .= $this->t('Add a more link to the bottom of the display.');
        $form['use_more'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Create more link'),
          '#description' => $this->t("This will add a more link to the bottom of this view, which will link to the page view. If you have more than one page view, the link will point to the display specified in 'Link display' section under pager. You can override the URL at the link display setting."),
          '#default_value' => $this->getOption('use_more'),
        ];
        $form['use_more_always'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Always display the more link'),
          '#description' => $this->t('Check this to display the more link even if there are no more items to display.'),
          '#default_value' => $this->getOption('use_more_always'),
          '#states' => [
            'visible' => [
              ':input[name="use_more"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['use_more_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('More link text'),
          '#description' => $this->t('The text to display for the more link.'),
          '#default_value' => $this->getOption('use_more_text'),
          '#states' => [
            'visible' => [
              ':input[name="use_more"]' => ['checked' => TRUE],
            ],
          ],
        ];
        break;

      case 'group_by':
        $form['#title'] .= $this->t('Allow grouping and aggregation (calculation) of fields.');
        $form['group_by'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Aggregate'),
          '#description' => $this->t('If enabled, some fields may become unavailable. All fields that are selected for grouping will be collapsed to one record per distinct value. Other fields which are selected for aggregation will have the function run on them. For example, you can group nodes on title and count the number of nids in order to get a list of duplicate titles.'),
          '#default_value' => $this->getOption('group_by'),
        ];
        break;

      case 'access':
        $form['#title'] .= $this->t('Access restrictions');
        $form['access'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];

        $access = $this->getOption('access');
        $form['access']['type'] = [
          '#title' => $this->t('Access'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('access', $this->getType(), [$this->view->storage->get('base_table')]),
          '#default_value' => $access['type'],
        ];

        $access_plugin = $this->getPlugin('access');
        if ($access_plugin->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected access restriction.', ['@settings' => $this->optionLink($this->t('settings'), 'access_options')]),
            '#suffix' => '</div>',
          ];
        }

        break;

      case 'access_options':
        $plugin = $this->getPlugin('access');
        $form['#title'] .= $this->t('Access options');
        if ($plugin) {
          $form['access_options'] = [
            '#tree' => TRUE,
          ];
          $plugin->buildOptionsForm($form['access_options'], $form_state);
        }
        break;

      case 'cache':
        $form['#title'] .= $this->t('Caching');
        $form['cache'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];

        $cache = $this->getOption('cache');
        $form['cache']['type'] = [
          '#title' => $this->t('Caching'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('cache', $this->getType(), [$this->view->storage->get('base_table')]),
          '#default_value' => $cache['type'],
        ];

        $cache_plugin = $this->getPlugin('cache');
        if ($cache_plugin->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected cache mechanism.', ['@settings' => $this->optionLink($this->t('settings'), 'cache_options')]),
          ];
        }
        break;

      case 'cache_options':
        $plugin = $this->getPlugin('cache');
        $form['#title'] .= $this->t('Caching options');
        if ($plugin) {
          $form['cache_options'] = [
            '#tree' => TRUE,
          ];
          $plugin->buildOptionsForm($form['cache_options'], $form_state);
        }
        break;

      case 'query':
        $query_options = $this->getOption('query');
        $plugin_name = $query_options['type'];

        $form['#title'] .= $this->t('Query options');
        $this->view->initQuery();
        if ($this->view->query) {
          $form['query'] = [
            '#tree' => TRUE,
            'type' => [
              '#type' => 'value',
              '#value' => $plugin_name,
            ],
            'options' => [
              '#tree' => TRUE,
            ],
          ];

          $this->view->query->buildOptionsForm($form['query']['options'], $form_state);
        }
        break;

      case 'rendering_language':
        $form['#title'] .= $this->t('Rendering language');
        if (\Drupal::languageManager()->isMultilingual() && $this->isBaseTableTranslatable()) {
          $options = $this->buildRenderingLanguageOptions();
          $form['rendering_language'] = [
            '#type' => 'select',
            '#options' => $options,
            '#title' => $this->t('Rendering language'),
            '#description' => $this->t('All content that supports translations will be displayed in the selected language.'),
            '#default_value' => $this->getOption('rendering_language'),
          ];
        }
        else {
          $form['rendering_language']['#markup'] = $this->t('The view is not based on a translatable entity type or the site is not multilingual.');
        }
        break;

      case 'style':
        $form['#title'] .= $this->t('How should this view be styled');
        $style_plugin = $this->getPlugin('style');
        $form['style'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];
        $form['style']['type'] = [
          '#title' => $this->t('Style'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('style', $this->getType(), [$this->view->storage->get('base_table')]),
          '#default_value' => $style_plugin->definition['id'],
          '#description' => $this->t('If the style you choose has settings, be sure to click the settings button that will appear next to it in the View summary.'),
        ];

        if ($style_plugin->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected style.', ['@settings' => $this->optionLink($this->t('settings'), 'style_options')]),
          ];
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
        // If row, $style will be empty.
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
        $form['row'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];
        $form['row']['type'] = [
          '#title' => $this->t('Row'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('row', $this->getType(), [$this->view->storage->get('base_table')]),
          '#default_value' => $row_plugin_instance->definition['id'],
        ];

        if ($row_plugin_instance->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected row style.', ['@settings' => $this->optionLink($this->t('settings'), 'row_options')]),
          ];
        }

        break;

      case 'link_display':
        $form['#title'] .= $this->t('Which display to use for path');
        $options = [FALSE => $this->t('None'), 'custom_url' => $this->t('Custom URL')];

        foreach ($this->view->storage->get('display') as $display_id => $display) {
          if ($this->view->displayHandlers->get($display_id)->hasPath()) {
            $options[$display_id] = $display['display_title'];
          }
        }

        $form['link_display'] = [
          '#type' => 'radios',
          '#options' => $options,
          '#description' => $this->t("Which display to use to get this display's path for things like summary links, rss feed links, more links, etc."),
          '#default_value' => $this->getOption('link_display'),
        ];

        $options = [];
        $optgroup_arguments = (string) t('Arguments');
        foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
          $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
          $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
        }

        // Default text.
        // We have some options, so make a list.
        $description = [];
        $description[] = [
          '#markup' => $this->t('A Drupal path or external URL the more link will point to. Note that this will override the link display setting above.'),
        ];
        if (!empty($options)) {
          $description[] = [
            '#prefix' => '<p>',
            '#markup' => $this->t('The following tokens are available for this link. You may use Twig syntax in this field.'),
            '#suffix' => '</p>',
          ];
          foreach (array_keys($options) as $type) {
            if (!empty($options[$type])) {
              $items = [];
              foreach ($options[$type] as $key => $value) {
                $items[] = $key . ' == ' . $value;
              }
              $item_list = [
                '#theme' => 'item_list',
                '#items' => $items,
              ];
              $description[] = $item_list;
            }
          }
        }

        $form['link_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Custom URL'),
          '#default_value' => $this->getOption('link_url'),
          '#description' => $description,
          '#states' => [
            'visible' => [
              ':input[name="link_display"]' => ['value' => 'custom_url'],
            ],
          ],
        ];
        break;

      case 'exposed_block':
        $form['#title'] .= $this->t('Put the exposed form in a block');
        $form['description'] = [
          '#markup' => '<div class="js-form-item form-item description">' . $this->t('If set, any exposed widgets will not appear with this view. Instead, a block will be made available to the Drupal block administration system, and the exposed form will appear there. Note that this block must be enabled manually, Views will not enable it for you.') . '</div>',
        ];
        $form['exposed_block'] = [
          '#type' => 'radios',
          '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
          '#default_value' => $this->getOption('exposed_block') ? 1 : 0,
        ];
        break;

      case 'exposed_form':
        $form['#title'] .= $this->t('Exposed Form');
        $form['exposed_form'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];

        $exposed_form = $this->getOption('exposed_form');
        $form['exposed_form']['type'] = [
          '#title' => $this->t('Exposed form'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('exposed_form', $this->getType(), [$this->view->storage->get('base_table')]),
          '#default_value' => $exposed_form['type'],
        ];

        $exposed_form_plugin = $this->getPlugin('exposed_form');
        if ($exposed_form_plugin->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected style.', ['@settings' => $this->optionLink($this->t('settings'), 'exposed_form_options')]),
          ];
        }
        break;

      case 'exposed_form_options':
        $plugin = $this->getPlugin('exposed_form');
        $form['#title'] .= $this->t('Exposed form options');
        if ($plugin) {
          $form['exposed_form_options'] = [
            '#tree' => TRUE,
          ];
          $plugin->buildOptionsForm($form['exposed_form_options'], $form_state);
        }
        break;

      case 'pager':
        $form['#title'] .= $this->t('Select pager');
        $form['pager'] = [
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        ];

        $pager = $this->getOption('pager');
        $form['pager']['type'] = [
          '#title' => $this->t('Pager'),
          '#title_display' => 'invisible',
          '#type' => 'radios',
          '#options' => Views::fetchPluginNames('pager', !$this->usesPager() ? 'basic' : NULL, [$this->view->storage->get('base_table')]),
          '#default_value' => $pager['type'],
        ];

        $pager_plugin = $this->getPlugin('pager');
        if ($pager_plugin->usesOptions()) {
          $form['markup'] = [
            '#prefix' => '<div class="js-form-item form-item description">',
            '#suffix' => '</div>',
            '#markup' => $this->t('You may also adjust the @settings for the currently selected pager.', ['@settings' => $this->optionLink($this->t('settings'), 'pager_options')]),
          ];
        }

        break;

      case 'pager_options':
        $plugin = $this->getPlugin('pager');
        $form['#title'] .= $this->t('Pager options');
        if ($plugin) {
          $form['pager_options'] = [
            '#tree' => TRUE,
          ];
          $plugin->buildOptionsForm($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extenders as $extender) {
      $extender->buildOptionsForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
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
            $form_state->setError($form['display_id'], $this->t('Display machine name must contain only lowercase letters, numbers, or underscores.'));
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
   * {@inheritdoc}
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
        $type = $form_state->getValue([$plugin_type, 'type']);
        if ($plugin_options['type'] != $type) {
          /** @var \Drupal\views\Plugin\views\ViewsPluginInterface $plugin */
          $plugin = Views::pluginManager($plugin_type)->createInstance($type);
          if ($plugin) {
            $plugin->init($this->view, $this, $plugin_options['options']);
            $plugin_options = [
              'type' => $type,
              'options' => $plugin->options,
            ];
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
   * {@inheritdoc}
   */
  public function optionsOverride($form, FormStateInterface $form_state) {
    $this->setOverride($form_state->get('section'));
  }

  /**
   * {@inheritdoc}
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
        // Copy existing values into our display.
        $this->options[$option] = $this->getOption($option);
        $this->display['display_options'][$option] = $this->options[$option];
      }
      $this->options['defaults'][$option] = $new_state;
      $this->display['display_options']['defaults'][$option] = $new_state;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    foreach ($this->extenders as $extender) {
      $extender->query();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renderFilters() {}

  /**
   * {@inheritdoc}
   */
  public function renderPager() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function renderMoreLink() {
    $hasMoreRecords = !empty($this->view->pager) && $this->view->pager->hasMoreRecords();
    if ($this->isMoreEnabled() && ($this->useMoreAlways() || $hasMoreRecords)) {
      $url = $this->getMoreUrl();

      return [
        '#type' => 'more_link',
        '#url' => $url,
        '#title' => $this->useMoreText(),
        '#view' => $this->view,
      ];
    }
  }

  /**
   * Get the more URL for this view.
   *
   * Uses the custom URL if there is one, otherwise the display path.
   *
   * @return \Drupal\Core\Url
   *   The more link as Url object.
   */
  protected function getMoreUrl() {
    $path = $this->getOption('link_url');

    // Return the display URL if there is no custom url.
    if ($this->getOption('link_display') !== 'custom_url' || empty($path)) {
      return $this->view->getUrl(NULL, $this->display['id']);
    }

    $parts = UrlHelper::parse($path);
    $options = $parts;
    $tokens = $this->getArgumentsTokens();

    // If there are no tokens there is nothing else to do.
    if (!empty($tokens)) {
      $parts['path'] = $this->viewsTokenReplace($parts['path'], $tokens);
      $parts['fragment'] = $this->viewsTokenReplace($parts['fragment'], $tokens);

      // Handle query parameters where the key is part of an array.
      // For example, f[0] for facets.
      array_walk_recursive($parts['query'], function (&$value) use ($tokens) {
        $value = $this->viewsTokenReplace($value, $tokens);
      });
      $options = $parts;
    }

    $path = $options['path'];
    unset($options['path']);

    // Create url.
    // @todo Views should expect and store a leading /. See:
    //   https://www.drupal.org/node/2423913
    $url = UrlHelper::isExternal($path) ? Url::fromUri($path, $options) : Url::fromUserInput('/' . ltrim($path, '/'), $options);

    // Merge the exposed query parameters.
    if (!empty($this->view->exposed_raw_input)) {
      $url->mergeOptions(['query' => $this->view->exposed_raw_input]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : [];

    $element = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#pre_render' => [[$this, 'elementPreRender']],
      '#rows' => $rows,
      // Assigned by reference so anything added in $element['#attached'] will
      // be available on the view.
      '#attached' => &$this->view->element['#attached'],
      '#cache' => &$this->view->element['#cache'],
    ];

    $this->applyDisplayCacheabilityMetadata($this->view->element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'elementPreRender';
    return $callbacks;
  }

  /**
   * Applies the cacheability of the current display to the given render array.
   *
   * @param array $element
   *   The render array with updated cacheability metadata.
   */
  protected function applyDisplayCacheabilityMetadata(array &$element) {
    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache */
    $cache = $this->getPlugin('cache');

    (new CacheableMetadata())
      ->setCacheTags(Cache::mergeTags($this->view->getCacheTags(), $this->display['cache_metadata']['tags'] ?? []))
      ->setCacheContexts($this->display['cache_metadata']['contexts'] ?? [])
      ->setCacheMaxAge(Cache::mergeMaxAges($cache->getCacheMaxAge(), $this->display['cache_metadata']['max-age'] ?? Cache::PERMANENT))
      ->merge(CacheableMetadata::createFromRenderArray($element))
      ->applyTo($element);
  }

  /**
   * {@inheritdoc}
   */
  public function elementPreRender(array $element) {
    $view = $element['#view'];
    $empty = empty($view->result);

    // Force a render array so CSS/JS can be attached.
    if (!is_array($element['#rows'])) {
      $element['#rows'] = ['#markup' => $element['#rows']];
    }

    $element['#header'] = $view->display_handler->renderArea('header', $empty);
    $element['#footer'] = $view->display_handler->renderArea('footer', $empty);
    $element['#empty'] = $empty ? $view->display_handler->renderArea('empty', $empty) : [];
    $element['#exposed'] = !empty($view->exposed_widgets) ? $view->exposed_widgets : [];
    $element['#more'] = $view->display_handler->renderMoreLink();
    $element['#feed_icons'] = !empty($view->feedIcons) ? $view->feedIcons : [];

    if ($view->display_handler->renderPager()) {
      $exposed_input = $view->exposed_raw_input ?? NULL;
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

      $form_object = ViewsForm::create(\Drupal::getContainer(), $view->storage->id(), $view->current_display, $view->args);
      $form = \Drupal::formBuilder()->getForm($form_object, $view, $output);
      // The form is requesting that all non-essential views elements be hidden,
      // usually because the rendered step is not a view result.
      if ($form['show_view_elements']['#value'] == FALSE) {
        $element['#header'] = [];
        $element['#exposed'] = [];
        $element['#pager'] = [];
        $element['#footer'] = [];
        $element['#more'] = [];
        $element['#feed_icons'] = [];
      }

      $element['#rows'] = $form;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function renderArea($area, $empty = FALSE) {
    $return = [];
    foreach ($this->getHandlers($area) as $key => $area_handler) {
      if ($area_render = $area_handler->render($empty)) {
        if (isset($area_handler->position)) {
          // Fix weight of area.
          $area_render['#weight'] = $area_handler->position;
        }
        $return[$key] = $area_render;
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
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

    // Fallback to all access if no plugin.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    $this->view->setAjaxEnabled($this->ajaxEnabled());
    if ($this->isMoreEnabled() && !$this->useMoreAlways()) {
      $this->view->get_total_rows = TRUE;
    }
    $this->view->initHandlers();
    if ($this->usesExposed()) {
      /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface $exposed_form */
      $exposed_form = $this->getPlugin('exposed_form');
      $exposed_form->preExecute();
    }

    foreach ($this->extenders as $extender) {
      $extender->preExecute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateCacheMetadata() {
    $cache_metadata = new CacheableMetadata();

    // Iterate over ordinary views plugins.
    foreach (Views::getPluginTypes('plugin') as $plugin_type) {
      $plugin = $this->getPlugin($plugin_type);
      if ($plugin instanceof CacheableDependencyInterface) {
        $cache_metadata = $cache_metadata->merge(CacheableMetadata::createFromObject($plugin));
      }
    }

    // Iterate over all handlers. Note that at least the argument handler will
    // need to ask all its subplugins.
    foreach (array_keys(Views::getHandlerTypes()) as $handler_type) {
      $handlers = $this->getHandlers($handler_type);
      foreach ($handlers as $handler) {
        if ($handler instanceof CacheableDependencyInterface) {
          $cache_metadata = $cache_metadata->merge(CacheableMetadata::createFromObject($handler));
        }
      }
    }

    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
    if ($cache_plugin = $this->getPlugin('cache')) {
      $cache_plugin->alterCacheMetadata($cache_metadata);
    }

    return $cache_metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMetadata() {
    if (!isset($this->display['cache_metadata'])) {
      $cache_metadata = $this->calculateCacheMetadata();
      $this->display['cache_metadata']['max-age'] = $cache_metadata->getCacheMaxAge();
      $this->display['cache_metadata']['contexts'] = $cache_metadata->getCacheContexts();
      $this->display['cache_metadata']['tags'] = $cache_metadata->getCacheTags();
    }
    else {
      $cache_metadata = (new CacheableMetadata())
        ->setCacheMaxAge($this->display['cache_metadata']['max-age'])
        ->setCacheContexts($this->display['cache_metadata']['contexts'])
        ->setCacheTags($this->display['cache_metadata']['tags']);
    }
    return $cache_metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {}

  /**
   * {@inheritdoc}
   */
  public function buildRenderable(array $args = [], $cache = TRUE) {
    $this->view->element += [
      '#type' => 'view',
      '#name' => $this->view->storage->id(),
      '#display_id' => $this->display['id'],
      '#arguments' => $args,
      '#embed' => FALSE,
      '#view' => $this->view,
      '#cache_properties' => ['#view_id', '#view_display_show_admin_links', '#view_display_plugin_id'],
    ];

    // When something passes $cache = FALSE, they're asking us not to create our
    // own render cache for it. However, we still need to include certain pieces
    // of cacheability metadata (e.g.: cache contexts), so they can bubble up.
    // Thus, we add the cacheability metadata first, then modify / remove the
    // cache keys depending on the $cache argument.
    $this->applyDisplayCacheabilityMetadata($this->view->element);
    if ($cache) {
      $this->view->element['#cache'] += ['keys' => []];
      // Places like \Drupal\views\ViewExecutable::setCurrentPage() set up an
      // additional cache context.
      $this->view->element['#cache']['keys'] = array_merge(['views', 'display', $this->view->element['#name'], $this->view->element['#display_id']], $this->view->element['#cache']['keys']);

      // Add arguments to the cache key.
      if ($args) {
        $this->view->element['#cache']['keys'][] = 'args';
        $this->view->element['#cache']['keys'][] = implode(',', $args);
      }
    }
    else {
      // Remove the cache keys, to ensure render caching is not triggered. We
      // don't unset the other #cache values, to allow cacheability metadata to
      // still be bubbled.
      unset($this->view->element['#cache']['keys']);
    }

    return $this->view->element;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildBasicRenderable($view_id, $display_id, array $args = []) {
    $build = [
      '#type' => 'view',
      '#name' => $view_id,
      '#display_id' => $display_id,
      '#arguments' => $args,
      '#embed' => FALSE,
      '#cache' => [
        'keys' => ['view', $view_id, 'display', $display_id],
      ],
    ];

    if ($args) {
      $build['#cache']['keys'][] = 'args';
      $build['#cache']['keys'][] = implode(',', $args);
    }

    $build['#cache_properties'] = ['#view_id', '#view_display_show_admin_links', '#view_display_plugin_id'];

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'normal';
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [];
    // Make sure displays that use fields HAVE fields.
    if ($this->usesFields()) {
      $fields = FALSE;
      foreach ($this->getHandlers('field') as $field) {
        if (empty($field->options['exclude'])) {
          $fields = TRUE;
        }
      }

      if (!$fields) {
        $errors[] = $this->t('Display "@display" uses fields but there are none defined for it or all are excluded.', ['@display' => $this->display['display_title']]);
      }
    }

    // Validate the more link.
    if ($this->isMoreEnabled() && $this->getOption('link_display') !== 'custom_url') {
      $routed_display = $this->getRoutedDisplay();
      if (!$routed_display || !$routed_display->isEnabled()) {
        $errors[] = $this->t('Display "@display" uses a "more" link but there are no displays it can link to. You need to specify a custom URL.', ['@display' => $this->display['display_title']]);
      }
    }

    if ($this->hasPath() && !$this->getOption('path')) {
      $errors[] = $this->t('Display "@display" uses a path but the path is undefined.', ['@display' => $this->display['display_title']]);
    }

    // Validate style plugin.
    $style = $this->getPlugin('style');
    if (empty($style)) {
      $errors[] = $this->t('Display "@display" has an invalid style plugin.', ['@display' => $this->display['display_title']]);
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

    // Check for missing relationships.
    $relationships = array_keys($this->getHandlers('relationship'));
    foreach (ViewExecutable::getHandlerTypes() as $type => $handler_type_info) {
      foreach ($this->getHandlers($type) as $handler) {
        if (!empty($handler->options['relationship']) && $handler->options['relationship'] != 'none' && !in_array($handler->options['relationship'], $relationships)) {
          $errors[] = $this->t('The %handler_type %handler uses a relationship that has been removed.', ['%handler_type' => $handler_type_info['lstitle'], '%handler' => $handler->adminLabel()]);
        }
      }
    }

    // Validate handlers.
    foreach (ViewExecutable::getHandlerTypes() as $type => $info) {
      foreach ($this->getHandlers($type) as $handler) {
        $result = $handler->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }

    // Validate extenders.
    foreach ($this->extenders as $extender) {
      $result = $extender->validate();
      if (!empty($result) && is_array($result)) {
        $errors = array_merge($errors, $result);
      }
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function newDisplay() {
  }

  /**
   * {@inheritdoc}
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
            if ($id != $key && isset($handler->options['expose']['identifier']) && $identifier == $handler->options['expose']['identifier']) {
              return FALSE;
            }
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function outputIsEmpty() {
    if (!empty($this->view->result)) {
      return FALSE;
    }

    // Check whether all of the area handlers are empty.
    foreach (['empty', 'footer', 'header'] as $type) {
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
   * {@inheritdoc}
   */
  public function getSpecialBlocks() {
    $blocks = [];

    if ($this->usesExposedFormInBlock()) {
      $delta = '-exp-' . $this->view->storage->id() . '-' . $this->display['id'];
      $desc = $this->t('Exposed form: @view-@display_id', ['@view' => $this->view->storage->id(), '@display_id' => $this->display['id']]);

      $blocks[$delta] = [
        'info' => $desc,
      ];
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function viewExposedFormBlocks() {
    // Avoid interfering with the admin forms.
    $route_name = \Drupal::routeMatch()->getRouteName();
    if (strpos($route_name, 'views_ui.') === 0) {
      return;
    }
    $this->view->initHandlers();

    if ($this->usesExposed() && $this->getOption('exposed_block')) {
      /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface $exposed_form */
      $exposed_form = $this->getPlugin('exposed_form');
      return $exposed_form->renderExposedForm(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentText() {
    return [
      'filter value not present' => $this->t('When the filter value is <em>NOT</em> available'),
      'filter value present' => $this->t('When the filter value <em>IS</em> available or a default is provided'),
      'description' => $this->t("This display does not have a source for contextual filters, so no contextual filter value will be available unless you select 'Provide default'."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerText() {
    return [
      'items per page title' => $this->t('Items to display'),
      'items per page description' => $this->t('Enter 0 for no limit.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDefaults() {
    $defined_options = $this->defineOptions();

    // Build a map of plural => singular for handler types.
    $type_map = [];
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
   * {@inheritdoc}
   */
  public function remove() {

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
   * {@inheritdoc}
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
    // @todo Consider making these plugins. See
    //   https://www.drupal.org/node/2173811.
    // Pass the current rendering language (in this case a one element array) so
    // is not lost when there are language configuration changes.
    return $this->listLanguages(LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_SITE_DEFAULT | PluginBase::INCLUDE_NEGOTIATED | PluginBase::INCLUDE_ENTITY, [$this->getOption('rendering_language')]);
  }

  /**
   * Returns whether the base table is of a translatable entity type.
   *
   * @return bool
   *   TRUE if the base table is of a translatable entity type, FALSE otherwise.
   */
  protected function isBaseTableTranslatable() {
    if ($entity_type = $this->view->getBaseEntityType()) {
      return $entity_type->isTranslatable();
    }
    return FALSE;
  }

}

/**
 * @}
 */
