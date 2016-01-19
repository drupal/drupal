<?php

/**
 * @file
 * Contains \Drupal\system\Form\CronForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure cron settings for this site.
 */
class CronForm extends FormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a CronForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, CronInterface $cron, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler) {
    $this->state = $state;
    $this->cron = $cron;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('cron'),
      $container->get('date.formatter'),
      $container->get('module_handler')
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = array(
      '#markup' => '<p>' . t('Cron takes care of running periodic tasks like checking for updates and indexing content for search.') . '</p>',
    );
    $form['run'] = array(
      '#type' => 'submit',
      '#value' => t('Run cron'),
    );
    $status = '<p>' . $this->t('Last run: %time ago.', array('%time' => $this->dateFormatter->formatTimeDiffSince($this->state->get('system.cron_last')))) . '</p>';
    $form['status'] = array(
      '#markup' => $status,
    );

    $cron_url = $this->url('system.cron', array('key' => $this->state->get('system.cron_key')), array('absolute' => TRUE));
    $form['cron_url'] = array(
      '#markup' => '<p>' . t('To run cron from outside the site, go to <a href=":cron">@cron</a>', array(':cron' => $cron_url, '@cron' => $cron_url)) . '</p>',
    );

    if (!$this->moduleHandler->moduleExists('automated_cron')) {
      $form['cron'] = array(
        '#markup' => $this->t('Enable the <em>Automated Cron</em> module to allow cron execution at the end of a server response.'),
      );
    }

    return $form;
  }

  /**
   * Runs cron and reloads the page.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Run cron manually from Cron form.
    if ($this->cron->run()) {
      drupal_set_message(t('Cron run successfully.'));
    }
    else {
      drupal_set_message(t('Cron run failed.'), 'error');
    }

  }

}
