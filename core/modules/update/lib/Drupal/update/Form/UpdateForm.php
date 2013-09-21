<?php
/**
 * @file
 * Contains \Drupal\update\Form\UpdateForm.
 */

namespace Drupal\update\Form;

/**
 * Temporary form controller for update module.
 */
class UpdateForm {

  /**
   * Wraps update_manager_install_form().
   *
   * @todo Remove update_manager_install_form().
   */
  public function reportInstall() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_install_form', 'report');
  }

  /**
   * Wraps update_manager_update_form().
   *
   * @todo Remove update_manager_update_form().
   */
  public function reportUpdate() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_update_form', 'report');
  }

  /**
   * Wraps update_manager_install_form().
   *
   * @todo Remove update_manager_install_form().
   */
  public function moduleInstall() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_install_form', 'module');
  }

  /**
   * Wraps update_manager_update_form().
   *
   * @todo Remove update_manager_update_form().
   */
  public function moduleUpdate() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_update_form', 'module');
  }

  /**
   * Wraps update_manager_install_form().
   *
   * @todo Remove update_manager_install_form().
   */
  public function themeInstall() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_install_form', 'theme');
  }

  /**
   * Wraps update_manager_update_form().
   *
   * @todo Remove update_manager_update_form().
   */
  public function themeUpdate() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_update_form', 'theme');
  }

  /**
   * Wraps update_manager_update_ready_form().
   *
   * @todo Remove update_manager_update_ready_form().
   */
  public function confirmUpdates() {
    module_load_include('manager.inc', 'update');
    return drupal_get_form('update_manager_update_ready_form');
  }

}
