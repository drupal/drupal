<?php

/**
 * @file
 * Contains \Drupal\system\CronController.
 */

namespace Drupal\system;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\CronInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Cron handling.
 */
class CronController extends ControllerBase {

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * Constructs a CronController object.
   *
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   */
  public function __construct(CronInterface $cron) {
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cron'));
  }


  /**
   * Run Cron once.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   */
  public function run() {
    $this->cron->run();

    // HTTP 204 is "No content", meaning "I did what you asked and we're done."
    return new Response('', 204);
  }

  /**
   * Run cron manually.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function runManually() {
    if ($this->cron->run()) {
      drupal_set_message($this->t('Cron ran successfully.'));
    }
    else {
      drupal_set_message($this->t('Cron run failed.'), 'error');
    }

    return $this->redirect('system.status');
  }

}
