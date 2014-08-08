<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\SettingsForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\Plugin\AggregatorPluginManager;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures aggregator settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The aggregator plugin managers.
   *
   * @var \Drupal\aggregator\Plugin\AggregatorPluginManager[]
   */
  protected $managers = array();

  /**
   * The instantiated plugin instances that have configuration forms.
   *
   * @var \Drupal\Core\Plugin\PluginFormInterface[]
   */
  protected $configurableInstances = array();

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $fetcher_manager
   *   The aggregator fetcher plugin manager.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $parser_manager
   *   The aggregator parser plugin manager.
   * @param \Drupal\aggregator\Plugin\AggregatorPluginManager $processor_manager
   *   The aggregator processor plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AggregatorPluginManager $fetcher_manager, AggregatorPluginManager $parser_manager, AggregatorPluginManager $processor_manager, TranslationInterface $string_translation) {
    parent::__construct($config_factory);
    $this->stringTranslation = $string_translation;
    $this->managers = array(
      'fetcher' => $fetcher_manager,
      'parser' => $parser_manager,
      'processor' => $processor_manager,
    );
    // Get all available fetcher, parser and processor definitions.
    foreach (array('fetcher', 'parser', 'processor') as $type) {
      foreach ($this->managers[$type]->getDefinitions() as $id => $definition) {
        $this->definitions[$type][$id] = String::format('@title <span class="description">@description</span>', array('@title' => $definition['title'], '@description' => $definition['description']));
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
      $container->get('plugin.manager.aggregator.processor'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aggregator_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aggregator.settings');

    // Global aggregator settings.
    $form['aggregator_allowed_html_tags'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#size' => 80,
      '#maxlength' => 255,
      '#default_value' => $config->get('items.allowed_html'),
      '#description' => $this->t('A space-separated list of HTML tags allowed in the content of feed items. Disallowed tags are stripped from the content.'),
    );

    // Only show basic configuration if there are actually options.
    $basic_conf = array();
    if (count($this->definitions['fetcher']) > 1) {
      $basic_conf['aggregator_fetcher'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Fetcher'),
        '#description' => $this->t('Fetchers download data from an external source. Choose a fetcher suitable for the external source you would like to download from.'),
        '#options' => $this->definitions['fetcher'],
        '#default_value' => $config->get('fetcher'),
      );
    }
    if (count($this->definitions['parser']) > 1) {
      $basic_conf['aggregator_parser'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Parser'),
        '#description' => $this->t('Parsers transform downloaded data into standard structures. Choose a parser suitable for the type of feeds you would like to aggregate.'),
        '#options' => $this->definitions['parser'],
        '#default_value' => $config->get('parser'),
      );
    }
    if (count($this->definitions['processor']) > 1) {
      $basic_conf['aggregator_processors'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Processors'),
        '#description' => $this->t('Processors act on parsed feed data, for example they store feed items. Choose the processors suitable for your task.'),
        '#options' => $this->definitions['processor'],
        '#default_value' => $config->get('processors'),
      );
    }
    if (count($basic_conf)) {
      $form['basic_conf'] = array(
        '#type' => 'details',
        '#title' => $this->t('Basic configuration'),
        '#description' => $this->t('For most aggregation tasks, the default settings are fine.'),
        '#open' => TRUE,
      );
      $form['basic_conf'] += $basic_conf;
    }

    // Call buildConfigurationForm() on the active fetcher and parser.
    foreach (array('fetcher', 'parser') as $type) {
      $active = $config->get($type);
      if (array_key_exists($active, $this->definitions[$type])) {
        $instance = $this->managers[$type]->createInstance($active);
        if ($instance instanceof PluginFormInterface) {
          $form = $instance->buildConfigurationForm($form, $form_state);
          // Store the instance for validate and submit handlers.
          // Keying by ID would bring conflicts, because two instances of a
          // different type could have the same ID.
          $this->configurableInstances[] = $instance;
        }
      }
    }

    // Implementing processor plugins will expect an array at $form['processors'].
    $form['processors'] = array();
    // Call buildConfigurationForm() for each active processor.
    foreach ($this->definitions['processor'] as $id => $definition) {
      if (in_array($id, $config->get('processors'))) {
        $instance = $this->managers['processor']->createInstance($id);
        if ($instance instanceof PluginFormInterface) {
          $form = $instance->buildConfigurationForm($form, $form_state);
          // Store the instance for validate and submit handlers.
          // Keying by ID would bring conflicts, because two instances of a
          // different type could have the same ID.
          $this->configurableInstances[] = $instance;
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Let active plugins validate their settings.
    foreach ($this->configurableInstances as $instance) {
      $instance->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('aggregator.settings');
    // Let active plugins save their settings.
    foreach ($this->configurableInstances as $instance) {
      $instance->submitConfigurationForm($form, $form_state);
    }

    $config->set('items.allowed_html', $form_state->getValue('aggregator_allowed_html_tags'));
    if ($form_state->hasValue('aggregator_fetcher')) {
      $config->set('fetcher', $form_state->getValue('aggregator_fetcher'));
    }
    if ($form_state->hasValue('aggregator_parser')) {
      $config->set('parser', $form_state->getValue('aggregator_parser'));
    }
    if ($form_state->hasValue('aggregator_processors')) {
      $config->set('processors', array_filter($form_state->getValue('aggregator_processors')));
    }
    $config->save();
  }

}
