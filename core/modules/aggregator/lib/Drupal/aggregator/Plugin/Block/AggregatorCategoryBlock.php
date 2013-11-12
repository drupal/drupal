<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Block\AggregatorCategoryBlock.
 */

namespace Drupal\aggregator\Plugin\Block;

use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Aggregator category' block for the latest items in a category.
 *
 * @Block(
 *   id = "aggregator_category_block",
 *   admin_label = @Translation("Aggregator category"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class AggregatorCategoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorageController;

  /**
   * Constructs an AggregatorFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $connection, CategoryStorageControllerInterface $category_storage_controller) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->categoryStorageController = $category_storage_controller;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will contain 10 feed items.
    return array(
      'cid' => 0,
      'block_count' => 10,
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
    $result = $this->connection->query('SELECT cid, title FROM {aggregator_category} ORDER BY title');
    $options = array();
    foreach ($result as $category) {
      $options[$category->cid] = check_plain($category->title);
    }

    $form['cid'] = array(
      '#type' => 'select',
      '#title' => t('Select the category that should be displayed'),
      '#default_value' => $this->configuration['cid'],
      '#options' => $options,
    );
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of news items in block'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => drupal_map_assoc(range(2, 20)),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['cid'] = $form_state['values']['cid'];
    $this->configuration['block_count'] = $form_state['values']['block_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cid = $this->configuration['cid'];
    if ($category = $this->categoryStorageController->load($cid)) {
      $result = $this->connection->queryRange('SELECT i.* FROM {aggregator_category_item} ci LEFT JOIN {aggregator_item} i ON ci.iid = i.iid WHERE ci.cid = :cid ORDER BY i.timestamp DESC, i.iid DESC', 0, $this->configuration['block_count'], array(':cid' => $category->cid));
      $more_link = array(
        '#theme' => 'more_link',
        '#url' => 'aggregator/categories/' . $category->cid,
        '#title' => t("View this category's recent news."),
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
      return array();
    }
  }

}
