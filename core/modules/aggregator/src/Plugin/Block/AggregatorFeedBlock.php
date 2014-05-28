<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Block\AggregatorFeedBlock.
 */

namespace Drupal\aggregator\Plugin\Block;

use Drupal\aggregator\FeedStorageInterface;
use Drupal\aggregator\ItemStorageInterface;
use Drupal\block\BlockBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Aggregator feed' block with the latest items from the feed.
 *
 * @Block(
 *   id = "aggregator_feed_block",
 *   admin_label = @Translation("Aggregator feed"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class AggregatorFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage for feeds.
   *
   * @var \Drupal\aggregator\FeedStorageInterface
   */
  protected $feedStorage;

  /**
   * The entity storage for items.
   *
   * @var \Drupal\aggregator\ItemStorageInterface
   */
  protected $itemStorage;

  /**
   * The entity query object for feed items.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $itemQuery;

  /**
   * Constructs an AggregatorFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\aggregator\FeedStorageInterface $feed_storage
   *   The entity storage for feeds.
   * @param \Drupal\aggregator\ItemStorageInterface $item_storage
   *   The entity storage for feed items.
   * @param \Drupal\Core\Entity\Query\QueryInterface $item_query
   *   The entity query object for feed items.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FeedStorageInterface $feed_storage, ItemStorageInterface $item_storage, QueryInterface $item_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->feedStorage = $feed_storage;
    $this->itemStorage = $item_storage;
    $this->itemQuery = $item_query;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('aggregator_feed'),
      $container->get('entity.manager')->getStorage('aggregator_item'),
      $container->get('entity.query')->get('aggregator_item')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will contain 10 feed items.
    return array(
      'block_count' => 10,
      'feed' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Only grant access to users with the 'access news feeds' permission.
    return $account->hasPermission('access news feeds');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $feeds = $this->feedStorage->loadMultiple();
    $options = array();
    foreach ($feeds as $feed) {
      $options[$feed->id()] = $feed->label();
    }
    $form['feed'] = array(
      '#type' => 'select',
      '#title' => t('Select the feed that should be displayed'),
      '#default_value' => $this->configuration['feed'],
      '#options' => $options,
    );
    $range = range(2, 20);
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of news items in block'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => array_combine($range, $range),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_count'] = $form_state['values']['block_count'];
    $this->configuration['feed'] = $form_state['values']['feed'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load the selected feed.
    if ($feed = $this->feedStorage->load($this->configuration['feed'])) {
      $result = $this->itemQuery
        ->condition('fid', $feed->id())
        ->range(0, $this->configuration['block_count'])
        ->sort('timestamp', 'DESC')
        ->sort('iid', 'DESC')
        ->execute();

      $items = $this->itemStorage->loadMultiple($result);

      $more_link = array(
        '#theme' => 'more_link',
        '#url' => 'aggregator/sources/' . $feed->id(),
        '#title' => t("View this feed's recent news."),
      );
      $read_more = drupal_render($more_link);
      $rendered_items = array();
      foreach ($items as $item) {
        $aggregator_block_item = array(
          '#theme' => 'aggregator_block_item',
          '#item' => $item,
        );
        $rendered_items[] = drupal_render($aggregator_block_item);
      }
      // Only display the block if there are items to show.
      if (count($rendered_items) > 0) {
        $item_list = array(
          '#theme' => 'item_list',
          '#items' => $rendered_items,
        );
        return array(
          '#children' => drupal_render($item_list) . $read_more,
        );
      }
    }
  }

}
