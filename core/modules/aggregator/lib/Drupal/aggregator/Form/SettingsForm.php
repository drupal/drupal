<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\SettingsForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\system\SystemConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\aggregator\Plugin\AggregatorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures aggregator settings for this site.
 */
class SettingsForm extends SystemConfigFormBase {

  /**
   * The aggregator plugin managers.
   *
   * @var array
   */
  protected $managers = array();

  /**
   * The aggregator plugin definitions.
   *
   * @var array
   */
  protected $definitions = array(
    'fetcher' => array(),
    'parser' => array(),
    'processor' => array(),
  );

  /**
   * Constructs a \Drupal\aggregator\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $fetcher_manager
   *   The aggregator fetcher plugin manager.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $parser_manager
   *   The aggregator parser plugin manager.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $processor_manager
   *   The aggregator processor plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, AggregatorPluginManager $fetcher_manager, AggregatorPluginManager $parser_manager, AggregatorPluginManager $processor_manager) {
    $this->configFactory = $config_factory;
    $this->managers = array(
      'fetcher' => $fetcher_manager,
      'parser' => $parser_manager,
      'processor' => $processor_manager,
    );
    // Get all available fetcher, parser and processor definitions.
    foreach (array('fetcher', 'parser', 'processor') as $type) {
      foreach ($this->managers[$type]->getDefinitions() as $id => $definition) {
        $this->definitions[$type][$id] = format_string('@title <span class="description">@description</span>', array('@title' => $definition['title'], '@description' => $definition['description']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.aggregator.fetcher'),
      $container->get('plugin.manager.aggregator.parser'),
      $container->get('plugin.manager.aggregator.processor')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'aggregator_admin_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('aggregator.settings');

    // Global aggregator settings.
    $form['aggregator_allowed_html_tags'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed HTML tags'),
      '#size' => 80,
      '#maxlength' => 255,
      '#default_value' => $config->get('items.allowed_html'),
      '#description' => t('A space-separated list of HTML tags allowed in the content of feed items. Disallowed tags are stripped from the content.'),
    );

    // Only show basic configuration if there are actually options.
    $basic_conf = array();
    if (count($this->definitions['fetcher']) > 1) {
      $basic_conf['aggregator_fetcher'] = array(
        '#type' => 'radios',
        '#title' => t('Fetcher'),
        '#description' => t('Fetchers download data from an external source. Choose a fetcher suitable for the external source you would like to download from.'),
        '#options' => $this->definitions['fetcher'],
        '#default_value' => $config->get('fetcher'),
      );
    }
    if (count($this->definitions['parser']) > 1) {
      $basic_conf['aggregator_parser'] = array(
        '#type' => 'radios',
        '#title' => t('Parser'),
        '#description' => t('Parsers transform downloaded data into standard structures. Choose a parser suitable for the type of feeds you would like to aggregate.'),
        '#options' => $this->definitions['parser'],
        '#default_value' => $config->get('parser'),
      );
    }
    if (count($this->definitions['processor']) > 1) {
      $basic_conf['aggregator_processors'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Processors'),
        '#description' => t('Processors act on parsed feed data, for example they store feed items. Choose the processors suitable for your task.'),
        '#options' => $this->definitions['processor'],
        '#default_value' => $config->get('processors'),
      );
    }
    if (count($basic_conf)) {
      $form['basic_conf'] = array(
        '#type' => 'details',
        '#title' => t('Basic configuration'),
        '#description' => t('For most aggregation tasks, the default settings are fine.'),
        '#collapsed' => FALSE,
      );
      $form['basic_conf'] += $basic_conf;
    }

    // Implementing processor plugins will expect an array at $form['processors'].
    $form['processors'] = array();
    // Call settingsForm() for each active processor.
    foreach ($this->definitions['processor'] as $id => $definition) {
      if (in_array($id, $config->get('processors'))) {
        $form = $this->managers['processor']->createInstance($id)->settingsForm($form, $form_state);
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->get('aggregator.settings');
    // Let active processors save their settings.
    foreach ($this->definitions['processor'] as $id => $definition) {
      if (in_array($id, $config->get('processors'))) {
        $this->managers['processor']->createInstance($id)->settingsSubmit($form, $form_state);
      }
    }

    $config->set('items.allowed_html', $form_state['values']['aggregator_allowed_html_tags']);
    if (isset($form_state['values']['aggregator_fetcher'])) {
      $config->set('fetcher', $form_state['values']['aggregator_fetcher']);
    }
    if (isset($form_state['values']['aggregator_parser'])) {
      $config->set('parser', $form_state['values']['aggregator_parser']);
    }
    if (isset($form_state['values']['aggregator_processors'])) {
      $config->set('processors', array_filter($form_state['values']['aggregator_processors']));
    }
    $config->save();
  }

}
