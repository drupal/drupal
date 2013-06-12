<?php

/**
 * @file
 * Contains \Drupal\aggregator_test\Plugin\aggregator\processor\TestProcessor.
 */

namespace Drupal\aggregator_test\Plugin\aggregator\processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\aggregator\Plugin\ProcessorInterface;
use Drupal\aggregator\Plugin\Core\Entity\Feed;
use Drupal\aggregator\Annotation\AggregatorProcessor;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a default processor implementation.
 *
 * Creates lightweight records from feed items.
 *
 * @AggregatorProcessor(
 *   id = "aggregator_test_processor",
 *   title = @Translation("Test processor"),
 *   description = @Translation("Test generic processor functionality.")
 * )
 */
class TestProcessor extends PluginBase implements ProcessorInterface {

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $config = config('aggregator.settings');
    $processors = $config->get('processors');
    $info = $this->getPluginDefinition();

    $form['processors'][$info['id']] = array(
      '#type' => 'details',
      '#title' => t('Test processor settings'),
      '#description' => $info['description'],
      '#collapsed' => !in_array($info['id'], $processors),
    );
    // Add some dummy settings to verify settingsForm is called.
    $form['processors'][$info['id']]['dummy_length'] = array(
      '#title' => t('Dummy length setting'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => config('aggregator_test.settings')->get('items.dummy_length'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::settingsSubmit().
   */
  public function settingsSubmit(array $form, array &$form_state) {
    config('aggregator_test.settings')
      ->set('items.dummy_length', $form_state['values']['dummy_length'])
      ->save();
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::process().
   */
  public function process(Feed $feed) {
    foreach ($feed->items as &$item) {
      // Prepend our test string.
      $item['title'] = 'testProcessor' . $item['title'];
    }
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::remove().
   */
  public function remove(Feed $feed) {
    // Append a random number, just to change the feed description.
    $feed->description->value .= rand(0, 10);
  }

  /**
   * Implements \Drupal\aggregator\Plugin\ProcessorInterface::postProcess().
   */
  public function postProcess(Feed $feed) {
    // Double the refresh rate.
    $feed->refresh->value *= 2;
    $feed->save();
  }
}
