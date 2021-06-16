<?php

namespace Drupal\block\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a list of block plugins to be added to the layout.
 */
class BlockLibraryController extends ControllerBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * Constructs a BlockLibraryController object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager.
   */
  public function __construct(BlockManagerInterface $block_manager, LazyContextRepository $context_repository, RouteMatchInterface $route_match, LocalActionManagerInterface $local_action_manager) {
    $this->blockManager = $block_manager;
    $this->routeMatch = $route_match;
    $this->localActionManager = $local_action_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_action')
    );
  }

  /**
   * Shows a list of blocks that can be added to a theme's layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $theme
   *   Theme key of the block list.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listBlocks(Request $request, $theme) {
    // Since modals do not render any other part of the page, we need to render
    // them manually as part of this listing.
    if ($request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal') {
      $build['local_actions'] = $this->buildLocalActions();
    }

    $headers = [
      ['data' => $this->t('Block')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Operations')],
    ];

    $region = $request->query->get('region');
    $weight = $request->query->get('weight');

    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getFilteredDefinitions('block_ui', $this->contextRepository->getAvailableContexts(), [
      'theme' => $theme,
      'region' => $region,
    ]);
    // Order by category, and then by admin label.
    $definitions = $this->blockManager->getSortedDefinitions($definitions);
    // Filter out definitions that are not intended to be placed by the UI.
    $definitions = array_filter($definitions, function (array $definition) {
      return empty($definition['_block_ui_hidden']);
    });

    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['admin_label'],
        ],
      ];
      $row['category']['data'] = $plugin_definition['category'];
      $links['add'] = [
        'title' => $this->t('Place block'),
        'url' => Url::fromRoute('block.admin_add', ['plugin_id' => $plugin_id, 'theme' => $theme]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];
      if ($region) {
        $links['add']['query']['region'] = $region;
      }
      if (isset($weight)) {
        $links['add']['query']['weight'] = $weight;
      }
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['#attached']['library'][] = 'block/drupal.block.admin';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the local actions for this listing.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions() {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

}
