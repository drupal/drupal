<?php
/**
 * @file
 * Contains \Drupal\system\SystemManager.
 */

namespace Drupal\system;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * System Manager Service.
 */
class SystemManager {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Requirement severity -- Requirement successfully met.
   */
  const REQUIREMENT_OK = 0;

  /**
   * Requirement severity -- Warning condition; proceed but flag warning.
   */
  const REQUIREMENT_WARNING = 1;

  /**
   * Requirement severity -- Error condition; abort installation.
   */
  const REQUIREMENT_ERROR = 2;

  /**
   * Constructs a SystemManager object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Connection $database) {
    $this->moduleHandler = $module_handler;
    $this->database = $database;
  }

  /**
   * Checks for requirement severity.
   *
   * @return boolean
   *   Returns the status of the system.
   */
  public function checkRequirements() {
    $requirements = $this->listRequirements();
    return $this->getMaxSeverity($requirements) == static::REQUIREMENT_ERROR;
  }

  /**
   * Displays the site status report. Can also be used as a pure check.
   *
   * @return array
   *   An array of system requirements.
   */
  public function listRequirements() {
    // Load .install files
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    // Check run-time requirements and status information.
    $requirements = $this->moduleHandler->invokeAll('requirements', array('runtime'));
    usort($requirements, function($a, $b) {
      if (!isset($a['weight'])) {
        if (!isset($b['weight'])) {
          return strcmp($a['title'], $b['title']);
        }
        return -$b['weight'];
      }
      return isset($b['weight']) ? $a['weight'] - $b['weight'] : $a['weight'];
    });

    return $requirements;
  }

  /**
   * Fixes anonymous user on MySQL.
   *
   * MySQL import might have set the uid of the anonymous user to autoincrement
   * value. Let's try fixing it. See http://drupal.org/node/204411
   */
  public function fixAnonymousUid() {
    $this->database->update('users')
      ->expression('uid', 'uid - uid')
      ->condition('name', '')
      ->condition('pass', '')
      ->condition('status', 0)
      ->execute();
  }

  /**
   * Extracts the highest severity from the requirements array.
   *
   * @param $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @return
   *   The highest severity in the array.
   */
  public function getMaxSeverity(&$requirements) {
    $severity = static::REQUIREMENT_OK;
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $severity = max($severity, $requirement['severity']);
      }
    }
    return $severity;
  }

}
