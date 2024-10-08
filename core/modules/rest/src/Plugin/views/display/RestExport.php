<?php

namespace Drupal\rest\Plugin\views\display;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplay;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 */
#[ViewsDisplay(
  id: "rest_export",
  title: new TranslatableMarkup("REST export"),
  help: new TranslatableMarkup("Create a REST export resource."),
  admin: new TranslatableMarkup("REST export"),
  uses_route: TRUE,
  returns_response: TRUE
)]
class RestExport extends PathPluginBase implements ResponseDisplayPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * Overrides the content type of the data response, if needed.
   *
   * @var string
   */
  protected $contentType = 'json';

  /**
   * The mime type for the response.
   *
   * @var string
   */
  protected $mimeType = 'application/json';

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The collector of authentication providers.
   *
   * @var \Drupal\Core\Authentication\AuthenticationCollectorInterface
   */
  protected $authenticationCollector;

  /**
   * The authentication providers, like 'cookie' and 'basic_auth'.
   *
   * @var string[]
   */
  protected $authenticationProviderIds;

  /**
   * The serialization format providers, keyed by format.
   *
   * @var string[]
   */
  protected $formatProviders;

  /**
   * Constructs a RestExport object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param string[] $authentication_providers
   *   The authentication providers, keyed by ID.
   * @param string[] $serializer_format_providers
   *   The serialization format providers, keyed by format.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state, RendererInterface $renderer, array $authentication_providers, array $serializer_format_providers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $state);

    $this->renderer = $renderer;
    // $authentication_providers as defined in
    // \Drupal\Core\DependencyInjection\Compiler\AuthenticationProviderPass
    // and as such it is an array, with authentication providers (cookie,
    // basic_auth) as keys and modules providing those as values (user,
    // basic_auth).
    $this->authenticationProviderIds = array_keys($authentication_providers);
    $this->formatProviders = $serializer_format_providers;
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
      $container->get('state'),
      $container->get('renderer'),
      $container->getParameter('authentication_providers'),
      $container->getParameter('serializer.format_providers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initDisplay(ViewExecutable $view, array &$display, ?array &$options = NULL) {
    parent::initDisplay($view, $display, $options);

    // If the default 'json' format is not selected as a format option in the
    // view display, fallback to the first format available for the default.
    if (!empty($options['style']['options']['formats']) && !isset($options['style']['options']['formats'][$this->getContentType()])) {
      $default_format = reset($options['style']['options']['formats']);
      $this->setContentType($default_format);
    }

    // Only use the requested content type if it's not 'html'. This allows
    // still falling back to the default for things like views preview.
    $request_content_type = $this->view->getRequest()->getRequestFormat();

    if ($request_content_type !== 'html') {
      $this->setContentType($request_content_type);
    }

    $this->setMimeType($this->view->getRequest()->getMimeType($this->getContentType()));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'data';
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return FALSE;
  }

  /**
   * Sets the request content type.
   *
   * @param string $mime_type
   *   The response mime type. E.g. 'application/json'.
   */
  public function setMimeType($mime_type) {
    $this->mimeType = $mime_type;
  }

  /**
   * Gets the mime type.
   *
   * This will return any overridden mime type, otherwise returns the mime type
   * from the request.
   *
   * @return string
   *   The response mime type. E.g. 'application/json'.
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * Sets the content type.
   *
   * @param string $content_type
   *   The content type machine name. E.g. 'json'.
   */
  public function setContentType($content_type) {
    $this->contentType = $content_type;
  }

  /**
   * Gets the content type.
   *
   * @return string
   *   The content type machine name. E.g. 'json'.
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * Gets the auth options available.
   *
   * @return string[]
   *   An array to use as value for "#options" in the form element.
   */
  public function getAuthOptions() {
    return array_combine($this->authenticationProviderIds, $this->authenticationProviderIds);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Options for REST authentication.
    $options['auth'] = ['default' => []];

    // Set the default style plugin to 'json'.
    $options['style']['contains']['type']['default'] = 'serializer';
    $options['row']['contains']['type']['default'] = 'data_entity';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings, as they are not used for the data display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Authentication.
    $auth = $this->getOption('auth') ? implode(', ', $this->getOption('auth')) : $this->t('No authentication is set');

    unset($categories['page'], $categories['exposed']);
    // Hide some settings, as they aren't useful for pure data output.
    unset($options['show_admin_links'], $options['analyze-theme']);

    $categories['path'] = [
      'title' => $this->t('Path settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $options['path']['category'] = 'path';
    $options['path']['title'] = $this->t('Path');
    $options['auth'] = [
      'category' => 'path',
      'title' => $this->t('Authentication'),
      'value' => Unicode::truncate($auth, 24, FALSE, TRUE),
    ];

    // Remove css/exposed form settings, as they are not used for the data
    // display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($form_state->get('section') === 'auth') {
      $form['#title'] .= $this->t('The supported authentication methods for this view');
      $form['auth'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Authentication methods'),
        '#description' => $this->t('These are the supported authentication providers for this view. When this view is requested, the client will be forced to authenticate with one of the selected providers. Make sure you set the appropriate requirements at the <em>Access</em> section since the Authentication System will fallback to the anonymous user if it fails to authenticate. For example: require Access: Role | Authenticated User.'),
        '#options' => $this->getAuthOptions(),
        '#default_value' => $this->getOption('auth'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'auth') {
      $this->setOption('auth', array_keys(array_filter($form_state->getValue('auth'))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function collectRoutes(RouteCollection $collection) {
    parent::collectRoutes($collection);
    $view_id = $this->view->storage->id();
    $display_id = $this->display['id'];

    if ($route = $collection->get("view.$view_id.$display_id")) {
      $style_plugin = $this->getPlugin('style');

      // REST exports should only respond to GET methods.
      $route->setMethods(['GET']);

      $formats = $style_plugin->getFormats();

      // If there are no configured formats, add all formats that serialization
      // is known to support.
      if (!$formats) {
        $formats = $this->getFormatOptions();
      }

      // Format as a string using pipes as a delimiter.
      $route->setRequirement('_format', implode('|', $formats));

      // Add authentication to the route if it was set. If no authentication was
      // set, the default authentication will be used, which is cookie based by
      // default.
      $auth = $this->getOption('auth');
      if (!empty($auth)) {
        $route->setOption('_auth', $auth);
      }
    }
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
    $route_has_format = $route->hasRequirement('_format');
    $route_formats = $route_has_format ? explode('|', $route->getRequirement('_format')) : [];
    $view_route_formats = $view_route->hasRequirement('_format') ? explode('|', $view_route->getRequirement('_format')) : [];
    return $this->overrideAppliesPathAndMethod($view_path, $view_route, $route)
      && (!$route_has_format || array_intersect($route_formats, $view_route_formats) != []);
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    // Setup an empty response so headers can be added as needed during views
    // rendering and processing.
    $response = new CacheableResponse('', 200);
    $build['#response'] = $response;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);

    $response->setContent($output);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);

    $response->headers->set('Content-type', $build['#content_type']);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();

    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];
    $build['#markup'] = $this->renderer->executeInRenderContext(new RenderContext(), function () {
      return $this->view->style_plugin->render();
    });

    $this->view->element['#content_type'] = $this->getMimeType();
    $this->view->element['#cache_properties'][] = '#content_type';

    // Encode and wrap the output in a pre tag if this is for a live preview.
    if (!empty($this->view->live_preview)) {
      $build['#prefix'] = '<pre>';
      $build['#plain_text'] = $build['#markup'];
      $build['#suffix'] = '</pre>';
      unset($build['#markup']);
    }
    else {
      // This display plugin is for returning non-HTML formats. However, we
      // still invoke the renderer to collect cacheability metadata. Because the
      // renderer is designed for HTML rendering, it filters #markup for XSS
      // unless it is already known to be safe, but that filter only works for
      // HTML. Therefore, we mark the contents as safe to bypass the filter. So
      // long as we are returning this in a non-HTML response,
      // this is safe, because an XSS attack only works when executed by an HTML
      // agent.
      // @todo Decide how to support non-HTML in the render API in
      //   https://www.drupal.org/node/2501313.
      $build['#markup'] = ViewsRenderPipelineMarkup::create($build['#markup']);
    }

    parent::applyDisplayCacheabilityMetadata($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * The DisplayPluginBase preview method assumes we will be returning a render
   * array. The data plugin will already return the serialized string.
   */
  public function preview() {
    return $this->view->render();
  }

  /**
   * Returns an array of format options.
   *
   * @return string[]
   *   An array of format options. Both key and value are the same.
   */
  protected function getFormatOptions() {
    $formats = array_keys($this->formatProviders);
    return array_combine($formats, $formats);
  }

}
