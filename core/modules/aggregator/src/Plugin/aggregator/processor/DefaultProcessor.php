<?php

namespace Drupal\aggregator\Plugin\aggregator\processor;

use Drupal\aggregator\Entity\Item;
use Drupal\aggregator\ItemStorageInterface;
use Drupal\aggregator\Plugin\AggregatorPluginSettingsBase;
use Drupal\aggregator\Plugin\ProcessorInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default processor implementation.
 *
 * Creates lightweight records from feed items.
 *
 * @AggregatorProcessor(
 *   id = "aggregator",
 *   title = @Translation("Default processor"),
 *   description = @Translation("Creates lightweight records from feed items.")
 * )
 */
class DefaultProcessor extends AggregatorPluginSettingsBase implements ProcessorInterface, ContainerFactoryPluginInterface {
  use ConfigFormBaseTrait;
  use UrlGeneratorTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity storage for items.
   *
   * @var \Drupal\aggregator\ItemStorageInterface
   */
  protected $itemStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DefaultProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\aggregator\ItemStorageInterface $item_storage
   *   The entity storage for feed items.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, ItemStorageInterface $item_storage, DateFormatterInterface $date_formatter) {
    $this->configFactory = $config;
    $this->itemStorage = $item_storage;
    $this->dateFormatter = $date_formatter;
    // @todo Refactor aggregator plugins to ConfigEntity so merging
    //   the configuration here is not needed.
    parent::__construct($configuration + $this->getConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('aggregator_item'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['aggregator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aggregator.settings');
    $processors = $config->get('processors');
    $info = $this->getPluginDefinition();
    $counts = [3, 5, 10, 15, 20, 25];
    $items = array_map(function ($count) {
      return $this->formatPlural($count, '1 item', '@count items');
    }, array_combine($counts, $counts));
    $intervals = [3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($intervals, $intervals));
    $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

    $form['processors'][$info['id']] = [];
    // Only wrap into details if there is a basic configuration.
    if (isset($form['basic_conf'])) {
      $form['processors'][$info['id']] = [
        '#type' => 'details',
        '#title' => t('Default processor settings'),
        '#description' => $info['description'],
        '#open' => in_array($info['id'], $processors),
      ];
    }

    $form['processors'][$info['id']]['aggregator_summary_items'] = [
      '#type' => 'select',
      '#title' => t('Number of items shown in listing pages'),
      '#default_value' => $config->get('source.list_max'),
      '#empty_value' => 0,
      '#options' => $items,
    ];

    $form['processors'][$info['id']]['aggregator_clear'] = [
      '#type' => 'select',
      '#title' => t('Discard items older than'),
      '#default_value' => $config->get('items.expire'),
      '#options' => $period,
      '#description' => t('Requires a correctly configured <a href=":cron">cron maintenance task</a>.', [':cron' => $this->url('system.status')]),
    ];

    $lengths = [0, 200, 400, 600, 800, 1000, 1200, 1400, 1600, 1800, 2000];
    $options = array_map(function($length) {
      return ($length == 0) ? t('Unlimited') : $this->formatPlural($length, '1 character', '@count characters');
    }, array_combine($lengths, $lengths));

    $form['processors'][$info['id']]['aggregator_teaser_length'] = [
      '#type' => 'select',
      '#title' => t('Length of trimmed description'),
      '#default_value' => $config->get('items.teaser_length'),
      '#options' => $options,
      '#description' => t('The maximum number of characters used in the trimmed version of content.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['items']['expire'] = $form_state->getValue('aggregator_clear');
    $this->configuration['items']['teaser_length'] = $form_state->getValue('aggregator_teaser_length');
    $this->configuration['source']['list_max'] = $form_state->getValue('aggregator_summary_items');
    // @todo Refactor aggregator plugins to ConfigEntity so this is not needed.
    $this->setConfiguration($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed) {
    if (!is_array($feed->items)) {
      return;
    }
    foreach ($feed->items as $item) {
      // @todo: The default entity view builder always returns an empty
      //   array, which is ignored in aggregator_save_item() currently. Should
      //   probably be fixed.
      if (empty($item['title'])) {
        continue;
      }

      // Save this item. Try to avoid duplicate entries as much as possible. If
      // we find a duplicate entry, we resolve it and pass along its ID is such
      // that we can update it if needed.
      if (!empty($item['guid'])) {
        $values = ['fid' => $feed->id(), 'guid' => $item['guid']];
      }
      elseif ($item['link'] && $item['link'] != $feed->link && $item['link'] != $feed->url) {
        $values = ['fid' => $feed->id(), 'link' => $item['link']];
      }
      else {
        $values = ['fid' => $feed->id(), 'title' => $item['title']];
      }

      // Try to load an existing entry.
      if ($entry = entity_load_multiple_by_properties('aggregator_item', $values)) {
        $entry = reset($entry);
      }
      else {
        $entry = Item::create(['langcode' => $feed->language()->getId()]);
      }
      if ($item['timestamp']) {
        $entry->setPostedTime($item['timestamp']);
      }

      // Make sure the item title and author fit in the 255 varchar column.
      $entry->setTitle(Unicode::truncate($item['title'], 255, TRUE, TRUE));
      $entry->setAuthor(Unicode::truncate($item['author'], 255, TRUE, TRUE));

      $entry->setFeedId($feed->id());
      $entry->setLink($item['link']);
      $entry->setGuid($item['guid']);

      $description = '';
      if (!empty($item['description'])) {
        $description = $item['description'];
      }
      $entry->setDescription($description);

      $entry->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FeedInterface $feed) {
    if ($items = $this->itemStorage->loadByFeed($feed->id())) {
      $this->itemStorage->delete($items);
    }
    // @todo This should be moved out to caller with a different message maybe.
    drupal_set_message(t('The news items from %site have been deleted.', ['%site' => $feed->label()]));
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::postProcess().
   *
   * Expires items from a feed depending on expiration settings.
   */
  public function postProcess(FeedInterface $feed) {
    $aggregator_clear = $this->configuration['items']['expire'];

    if ($aggregator_clear != AGGREGATOR_CLEAR_NEVER) {
      // Delete all items that are older than flush item timer.
      $age = REQUEST_TIME - $aggregator_clear;
      $result = $this->itemStorage->getQuery()
        ->condition('fid', $feed->id())
        ->condition('timestamp', $age, '<')
        ->execute();
      if ($result) {
        $entities = $this->itemStorage->loadMultiple($result);
        $this->itemStorage->delete($entities);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->get('aggregator.settings')->get();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->config('aggregator.settings');
    foreach ($configuration as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

}
