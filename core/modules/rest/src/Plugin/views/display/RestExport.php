<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\display\RestExport.
 */

namespace Drupal\rest\Plugin\views\display;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "rest_export",
 *   title = @Translation("REST export"),
 *   help = @Translation("Create a REST export resource."),
 *   uses_route = TRUE,
 *   admin = @Translation("REST export"),
 *   returns_response = TRUE
 * )
 */
class RestExport extends PathPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAJAX.
   */
  protected $usesAJAX = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesPager.
   */
  protected $usesPager = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesMore.
   */
  protected $usesMore = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
   */
  protected $usesAreas = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
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
  protected $mimeType;

  /**
   * The renderer
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $state);

    $this->renderer = $renderer;
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
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL) {
    parent::initDisplay($view, $display, $options);

    $request_content_type = $this->view->getRequest()->getRequestFormat();
    // Only use the requested content type if it's not 'html'. If it is then
    // default to 'json' to aid debugging.
    // @todo Remove the need for this when we have better content negotiation.
    if ($request_content_type != 'html') {
      $this->setContentType($request_content_type);
    }
    // If the requested content type is 'html' and the default 'json' is not
    // selected as a format option in the view display, fallback to the first
    // format in the array.
    elseif (!empty($options['style']['options']['formats']) && !isset($options['style']['options']['formats'][$this->getContentType()])) {
      $this->setContentType(reset($options['style']['options']['formats']));
    }

    $this->setMimeType($this->view->getRequest()->getMimeType($this->contentType));
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'data';
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return FALSE;
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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

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

    unset($categories['page'], $categories['exposed']);
    // Hide some settings, as they aren't useful for pure data output.
    unset($options['show_admin_links'], $options['analyze-theme']);

    $categories['path'] = array(
      'title' => $this->t('Path settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $options['path']['category'] = 'path';
    $options['path']['title'] = $this->t('Path');

    // Remove css/exposed form settings, as they are not used for the data
    // display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);
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
      // REST exports should only respond to get methods.
      $route->setMethods(['GET']);

      // Format as a string using pipes as a delimiter.
      $requirements['_format'] = implode('|', $style_plugin->getFormats());

      // Add the new requirements to the route.
      $route->addRequirements($requirements);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();

    $output = $this->view->render();

    $header = [];
    $header['Content-type'] = $this->getMimeType();

    $response = new CacheableResponse($this->renderer->renderRoot($output), 200);
    $cache_metadata = CacheableMetadata::createFromRenderArray($output);

    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = array();
    $build['#markup'] = $this->view->style_plugin->render();

    // Wrap the output in a pre tag if this is for a live preview.
    if (!empty($this->view->live_preview)) {
      $build['#prefix'] = '<pre>';
      $build['#markup'] = SafeMarkup::checkPlain($build['#markup']);
      $build['#suffix'] = '</pre>';
    }

    // Defaults for bubbleable rendering metadata.
    $build['#cache']['tags'] = isset($build['#cache']['tags']) ? $build['#cache']['tags'] : array();
    $build['#cache']['max-age'] = isset($build['#cache']['max-age']) ? $build['#cache']['max-age'] : Cache::PERMANENT;

    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache */
    $cache = $this->getPlugin('cache');

    $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $cache->getCacheTags());
    $build['#cache']['max-age'] = Cache::mergeMaxAges($build['#cache']['max-age'], $cache->getCacheMaxAge());

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

}
