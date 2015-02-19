<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\PathPluginBase.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\views\Views;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The base display plugin for path/callbacks. This is used for pages and feeds.
 *
 * @see \Drupal\views\EventSubscriber\RouteSubscriber
 */
abstract class PathPluginBase extends DisplayPluginBase implements DisplayRouterInterface {

  use UrlGeneratorTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a PathPluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeProvider = $route_provider;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('state')
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::hasPath().
   */
  public function hasPath() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $bits = explode('/', $this->getOption('path'));
    if ($this->isDefaultTabPath()) {
      array_pop($bits);
    }
    return implode('/', $bits);
  }

  /**
   * Determines if this display's path is a default tab.
   *
   * @return bool
   *   TRUE if the display path is for a default tab, FALSE otherwise.
   */
  protected function isDefaultTabPath() {
    $menu = $this->getOption('menu');
    $tab_options = $this->getOption('tab_options');
    return $menu['type'] == 'default tab' && !empty($tab_options['type']) && $tab_options['type'] != 'none';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase:defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = array('default' => '');
    $options['route_name'] = array('default' => '');

    return $options;
  }

  /**
   * Generates a route entry for a given view and display.
   *
   * @param string $view_id
   *   The ID of the view.
   * @param string $display_id
   *   The current display ID.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route for the view.
   */
  protected function getRoute($view_id, $display_id) {
    $defaults = array(
      '_controller' => 'Drupal\views\Routing\ViewPageController::handle',
      'view_id' => $view_id,
      'display_id' => $display_id,
    );

    // @todo How do we apply argument validation?
    $bits = explode('/', $this->getOption('path'));
    // @todo Figure out validation/argument loading.
    // Replace % with %views_arg for menu autoloading and add to the
    // page arguments so the argument actually comes through.
    $arg_counter = 0;

    $argument_ids = array_keys((array) $this->getOption('arguments'));
    $total_arguments = count($argument_ids);

    $argument_map = array();

    // Replace arguments in the views UI (defined via %) with parameters in
    // routes (defined via {}). As a name for the parameter use arg_$key, so
    // it can be pulled in the views controller from the request.
    foreach ($bits as $pos => $bit) {
      if ($bit == '%') {
        // Generate the name of the parameter using the key of the argument
        // handler.
        $arg_id = 'arg_' . $arg_counter++;
        $bits[$pos] = '{' . $arg_id . '}';
      }
      elseif (strpos($bit, '%') === 0) {
        // Use the name defined in the path.
        $parameter_name = substr($bit, 1);
        $arg_id = 'arg_' . $arg_counter++;
        $argument_map[$arg_id] = $parameter_name;
        $bits[$pos] = '{' . $parameter_name . '}';
      }
    }

    // Add missing arguments not defined in the path, but added as handler.
    while (($total_arguments - $arg_counter) > 0) {
      $arg_id = 'arg_' . $arg_counter++;
      $bit = '{' . $arg_id . '}';
      // In contrast to the previous loop add the defaults here, as % was not
      // specified, which means the argument is optional.
      $defaults[$arg_id] = NULL;
      $bits[] = $bit;
    }

    // If this is to be a default tab, create the route for the parent path.
    if ($this->isDefaultTabPath()) {
      $bit = array_pop($bits);
      if (empty($bits)) {
        $bits[] = $bit;
      }
    }

    $route_path = '/' . implode('/', $bits);

    $route = new Route($route_path, $defaults);

    // Add access check parameters to the route.
    $access_plugin = $this->getPlugin('access');
    if (!isset($access_plugin)) {
      // @todo Do we want to support a default plugin in getPlugin itself?
      $access_plugin = Views::pluginManager('access')->createInstance('none');
    }
    $access_plugin->alterRouteDefinition($route);

    // Set the argument map, in order to support named parameters.
    $route->setOption('_view_argument_map', $argument_map);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function collectRoutes(RouteCollection $collection) {
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];

    $route = $this->getRoute($view_id, $display_id);

    if (!($route_name = $this->getOption('route_name'))) {
      $route_name = "view.$view_id.$display_id";
    }
    $collection->add($route_name, $route);
    return array("$view_id.$display_id" => $route_name);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $view_route_names = array();
    $view_path = $this->getPath();
    foreach ($collection->all() as $name => $route) {
      // Find all paths which match the path of the current display..
      $route_path = RouteCompiler::getPathWithoutDefaults($route);
      $route_path = RouteCompiler::getPatternOutline($route_path);
      // Ensure that we don't override a route which is already controlled by
      // views.
      if (!$route->hasDefault('view_id') && ('/' . $view_path == $route_path)) {
        $parameters = $route->compile()->getPathVariables();

        // @todo Figure out whether we need to merge some settings (like
        // requirements).

        // Replace the existing route with a new one based on views.
        $original_route = $collection->get($name);
        $collection->remove($name);

        $view_id = $this->view->storage->id();
        $display_id = $this->display['id'];
        $route = $this->getRoute($view_id, $display_id);

        $path = $route->getPath();
        // Replace the path with the original parameter names and add a mapping.
        $argument_map = array();
        // We assume that the numeric ids of the parameters match the one from
        // the view argument handlers.
        foreach ($parameters as $position => $parameter_name) {
          $path = str_replace('{arg_' . $position . '}', '{' . $parameter_name . '}', $path);
          $argument_map['arg_' . $position] = $parameter_name;
        }
        // Copy the original options from the route, so for example we ensure
        // that parameter conversion options is carried over.
        $route->setOptions($route->getOptions() + $original_route->getOptions());

        // Set the corrected path and the mapping to the route object.
        $route->setOption('_view_argument_map', $argument_map);
        $route->setPath($path);

        $collection->add($name, $route);
        $view_route_names[$view_id . '.' . $display_id] = $name;
      }
    }

    return $view_route_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuLinks() {
    $links = array();

    // Replace % with the link to our standard views argument loader
    // views_arg_load -- which lives in views.module.

    $bits = explode('/', $this->getOption('path'));

    // Replace % with %views_arg for menu autoloading and add to the
    // page arguments so the argument actually comes through.
    foreach ($bits as $pos => $bit) {
      if ($bit == '%') {
        // If a view requires any arguments we cannot create a static menu link.
        return array();
      }
    }

    $path = implode('/', $bits);
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];
    $view_id_display =  "{$view_id}.{$display_id}";
    $menu_link_id = 'views.' . str_replace('/', '.', $view_id_display);

    if ($path) {
      $menu = $this->getOption('menu');
      if (!empty($menu['type']) && $menu['type'] == 'normal') {
        $links[$menu_link_id] = array();
        // Some views might override existing paths, so we have to set the route
        // name based upon the altering.
        $links[$menu_link_id] = array(
          'route_name' => $this->getRouteName(),
          // Identify URL embedded arguments and correlate them to a handler.
          'load arguments'  => array($this->view->storage->id(), $this->display['id'], '%index'),
          'id' => $menu_link_id,
        );
        $links[$menu_link_id]['title'] = $menu['title'];
        $links[$menu_link_id]['description'] = $menu['description'];
        $links[$menu_link_id]['parent'] = $menu['parent'];

        if (isset($menu['weight'])) {
          $links[$menu_link_id]['weight'] = intval($menu['weight']);
        }

        // Insert item into the proper menu.
        $links[$menu_link_id]['menu_name'] = $menu['menu_name'];
        // Keep track of where we came from.
        $links[$menu_link_id]['metadata'] = array(
          'view_id' => $view_id,
          'display_id' => $display_id,
        );
      }
    }

    return $links;
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
      'title' => $this->t('Page settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $path = strip_tags($this->getOption('path'));

    if (empty($path)) {
      $path = $this->t('No path is set');
    }
    else {
      $path = '/' . $path;
    }

    $options['path'] = array(
      'category' => 'page',
      'title' => $this->t('Path'),
      'value' => views_ui_truncate($path, 24),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'path':
        $form['#title'] .= $this->t('The menu path or URL of this view');
        $form['path'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Path'),
          '#description' => $this->t('This view will be displayed by visiting this path on your site. You may use "%" in your URL to represent values that will be used for contextual filters: For example, "node/%/feed". If needed you can even specify named route parameters like taxonomy/term/%taxonomy_term'),
          '#default_value' => $this->getOption('path'),
          '#field_prefix' => '<span dir="ltr">' . $this->url('<none>', [], ['absolute' => TRUE]),
          '#field_suffix' => '</span>&lrm;',
          '#attributes' => array('dir' => LanguageInterface::DIRECTION_LTR),
          // Account for the leading backslash.
          '#maxlength' => 254,
        );
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'path') {
      $errors = $this->validatePath($form_state->getValue('path'));
      foreach ($errors as $error) {
        $form_state->setError($form['path'], $error);
      }

      // Automatically remove '/' and trailing whitespace from path.
      $form_state->setValue('path', trim($form_state->getValue('path'), '/ '));
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'path') {
      $this->setOption('path', $form_state->getValue('path'));
    }
  }

  /**
   * Validates the path of the display.
   *
   * @param string $path
   *   The path to validate.
   *
   * @return array
   *   A list of error strings.
   */
  protected function validatePath($path) {
    $errors = array();
    if (strpos($path, '%') === 0) {
      $errors[] = $this->t('"%" may not be used for the first segment of a path.');
    }

    $parsed_url = UrlHelper::parse($path);
    if (empty($parsed_url['path'])) {
      $errors[] = $this->t('Path is empty.');
    }

    if (!empty($parsed_url['query'])) {
      $errors[] = $this->t('No query allowed.');
    }

    if (!parse_url('user-path:/' . $path)) {
      $errors[] = $this->t('Invalid path. Valid characters are alphanumerics as well as "-", ".", "_" and "~".');
    }

    $path_sections = explode('/', $path);
    // Symfony routing does not allow to use numeric placeholders.
    // @see \Symfony\Component\Routing\RouteCompiler
    $numeric_placeholders = array_filter($path_sections, function ($section) {
      return (preg_match('/^%(.*)/', $section, $matches)
        && is_numeric($matches[1]));
    });
    if (!empty($numeric_placeholders)) {
      $errors[] = $this->t("Numeric placeholders may not be used. Please use plain placeholders (%).");
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    $errors += $this->validatePath($this->getOption('path'));

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo() {
    return Url::fromRoute($this->getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];
    $view_route_key = "$view_id.$display_id";

    // Check for overridden route names.
    $view_route_names = $this->getAlteredRouteNames();

    return (isset($view_route_names[$view_route_key]) ? $view_route_names[$view_route_key] : "views.$view_route_key");
  }

  /**
   * {@inheritdoc}
   */
  public function getAlteredRouteNames() {
    return $this->state->get('views.view_route_names') ?: array();
  }

}
