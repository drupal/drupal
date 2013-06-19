<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestController.
 */

namespace Drupal\config_test;

use Drupal\config_test\Plugin\Core\Entity\ConfigTest;
use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller class for the config_test module.
 */
class ConfigTestController {

  /**
   * Presents the ConfigTest edit form.
   *
   * @param \Drupal\config_test\Plugin\Core\Entity\ConfigTest $config_test
   *   The ConfigTest object to edit.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function edit(ConfigTest $config_test) {
    drupal_set_title(String::format('Edit %label', array('%label' => $config_test->label())), PASS_THROUGH);
    return \Drupal::entityManager()->getForm($config_test);
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
    return new RedirectResponse(url('admin/structure/config_test', array('absolute' => TRUE)));
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
    return new RedirectResponse(url('admin/structure/config_test', array('absolute' => TRUE)));
  }

}
