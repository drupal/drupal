<?php

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
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
abstract class PathPluginBase extends DisplayPluginBase implements DisplayRouterInterface, DisplayMenuInterface {

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
   * {@inheritdoc}
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
    return $menu && $menu['type'] == 'default tab' && !empty($tab_options['type']) && $tab_options['type'] != 'none';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase:defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = ['default' => ''];
    $options['route_name'] = ['default' => ''];

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
    $defaults = [
      '_controller' => 'Drupal\views\Routing\ViewPageController::handle',
      '_title_callback' => 'Drupal\views\Routing\ViewPageController::getTitle',
      'view_id' => $view_id,
      'display_id' => $display_id,
      '_view_display_show_admin_links' => $this->getOption('show_admin_links'),
    ];

    // @todo How do we apply argument validation?
    $path = $this->getOption('path');

    // @todo Figure out validation/argument loading.
    // Replace % with %views_arg for menu autoloading and add to the
    // page arguments so the argument actually comes through.
    $arg_counter = 0;

    $argument_ids = array_keys((array) $this->getOption('arguments'));
    $total_arguments = count($argument_ids);

    $argument_map = [];

    $bits = [];
    if (is_string($path)) {
      $bits = explode('/', $path);
      // Replace arguments in the views UI (defined via %) with parameters in
      // routes (defined via {}). As a name for the parameter use arg_$key, so
      // it can be pulled in the views controller from the request.
      foreach ($bits as $pos => $bit) {
        if ($bit == '%') {
          // Generate the name of the parameter using the key of the argument
          // handler.
          $arg_id = 'arg_' . $arg_counter++;
          $bits[$pos] = '{' . $arg_id . '}';
          $argument_map[$arg_id] = $arg_id;
        }
        elseif (str_starts_with($bit, '%')) {
          // Use the name defined in the path.
          $parameter_name = substr($bit, 1);
          $arg_id = 'arg_' . $arg_counter++;
          $argument_map[$arg_id] = $parameter_name;
          $bits[$pos] = '{' . $parameter_name . '}';
        }
      }
    }

    // Add missing arguments not defined in the path, but added as handler.
    while (($total_arguments - $arg_counter) > 0) {
      $arg_id = 'arg_' . $arg_counter++;
      $bit = '{' . $arg_id . '}';
      // In contrast to the previous loop add the defaults here, as % was not
      // specified, which means the argument is optional.
      $defaults[$arg_id] = NULL;
      $argument_map[$arg_id] = $arg_id;
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
    $route->setOption('_view_display_plugin_id', $this->getPluginId());
    $route->setOption('_view_display_plugin_class', static::class);
    $route->setOption('_view_display_show_admin_links', $this->getOption('show_admin_links'));

    // Store whether the view will return a response.
    $route->setOption('returns_response', !empty($this->getPluginDefinition()['returns_response']));

    // Symfony 4 requires that UTF-8 route patterns have the "utf8" option set
    $route->setOption('utf8', TRUE);

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
    return ["$view_id.$display_id" => $route_name];
  }

  /**
   * Determines whether the view overrides the given route.
   *
   * @param string $view_path
   *   The path of the view.
   * @param \Symfony\Component\Routing\Route $view_route
   *   The route of the view.
   * @param \Symfony\Component\Routing\Route $route
   *   The route itself.
   *
   * @return bool
   *   TRUE, when the view should override the given route.
   */
  protected function overrideApplies($view_path, Route $view_route, Route $route) {
    return (!$route->hasRequirement('_format') || $route->getRequirement('_format') === 'html')
      && $this->overrideAppliesPathAndMethod($view_path, $view_route, $route);
  }

  /**
   * Determines whether an override for the path and method should happen.
   *
   * @param string $view_path
   *   The path of the view.
   * @param \Symfony\Component\Routing\Route $view_route
   *   The route of the view.
   * @param \Symfony\Component\Routing\Route $route
   *   The route itself.
   *
   * @return bool
   *   TRUE, when the view should override the given route.
   */
  protected function overrideAppliesPathAndMethod($view_path, Route $view_route, Route $route) {
    // Find all paths which match the path of the current display..
    $route_path = RouteCompiler::getPathWithoutDefaults($route);
    $route_path = RouteCompiler::getPatternOutline($route_path);

    // Ensure that we don't override a route which is already controlled by
    // views.
    return !$route->hasDefault('view_id')
    && ('/' . $view_path == $route_path)
    // Also ensure that we don't override for example REST routes.
    && (!$route->getMethods() || in_array('GET', $route->getMethods()));
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $view_route_names = [];
    $view_path = $this->getPath();
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];
    $view_route = $this->getRoute($view_id, $display_id);

    foreach ($collection->all() as $name => $route) {
      if ($this->overrideApplies($view_path, $view_route, $route)) {
        $parameters = $route->compile()->getPathVariables();

        // @todo Figure out whether we need to merge some settings (like
        // requirements).

        // Replace the existing route with a new one based on views.
        $original_route = $collection->get($name);
        $collection->remove($name);

        $path = $view_route->getPath();
        // Replace the path with the original parameter names and add a mapping.
        $argument_map = [];
        // We assume that the numeric ids of the parameters match the one from
        // the view argument handlers.
        foreach ($parameters as $position => $parameter_name) {
          $path = str_replace('{arg_' . $position . '}', '{' . $parameter_name . '}', $path);
          $argument_map['arg_' . $position] = $parameter_name;
        }
        // Copy the original options from the route, so for example we ensure
        // that parameter conversion options is carried over.
        $view_route->setOptions($view_route->getOptions() + $original_route->getOptions());

        if ($original_route->hasDefault('_title_callback')) {
          $view_route->setDefault('_title_callback', $original_route->getDefault('_title_callback'));
        }

        // Set the corrected path and the mapping to the route object.
        $view_route->setOption('_view_argument_map', $argument_map);
        $view_route->setPath($path);

        $collection->add($name, $view_route);
        $view_route_names[$view_id . '.' . $display_id] = $name;
      }
    }

    return $view_route_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuLinks() {
    $links = [];

    // Replace % with the link to our standard views argument loader
    // views_arg_load -- which lives in views.module.

    $bits = explode('/', $this->getOption('path'));

    // Replace % with %views_arg for menu autoloading and add to the
    // page arguments so the argument actually comes through.
    if (in_array('%', $bits, TRUE)) {
      // If a view requires any arguments we cannot create a static menu link.
      return [];
    }
    $path = implode('/', $bits);
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];
    $view_id_display = "{$view_id}.{$display_id}";
    $menu_link_id = 'views.' . str_replace('/', '.', $view_id_display);

    if ($path) {
      $menu = $this->getOption('menu');
      if (!empty($menu['type']) && $menu['type'] == 'normal') {
        $links[$menu_link_id] = [];
        // Some views might override existing paths, so we have to set the route
        // name based upon the altering.
        $links[$menu_link_id] = [
          'route_name' => $this->getRouteName(),
          // Identify URL embedded arguments and correlate them to a handler.
          'load arguments'  => [$this->view->storage->id(), $this->display['id'], '%index'],
          'id' => $menu_link_id,
        ];
        $links[$menu_link_id]['title'] = $menu['title'];
        $links[$menu_link_id]['description'] = $menu['description'];
        $links[$menu_link_id]['parent'] = $menu['parent'];
        $links[$menu_link_id]['enabled'] = $menu['enabled'];
        $links[$menu_link_id]['expanded'] = $menu['expanded'];

        if (isset($menu['weight'])) {
          $links[$menu_link_id]['weight'] = intval($menu['weight']);
        }

        // Insert item into the proper menu.
        $links[$menu_link_id]['menu_name'] = $menu['menu_name'];
        // Keep track of where we came from.
        $links[$menu_link_id]['metadata'] = [
          'view_id' => $view_id,
          'display_id' => $display_id,
        ];
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['page'] = [
      'title' => $this->t('Page settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $path = strip_tags($this->getOption('path'));

    if (empty($path)) {
      $path = $this->t('No path is set');
    }
    else {
      $path = '/' . $path;
    }

    $options['path'] = [
      'category' => 'page',
      'title' => $this->t('Path'),
      'value' => Unicode::truncate($path, 24, FALSE, TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'path':
        $form['#title'] .= $this->t('The menu path or URL of this view');
        $form['path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Path'),
          '#description' => $this->t('This view will be displayed by visiting this path on your site. You may use "%" or named route parameters like "%node" in your URL to represent values that will be used for contextual filters: For example, "node/%node/feed" or "view_path/%". Named route parameters are required when this path matches an existing path. For example, paths such as "taxonomy/term/%taxonomy_term" or "user/%user/custom-view".'),
          '#default_value' => $this->getOption('path'),
          '#field_prefix' => '<span dir="ltr">' . Url::fromRoute('<none>', [], ['absolute' => TRUE])->toString() . '</span>&lrm;',
          '#attributes' => ['dir' => LanguageInterface::DIRECTION_LTR],
          // Account for the leading backslash.
          '#maxlength' => 254,
        ];
        break;
    }
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
    $errors = [];
    if (str_starts_with($path, '%')) {
      $errors[] = $this->t('"%" may not be used for the first segment of a path.');
    }

    $parsed_url = UrlHelper::parse($path);
    if (empty($parsed_url['path'])) {
      $errors[] = $this->t('Path is empty.');
    }

    if (!empty($parsed_url['query'])) {
      $errors[] = $this->t('No query allowed.');
    }

    if (!parse_url('internal:/' . $path)) {
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
      $errors[] = $this->t("Numeric placeholders may not be used. Use plain placeholders (%).");
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

    return $view_route_names[$view_route_key] ?? "view.$view_route_key";
  }

  /**
   * {@inheritdoc}
   */
  public function getAlteredRouteNames() {
    return $this->state->get('views.view_route_names', []);
  }

  /**
   * {@inheritdoc}
   */
  public function remove() {
    $menu_links = $this->getMenuLinks();
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    foreach ($menu_links as $menu_link_id => $menu_link) {
      $menu_link_manager->removeDefinition("views_view:$menu_link_id");
    }
  }

}
