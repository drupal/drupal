<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\UninstallTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for module uninstallation and related hooks.
 */
class UninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('module_test', 'user', 'views', 'node');

  public static function getInfo() {
    return array(
      'name' => 'Module uninstallation',
      'description' => 'Tests the uninstallation of modules.',
      'group' => 'Module',
    );
  }

  /**
   * Tests the hook_modules_uninstalled() of the user module.
   */
  function testUserPermsUninstalled() {
    // Uninstalls the module_test module, so hook_modules_uninstalled()
    // is executed.
    module_uninstall(array('module_test'));

    // Are the perms defined by module_test removed?
    $this->assertFalse(user_roles(FALSE, 'module_test perm'), 'Permissions were all removed.');
  }

  /**
   * Tests the Uninstall page and Uninstall confirmation page.
   */
  function testUninstallPage() {
    $account = $this->drupalCreateUser(array('administer modules'));
    $this->drupalLogin($account);
    $this->drupalGet('admin/modules/uninstall');
    $this->assertTitle(t('Uninstall') . ' | Drupal');

    // Uninstall module_test.
    $edit = array();
    $edit['uninstall[module_test]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Uninstall node testing that the configuration that will be deleted is
    // listed.
    $node_dependencies = \Drupal::service('config.manager')->findConfigEntityDependentsAsEntities('module', array('node'));
    $edit = array();
    $edit['uninstall[node]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertText(\Drupal::translation()->translate('Configuration deletions'), 'Configuration deletions listed on the module install confirmation page.');

    $entity_types = array();
    foreach ($node_dependencies as $entity) {
      $label = $entity->label();
      $this->assertText($label, String::format('The entity label "!label" found.', array('!label' => $label)));
      $entity_types[] = $entity->getEntityTypeId();
    }
    $entity_types = array_unique($entity_types);
    foreach ($entity_types as $entity_type_id) {
      $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
      // Add h3's since the entity type label is often repeated in the entity
      // labels.
      $this->assertRaw('<h3>' . $entity_type->getLabel() . '</h3>');
    }
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
  }
}
