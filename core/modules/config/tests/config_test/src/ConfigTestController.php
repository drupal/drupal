<?php

namespace Drupal\config_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\config_test\Entity\ConfigTest;
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
    return $this->t('Edit %label', ['%label' => $config_test->label()]);
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
  public function enable(ConfigTest $config_test) {
    $config_test->enable()->save();
    return new RedirectResponse($config_test->url('collection', ['absolute' => TRUE]));
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
  public function disable(ConfigTest $config_test) {
    $config_test->disable()->save();
    return new RedirectResponse($config_test->url('collection', ['absolute' => TRUE]));
  }

}
