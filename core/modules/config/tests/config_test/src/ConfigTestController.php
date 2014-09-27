<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestController.
 */

namespace Drupal\config_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\config_test\Entity\ConfigTest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller class for the config_test module.
 */
class ConfigTestController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\config_test\Entity\ConfigTest $config_test
   *   The ConfigTest object.
   *
   * @return string
   *   The title for the ConfigTest edit form.
   */
  public function editTitle(ConfigTest $config_test) {
    return $this->t('Edit %label', array('%label' => $config_test->label()));
  }

  /**
   * Enables a ConfigTest object.
   *
   * @param \Drupal\config_test\ConfigTest $config_test
   *   The ConfigTest object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the config_test listing page.
   */
  function enable(ConfigTest $config_test) {
    $config_test->enable()->save();
    return new RedirectResponse($this->url('config_test.list_page', array(), array('absolute' => TRUE)));
  }

  /**
   * Disables a ConfigTest object.
   *
   * @param \Drupal\config_test\ConfigTest $config_test
   *   The ConfigTest object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the config_test listing page.
   */
  function disable(ConfigTest $config_test) {
    $config_test->disable()->save();
    return new RedirectResponse(\Drupal::url('config_test.list_page', array(), array('absolute' => TRUE)));
  }

}
