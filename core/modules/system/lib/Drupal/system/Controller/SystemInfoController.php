<?php

/**
 * @file
 * Contains \Drupal\system\Controller\SystemInfoController.
 */

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Database\Connection;
use Drupal\system\SystemManager;

/**
 * Returns responses for System Info routes.
 */
class SystemInfoController implements ContainerInjectionInterface {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager')
    );
  }

  /**
   * Constructs a SystemInfoController object.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   */
  public function __construct(SystemManager $systemManager) {
    $this->systemManager = $systemManager;
  }

  /**
   * Displays the site status report.
   *
   * @return string
   *   The current status of the Drupal installation.
   */
  public function status() {
    $requirements = $this->systemManager->listRequirements();
    $this->systemManager->fixAnonymousUid();
    return array('#theme' => 'status_report', '#requirements' => $requirements);
  }

  /**
   * Returns the contents of phpinfo().
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object to be sent to the client.
   */
  public function php() {
    ob_start();
    phpinfo();
    $output = ob_get_clean();
    return new Response($output);
  }

}
