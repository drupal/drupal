<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\processor\DefaultProcessor.
 */

namespace Drupal\aggregator\Plugin\aggregator\processor;

use Drupal\aggregator\Annotation\AggregatorProcessor;
use Drupal\aggregator\Plugin\AggregatorPluginSettingsBase;
use Drupal\aggregator\Plugin\ProcessorInterface;
use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Database;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a DefaultProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration factory object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $config) {
    $this->configFactory = $config;
    // @todo Refactor aggregator plugins to ConfigEntity so merging
    //   the configuration here is not needed.
    parent::__construct($configuration + $this->getConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $processors = $this->configuration['processors'];
    $info = $this->getPluginDefinition();
    $items = drupal_map_assoc(array(3, 5, 10, 15, 20, 25), array($this, 'formatItems'));
    $period = drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval');
    $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

    $form['processors'][$info['id']] = array();
    // Only wrap into details if there is a basic configuration.
    if (isset($form['basic_conf'])) {
      $form['processors'][$info['id']] = array(
        '#type' => 'details',
        '#title' => t('Default processor settings'),
        '#description' => $info['description'],
        '#collapsed' => !in_array($info['id'], $processors),
      );
    }

    $form['processors'][$info['id']]['aggregator_summary_items'] = array(
      '#type' => 'select',
      '#title' => t('Number of items shown in listing pages'),
      '#default_value' => $this->configuration['source']['list_max'],
      '#empty_value' => 0,
      '#options' => $items,
    );

    $form['processors'][$info['id']]['aggregator_clear'] = array(
      '#type' => 'select',
      '#title' => t('Discard items older than'),
      '#default_value' => $this->configuration['items']['expire'],
      '#options' => $period,
      '#description' => t('Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );

    $form['processors'][$info['id']]['aggregator_category_selector'] = array(
      '#type' => 'radios',
      '#title' => t('Select categories using'),
      '#default_value' => $this->configuration['source']['category_selector'],
      '#options' => array('checkboxes' => t('checkboxes'),
      'select' => t('multiple selector')),
      '#description' => t('For a small number of categories, checkboxes are easier to use, while a multiple selector works well with large numbers of categories.'),
    );
    $form['processors'][$info['id']]['aggregator_teaser_length'] = array(
      '#type' => 'select',
      '#title' => t('Length of trimmed description'),
      '#default_value' => $this->configuration['items']['teaser_length'],
      '#options' => drupal_map_assoc(array(0, 200, 400, 600, 800, 1000, 1200, 1400, 1600, 1800, 2000), array($this, 'formatCharacters')),
      '#description' => t('The maximum number of characters used in the trimmed version of content.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['items']['expire'] = $form_state['values']['aggregator_clear'];
    $this->configuration['items']['teaser_length'] = $form_state['values']['aggregator_teaser_length'];
    $this->configuration['source']['list_max'] = $form_state['values']['aggregator_summary_items'];
    $this->configuration['source']['category_selector'] = $form_state['values']['aggregator_category_selector'];
    // @todo Refactor aggregator plugins to ConfigEntity so this is not needed.
    $this->setConfiguration($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process(Feed $feed) {
    if (!is_array($feed->items)) {
      return;
    }
    foreach ($feed->items as $item) {
      // @todo: The default entity render controller always returns an empty
      //   array, which is ignored in aggregator_save_item() currently. Should
      //   probably be fixed.
      if (empty($item['title'])) {
        continue;
      }

      // Save this item. Try to avoid duplicate entries as much as possible. If
      // we find a duplicate entry, we resolve it and pass along its ID is such
      // that we can update it if needed.
      if (!empty($item['guid'])) {
        $values = array('fid' => $feed->id(), 'guid' => $item['guid']);
      }
      elseif ($item['link'] && $item['link'] != $feed->link && $item['link'] != $feed->url) {
        $values = array('fid' => $feed->id(), 'link' => $item['link']);
      }
      else {
        $values = array('fid' => $feed->id(), 'title' => $item['title']);
      }

      // Try to load an existing entry.
      if ($entry = entity_load_multiple_by_properties('aggregator_item', $values)) {
        $entry = reset($entry);
      }
      else {
        $entry = entity_create('aggregator_item', array('langcode' => $feed->language()->id));
      }
      if ($item['timestamp']) {
        $entry->timestamp->value = $item['timestamp'];
      }

      // Make sure the item title and author fit in the 255 varchar column.
      $entry->title->value = truncate_utf8($item['title'], 255, TRUE, TRUE);
      $entry->author->value = truncate_utf8($item['author'], 255, TRUE, TRUE);

      $entry->fid->value = $feed->id();
      $entry->link->value = $item['link'];
      $entry->description->value = $item['description'];
      $entry->guid->value = $item['guid'];
      $entry->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove(Feed $feed) {
    $iids = Database::getConnection()->query('SELECT iid FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchCol();
    if ($iids) {
      entity_delete_multiple('aggregator_item', $iids);
    }
    // @todo This should be moved out to caller with a different message maybe.
    drupal_set_message(t('The news items from %site have been removed.', array('%site' => $feed->label())));
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::postProcess().
   *
   * Expires items from a feed depending on expiration settings.
   */
  public function postProcess(Feed $feed) {
    $aggregator_clear = $this->configuration['items']['expire'];

    if ($aggregator_clear != AGGREGATOR_CLEAR_NEVER) {
      // Remove all items that are older than flush item timer.
      $age = REQUEST_TIME - $aggregator_clear;
      $iids = Database::getConnection()->query('SELECT iid FROM {aggregator_item} WHERE fid = :fid AND timestamp < :timestamp', array(
        ':fid' => $feed->id(),
        ':timestamp' => $age,
      ))
      ->fetchCol();
      if ($iids) {
        entity_delete_multiple('aggregator_item', $iids);
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
    $config = $this->configFactory->get('aggregator.settings');
    foreach ($configuration as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Helper function for drupal_map_assoc.
   *
   * @param int $count
   *   Items count.
   *
   * @return string
   *   A string that is plural-formatted as "@count items".
   */
  protected function formatItems($count) {
    return format_plural($count, '1 item', '@count items');
  }

  /**
   * Creates display text for teaser length option values.
   *
   * Callback for drupal_map_assoc() within settingsForm().
   *
   * @param int $length
   *   The desired length of teaser text, in bytes.
   *
   * @return string
   *   A translated string explaining the teaser string length.
   */
  protected function formatCharacters($length) {
    return ($length == 0) ? t('Unlimited') : format_plural($length, '1 character', '@count characters');
  }

}
