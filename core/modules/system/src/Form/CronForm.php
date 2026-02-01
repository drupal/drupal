<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure cron settings for this site.
 *
 * @internal
 */
class CronForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected StateInterface $state,
    protected CronInterface $cron,
    protected DateFormatterInterface $dateFormatter,
    protected ModuleHandlerInterface $moduleHandler,
    TypedConfigManagerInterface $typedConfigManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.cron'];
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
      $container->get('module_handler'),
      $container->get('config.typed')
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
    $form['description'] = [
      '#markup' => '<p>' . $this->t('Cron takes care of running periodic tasks like checking for updates and indexing content for search.') . '</p>',
    ];
    $form['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run cron'),
      '#submit' => ['::runCron'],
    ];
    if ($time_ago = $this->state->get('system.cron_last')) {
      $status = '<p>' . $this->t('Last run: %time ago.', ['%time' => $this->dateFormatter->formatTimeDiffSince($time_ago)]) . '</p>';
    }
    else {
      $status = '<p>' . $this->t('Last run: never') . '</p>';
    }
    $form['status'] = [
      '#markup' => $status,
    ];

    $cron_url = Url::fromRoute('system.cron', ['key' => $this->state->get('system.cron_key')], ['absolute' => TRUE])->toString();
    $form['cron_url'] = [
      '#markup' => '<p>' . $this->t('To run cron from outside the site, go to <a href=":cron" class="system-cron-settings__link">@cron</a>', [
        ':cron' => $cron_url,
        '@cron' => $cron_url,
      ]) . '</p>',
    ];

    if (!$this->moduleHandler->moduleExists('automated_cron')) {
      $form['automated_cron'] = [
        '#markup' => $this->t('Install the <em>Automated Cron</em> module to allow cron execution at the end of a server response.'),
      ];
    }

    $form['cron'] = [
      '#title' => $this->t('Cron settings'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['cron']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Detailed cron logging'),
      '#config_target' => 'system.cron:logging',
      '#description' => $this->t('Run times of individual cron jobs will be written to watchdog'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler for running cron manually.
   */
  public function runCron(array &$form, FormStateInterface $form_state) {
    if ($this->cron->run()) {
      $this->messenger()->addStatus($this->t('Cron ran successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Cron run failed.'));
    }
  }

}
