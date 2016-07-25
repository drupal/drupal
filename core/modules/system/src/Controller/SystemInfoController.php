<?php

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * @return array
   *   A render array containing a list of system requirements for the Drupal
   *   installation and whether this installation meets the requirements.
   */
  public function status() {
    $requirements = $this->systemManager->listRequirements();
    return array('#theme' => 'status_report', '#requirements' => $requirements);
  }

  /**
   * Returns the contents of phpinfo().
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object to be sent to the client.
   */
  public function php() {
    if (function_exists('phpinfo')) {
      ob_start();
      phpinfo();
      $output = ob_get_clean();
    }
    else {
      $output = t('The phpinfo() function has been disabled for security reasons. For more information, visit <a href=":phpinfo">Enabling and disabling phpinfo()</a> handbook page.', array(':phpinfo' => 'https://www.drupal.org/node/243993'));
    }
    return new Response($output);
  }

}
