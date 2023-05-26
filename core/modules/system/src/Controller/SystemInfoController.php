<?php

namespace Drupal\system\Controller;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\SystemManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Returns responses for System Info routes.
 */
class SystemInfoController implements ContainerInjectionInterface {

  use StringTranslationTrait;

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
    return ['#type' => 'status_report_page', '#requirements' => $requirements];
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
      $phpinfo_flags = Settings::get('sa_core_2023_004_phpinfo_flags', ~ (INFO_VARIABLES | INFO_ENVIRONMENT));
      phpinfo($phpinfo_flags);
      $output = ob_get_clean();
    }
    else {
      $output = $this->t('The phpinfo() function is disabled. For more information, visit the <a href=":phpinfo">Enabling and disabling phpinfo()</a> handbook page.', [':phpinfo' => 'https://www.drupal.org/node/243993']);
    }
    return new Response($output);
  }

}
