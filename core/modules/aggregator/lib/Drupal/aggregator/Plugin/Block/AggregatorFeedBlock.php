<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Block\AggregatorFeedBlock.
 */

namespace Drupal\aggregator\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageControllerInterface;
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
   * The entity storage controller for feeds.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an AggregatorFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller for feeds.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageControllerInterface $storage_controller, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storageController = $storage_controller;
    $this->connection = $connection;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorageController('aggregator_feed'),
      $container->get('database')
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
    $feeds = $this->storageController->loadMultiple();
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
    if ($feed = $this->storageController->load($this->configuration['feed'])) {
      $result = $this->connection->queryRange("SELECT * FROM {aggregator_item} WHERE fid = :fid ORDER BY timestamp DESC, iid DESC", 0, $this->configuration['block_count'], array(':fid' => $feed->id()));
      $more_link = array(
        '#theme' => 'more_link',
        '#url' => 'aggregator/sources/' . $feed->id(),
        '#title' => t("View this feed's recent news."),
      );
      $read_more = drupal_render($more_link);
      $items = array();
      foreach ($result as $item) {
        $aggregator_block_item = array(
          '#theme' => 'aggregator_block_item',
          '#item' => $item,
        );
        $items[] = drupal_render($aggregator_block_item);
      }
      // Only display the block if there are items to show.
      if (count($items) > 0) {
        $item_list = array(
          '#theme' => 'item_list',
          '#items' => $items,
        );
        return array(
          '#children' => drupal_render($item_list) . $read_more,
        );
      }
    }
  }

}
