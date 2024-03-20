<?php

namespace Drupal\views\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplay;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The plugin that handles a feed, such as RSS or atom.
 *
 * @ingroup views_display_plugins
 */
#[ViewsDisplay(
  id: "feed",
  title: new TranslatableMarkup("Feed"),
  admin: new TranslatableMarkup("Feed"),
  help: new TranslatableMarkup("Display the view as a feed, such as an RSS feed."),
  uses_route: TRUE,
  returns_response: TRUE
)]
class Feed extends PathPluginBase implements ResponseDisplayPluginInterface {

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @var bool
   */
  protected $ajaxEnabled = FALSE;

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
  public function getType() {
    return 'feed';
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    // Set up an empty response, so for example RSS can set the proper
    // Content-Type header.
    $response = new CacheableResponse('', 200);
    $build['#response'] = $response;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);

    if (empty($output)) {
      throw new NotFoundHttpException();
    }

    $response->setContent($output);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);

    // Set the HTTP headers and status code on the response if any bubbled.
    if (!empty($build['#attached']['http_header'])) {
      static::setHeaders($response, $build['#attached']['http_header']);
    }

    return $response;
  }

  /**
   * Sets headers on a response object.
   *
   * @param \Drupal\Core\Cache\CacheableResponse $response
   *   The HTML response to update.
   * @param array $headers
   *   The headers to set, as an array. The items in this array should be as
   *   follows:
   *   - The header name.
   *   - The header value.
   *   - (optional) Whether to replace a current value with the new one, or add
   *     it to the others. If the value is not replaced, it will be appended,
   *     resulting in a header like this: 'Header: value1,value2'.
   *
   * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::setHeaders()
   */
  protected static function setHeaders(CacheableResponse $response, array $headers): void {
    foreach ($headers as $values) {
      $name = $values[0];
      $value = $values[1];
      $replace = !empty($values[2]);

      // Drupal treats the HTTP response status code like a header, even though
      // it really is not.
      if (strtolower($name) === 'status') {
        $response->setStatusCode($value);
      }
      else {
        $response->headers->set($name, $value, $replace);
      }
    }
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
  public function preview() {
    $output = $this->view->render();

    if (!empty($this->view->live_preview)) {
      $output = [
        '#prefix' => '<pre>',
        '#plain_text' => $this->renderer->renderRoot($output),
        '#suffix' => '</pre>',
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->view->style_plugin->render($this->view->result);

    $this->applyDisplayCacheabilityMetadata($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections($section = NULL) {
    $sections = parent::defaultableSections($section);

    if (in_array($section, ['style', 'row'])) {
      return FALSE;
    }

    // Tell views our sitename_title option belongs in the title section.
    if ($section == 'title') {
      $sections[] = 'sitename_title';
    }
    elseif (!$section) {
      $sections['title'][] = 'sitename_title';
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = ['default' => []];

    // Overrides for standard stuff.
    $options['style']['contains']['type']['default'] = 'rss';
    $options['style']['contains']['options']['default'] = ['description' => ''];
    $options['sitename_title']['default'] = FALSE;
    $options['row']['contains']['type']['default'] = 'rss_fields';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function newDisplay() {
    parent::newDisplay();

    // Set the default row style. Ideally this would be part of the option
    // definition, but in this case it's dependent on the view's base table,
    // which we don't know until init().
    if (empty($this->options['row']['type']) || $this->options['row']['type'] === 'rss_fields') {
      $row_plugins = Views::fetchPluginNames('row', $this->getType(), [$this->view->storage->get('base_table')]);
      $default_row_plugin = key($row_plugins);

      $options = $this->getOption('row');
      $options['type'] = $default_row_plugin;
      $this->setOption('row', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'path' type, we'll still *call* our
    // category 'page' but let's override it so it says feed settings.
    $categories['page'] = [
      'title' => $this->t('Feed settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    if ($this->getOption('sitename_title')) {
      $options['title']['value'] = $this->t('Using the site name');
    }

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = [
      'category' => 'page',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here.
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'title':
        $title = $form['title'];
        // A little juggling to move the 'title' field beyond our checkbox.
        unset($form['title']);
        $form['sitename_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use the site name for the title'),
          '#default_value' => $this->getOption('sitename_title'),
        ];
        $form['title'] = $title;
        $form['title']['#states'] = [
          'visible' => [
            ':input[name="sitename_title"]' => ['checked' => FALSE],
          ],
        ];
        break;

      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = [];
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          // @todo The display plugin should have display_title and id as well.
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = [
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('The feed icon will be available only to the selected displays.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        ];
        break;

      case 'path':
        $form['path']['#description'] = $this->t('This view will be displayed by visiting this path on your site. It is recommended that the path be something like "path/%/%/feed" or "path/%/%/rss.xml", putting one % in the path for each contextual filter you have defined in the view.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'title':
        $this->setOption('sitename_title', $form_state->getValue('sitename_title'));
        break;

      case 'displays':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the feed style; it may put in meta information, and/or
    // attach a feed icon.
    $view->setArguments($this->view->args);
    $view->setDisplay($this->display['id']);
    $view->buildTitle();
    if ($plugin = $view->display_handler->getPlugin('style')) {
      $plugin->attachTo($build, $display_id, $view->getUrl(), $view->getTitle());
      foreach ($view->feedIcons as $feed_icon) {
        $this->view->feedIcons[] = $feed_icon;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return TRUE;
  }

}
