<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the uninstallation of modules.
 *
 * @group Module
 */
class UninstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['module_test', 'user', 'views', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the hook_modules_uninstalled() of the user module.
   */
  public function testUserPermsUninstalled() {
    // Uninstalls the module_test module, so hook_modules_uninstalled()
    // is executed.
    $this->container->get('module_installer')->uninstall(['module_test']);

    // Are the perms defined by module_test removed?
    $this->assertEmpty(user_roles(FALSE, 'module_test perm'), 'Permissions were all removed.');
  }

  /**
   * Tests the Uninstall page and Uninstall confirmation page.
   */
  public function testUninstallPage() {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    // Create a node type.
    $node_type = NodeType::create(['type' => 'uninstall_blocker', 'name' => 'Uninstall blocker']);
    // Create a dependency that can be fixed.
    $node_type->setThirdPartySetting('module_test', 'key', 'value');
    $node_type->save();
    // Add a node to prevent node from being uninstalled.
    $node = Node::create([
      'type' => 'uninstall_blocker',
      'title' => $this->randomString(),
    ]);
    $node->save();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertTitle(t('Uninstall') . ' | Drupal');

    foreach (\Drupal::service('extension.list.module')->getAllInstalledInfo() as $module => $info) {
      $field_name = "uninstall[$module]";
      if (!empty($info['required'])) {
        // A required module should not be listed on the uninstall page.
        $this->assertSession()->fieldNotExists($field_name);
      }
      else {
        $this->assertSession()->fieldExists($field_name);
      }
    }

    // Be sure labels are rendered properly.
    // @see regression https://www.drupal.org/node/2512106
    $this->assertRaw('<label for="edit-uninstall-node" class="module-name table-filter-text-source">Node</label>');

    $this->assertText(\Drupal::translation()->translate('The following reason prevents Node from being uninstalled:'));
    $this->assertText(\Drupal::translation()->translate('There is content for the entity type: Content'));
    // Delete the node to allow node to be uninstalled.
    $node->delete();

    // Uninstall module_test.
    $edit = [];
    $edit['uninstall[module_test]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->assertText(\Drupal::translation()->translate('Configuration updates'), 'Configuration updates listed on the module install confirmation page.');
    $this->assertText($node_type->label());
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Uninstall node testing that the configuration that will be deleted is
    // listed.
    $node_dependencies = \Drupal::service('config.manager')->findConfigEntityDependentsAsEntities('module', ['node']);
    $edit = [];
    $edit['uninstall[node]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertText(\Drupal::translation()->translate('Configuration deletions'), 'Configuration deletions listed on the module install confirmation page.');
    $this->assertNoText(\Drupal::translation()->translate('Configuration updates'), 'No configuration updates listed on the module install confirmation page.');

    $entity_types = [];
    foreach ($node_dependencies as $entity) {
      $label = $entity->label() ?: $entity->id();
      $this->assertText($label);
      $entity_types[] = $entity->getEntityTypeId();
    }
    $entity_types = array_unique($entity_types);
    foreach ($entity_types as $entity_type_id) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      // Add h3's since the entity type label is often repeated in the entity
      // labels.
      $this->assertRaw('<h3>' . $entity_type->getLabel() . '</h3>');
    }

    // Set a unique cache entry to be able to test whether all caches are
    // cleared during the uninstall.
    \Drupal::cache()->set('uninstall_test', 'test_uninstall_page', Cache::PERMANENT);
    $cached = \Drupal::cache()->get('uninstall_test');
    $this->assertEqual($cached->data, 'test_uninstall_page', new FormattableMarkup('Cache entry found: @bin', ['@bin' => $cached->data]));

    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $this->assertNoRaw('&lt;label', 'The page does not have double escaped HTML tags.');

    // Make sure our unique cache entry is gone.
    $cached = \Drupal::cache()->get('uninstall_test');
    $this->assertFalse($cached, 'Cache entry not found');
    // Make sure we get an error message when we try to confirm uninstallation
    // of an empty list of modules.
    $this->drupalGet('admin/modules/uninstall/confirm');
    $this->assertText(t('The selected modules could not be uninstalled, either due to a website problem or due to the uninstall confirmation form timing out. Please try again.'), 'Module uninstall confirmation form displays error message');

    // Make sure confirmation page is accessible only during uninstall process.
    $this->drupalGet('admin/modules/uninstall/confirm');
    $this->assertUrl('admin/modules/uninstall');
    $this->assertTitle(t('Uninstall') . ' | Drupal');

    // Make sure the correct error is shown when no modules are selected.
    $edit = [];
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertText(t('No modules selected.'), 'No module is selected to uninstall');
  }

  /**
   * Tests that a module which fails to install can still be uninstalled.
   */
  public function testFailedInstallStatus() {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $message = 'Exception thrown when installing module_installer_config_test with an invalid configuration file.';
    try {
      $this->container->get('module_installer')->install(['module_installer_config_test']);
      $this->fail($message);
    }
    catch (EntityMalformedException $e) {
      $this->pass($message);
    }

    // Even though the module failed to install properly, its configuration
    // status is "enabled" and should still be available to uninstall.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertText('Module installer config test');
    $edit['uninstall[module_installer_config_test]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'));
    $this->assertNoText('Module installer config test');
  }

}
