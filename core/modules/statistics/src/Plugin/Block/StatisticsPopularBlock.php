<?php

namespace Drupal\statistics\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\statistics\StatisticsStorageInterface;

/**
 * Provides a 'Popular content' block.
 *
 * @Block(
 *   id = "statistics_popular_block",
 *   admin_label = @Translation("Popular content")
 * )
 */
class StatisticsPopularBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The storage for statistics.
   *
   * @var \Drupal\statistics\StatisticsStorageInterface
   */
  protected $statisticsStorage;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a StatisticsPopularBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service
   * @param \Drupal\statistics\StatisticsStorageInterface $statistics_storage
   *   The storage for statistics.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer configuration array.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, StatisticsStorageInterface $statistics_storage, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->statisticsStorage = $statistics_storage;
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
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('statistics.storage.node'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'top_day_num' => 0,
      'top_all_num' => 0,
      'top_last_num' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Popular content block settings.
    $numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40];
    $numbers = ['0' => $this->t('Disabled')] + array_combine($numbers, $numbers);
    $form['statistics_block_top_day_num'] = [
      '#type' => 'select',
      '#title' => $this->t("Number of day's top views to display"),
      '#default_value' => $this->configuration['top_day_num'],
      '#options' => $numbers,
      '#description' => $this->t('How many content items to display in "day" list.'),
    ];
    $form['statistics_block_top_all_num'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of all time views to display'),
      '#default_value' => $this->configuration['top_all_num'],
      '#options' => $numbers,
      '#description' => $this->t('How many content items to display in "all time" list.'),
    ];
    $form['statistics_block_top_last_num'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of most recent views to display'),
      '#default_value' => $this->configuration['top_last_num'],
      '#options' => $numbers,
      '#description' => $this->t('How many content items to display in "recently viewed" list.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['top_day_num'] = $form_state->getValue('statistics_block_top_day_num');
    $this->configuration['top_all_num'] = $form_state->getValue('statistics_block_top_all_num');
    $this->configuration['top_last_num'] = $form_state->getValue('statistics_block_top_last_num');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [];

    if ($this->configuration['top_day_num'] > 0) {
      $nids = $this->statisticsStorage->fetchAll('daycount', $this->configuration['top_day_num']);
      if ($nids) {
        $content['top_day'] = $this->nodeTitleList($nids, $this->t("Today's:"));
        $content['top_day']['#suffix'] = '<br />';
      }
    }

    if ($this->configuration['top_all_num'] > 0) {
      $nids = $this->statisticsStorage->fetchAll('totalcount', $this->configuration['top_all_num']);
      if ($nids) {
        $content['top_all'] = $this->nodeTitleList($nids, $this->t('All time:'));
        $content['top_all']['#suffix'] = '<br />';
      }
    }

    if ($this->configuration['top_last_num'] > 0) {
      $nids = $this->statisticsStorage->fetchAll('timestamp', $this->configuration['top_last_num']);
      $content['top_last'] = $this->nodeTitleList($nids, $this->t('Last viewed:'));
      $content['top_last']['#suffix'] = '<br />';
    }

    return $content;
  }

  /**
   * Generates the ordered array of node links for build().
   *
   * @param int[] $nids
   *   An ordered array of node ids.
   * @param string $title
   *   The title for the list.
   *
   * @return array
   *   A render array for the list.
   */
  protected function nodeTitleList(array $nids, $title) {
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $items = [];
    foreach ($nids as $nid) {
      $node = $this->entityRepository->getTranslationFromContext($nodes[$nid]);
      $item = $node->toLink()->toRenderable();
      $this->renderer->addCacheableDependency($item, $node);
      $items[] = $item;
    }

    return [
      '#theme' => 'item_list__node',
      '#items' => $items,
      '#title' => $title,
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('node')->getListCacheTags(),
      ],
    ];
  }

}
