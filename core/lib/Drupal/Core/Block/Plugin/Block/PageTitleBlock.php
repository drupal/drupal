<?php

namespace Drupal\Core\Block\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block to display the page title.
 */
#[Block(
  id: "page_title_block",
  admin_label: new TranslatableMarkup("Page title"),
  forms: [
    'settings_tray' => FALSE,
  ]
)]
class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * Constructs a new PageTitleBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Controller\TitleResolverInterface|null $titleResolver
   *   The title resolver.
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requestStack
   *   The request stack.
   * @param \Drupal\Core\Controller\TitleResolverInterface|null $baseRouteTitleResolver
   *   The base route title resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ?TitleResolverInterface $titleResolver,
    protected ?RouteMatchInterface $routeMatch,
    protected ?RequestStack $requestStack,
    protected ?TitleResolverInterface $baseRouteTitleResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$this->titleResolver || !$this->routeMatch || !$this->requestStack || !$this->baseRouteTitleResolver) {
      @trigger_error('Calling PageTitleBlock::__construct() without the $titleResolver, $routeMatch, $requestStack, and $baseRouteTitleResolver arguments is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3397210', E_USER_DEPRECATED);
      $this->titleResolver = \Drupal::service('title_resolver');
      $this->routeMatch = \Drupal::service('current_route_match');
      $this->requestStack = \Drupal::service('request_stack');
      $this->baseRouteTitleResolver = \Drupal::service('base_route_title_resolver');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('title_resolver'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('base_route_title_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'base_route_title' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $title = $this->title;
    if ($this->configuration['base_route_title']) {
      $base_route_title = $this->getTitleBasedOnBaseRoute();
      if (!is_null($base_route_title)) {
        $title = $base_route_title;
      }
    }
    return [
      '#type' => 'page_title',
      '#title' => $title,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) : array {
    $form['base_route_title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Title to be displayed'),
      '#options' => [
        0 => $this->t('Current page title'),
        1 => $this->t('Section page title'),
      ],
      '#default_value' => (int) $this->configuration['base_route_title'],
      '#description' => $this->t('Choose whether to display the title of the current page or the current section. The section page title is preferred if the title is displayed before local tasks and if it is displayed after local tasks then the current page title is preferred.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) : void {
    $this->configuration['base_route_title'] = (bool) $form_state->getValue('base_route_title');
  }

  /**
   * Gets title based on base route.
   *
   * @return array|string|null|\Stringable
   *   The title based on base route.
   */
  private function getTitleBasedOnBaseRoute(): array|string|null|\Stringable {
    $controller_title = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->routeMatch->getRouteObject());

    // Controller render arrays using `#title` take precedent over the title
    // resolvers.
    if ((string) $this->titleToString($controller_title) !== (string) $this->titleToString($this->title)) {
      return $this->title;
    }

    $base_route_title = $this->baseRouteTitleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->routeMatch->getRouteObject());
    if (!is_null($base_route_title)) {
      // If the titles are equal, return the original title.
      if ((string) $this->titleToString($base_route_title) === (string) $this->titleToString($this->title)) {
        return $this->title;
      }

      return $this->t('@section_title<span class="visually-hidden">: @current_title</span>', [
        '@section_title' => $this->titleToString($base_route_title),
        '@current_title' => $this->titleToString($this->title),
      ]);
    }

    return $this->title;
  }

  /**
   * Converts title to string.
   *
   * @param array|string|null|\Stringable $title
   *   A title that could be an array, string or stringable object.
   *
   * @return string|\Stringable
   */
  private function titleToString(array|string|null|\Stringable $title): string|\Stringable {
    if (is_array($title)) {
      $title = \Drupal::service('renderer')->render($title);
    }

    return $title ?? '';
  }

}
