<?php

/**
 * @file
 * Contains \Drupal\system\Form\CronForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure cron settings for this site.
 */
class CronForm extends ConfigFormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * Constructs a CronForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, CronInterface $cron) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('cron')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_cron_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('system.cron');

    $form['description'] = array(
      '#markup' => '<p>' . t('Cron takes care of running periodic tasks like checking for updates and indexing content for search.') . '</p>',
    );
    $form['run'] = array(
      '#type' => 'submit',
      '#value' => t('Run cron'),
      '#submit' => array(array($this, 'submitCron')),
    );

    $status = '<p>' . t('Last run: %cron-last ago.', array('%cron-last' => format_interval(REQUEST_TIME - $this->state->get('system.cron_last')))) . '</p>';
    $form['status'] = array(
      '#markup' => $status,
    );

    $form['cron_url'] = array(
      '#markup' => '<p>' . t('To run cron from outside the site, go to <a href="!cron">!cron</a>', array('!cron' => url('cron/' . $this->state->get('system.cron_key'), array('absolute' => TRUE)))) . '</p>',
    );

    $form['cron'] = array(
      '#title' => t('Cron settings'),
      '#type' => 'details',
      '#open' => TRUE,
    );
    $options = array(3600, 10800, 21600, 43200, 86400, 604800);
    $form['cron']['cron_safe_threshold'] = array(
      '#type' => 'select',
      '#title' => t('Run cron every'),
      '#description' => t('More information about setting up scheduled tasks can be found by <a href="@url">reading the cron tutorial on drupal.org</a>.', array('@url' => url('http://drupal.org/cron'))),
      '#default_value' => $config->get('threshold.autorun'),
      '#options' => array(0 => t('Never')) + array_map('format_interval', array_combine($options, $options)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('system.cron')
      ->set('threshold.autorun', $form_state['values']['cron_safe_threshold'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Runs cron and reloads the page.
   */
  public function submitCron(array &$form, array &$form_state) {
    // Run cron manually from Cron form.
    if ($this->cron->run()) {
      drupal_set_message(t('Cron run successfully.'));
    }
    else {
      drupal_set_message(t('Cron run failed.'), 'error');
    }

    return new RedirectResponse(url('admin/config/system/cron', array('absolute' => TRUE)));
  }

}
