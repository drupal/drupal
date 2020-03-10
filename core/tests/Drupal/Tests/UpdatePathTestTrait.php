<?php

namespace Drupal\Tests;

use Drupal\Core\Url;

/**
 * Trait UpdatePathTestTrait
 *
 * For use on \Drupal\Tests\BrowserTestBase tests.
 */
trait UpdatePathTestTrait {
  use RequirementsPageTrait;
  use SchemaCheckTestTrait;

  /**
   * Fail the test if there are failed updates.
   *
   * @var bool
   */
  protected $checkFailedUpdates = TRUE;

  /**
   * Helper function to run pending database updates.
   *
   * @param string|null $update_url
   *   The update URL.
   */
  protected function runUpdates($update_url = NULL) {
    if (!$update_url) {
      $update_url = Url::fromRoute('system.db_update');
    }
    require_once $this->root . '/core/includes/update.inc';
    // The site might be broken at the time so logging in using the UI might
    // not work, so we use the API itself.
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);

    $this->drupalGet($update_url);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));

    $this->doSelectionTest();
    // Run the update hooks.
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();

    // Ensure there are no failed updates.
    if ($this->checkFailedUpdates) {
      $failure = $this->cssSelect('.failure');
      if ($failure) {
        $this->fail('The update failed with the following message: "' . reset($failure)->getText() . '"');
      }

      // Ensure that there are no pending updates. Clear the schema version
      // static cache first in case it was accessed before running updates.
      drupal_get_installed_schema_version(NULL, TRUE);
      foreach (['update', 'post_update'] as $update_type) {
        switch ($update_type) {
          case 'update':
            $all_updates = update_get_update_list();
            break;
          case 'post_update':
            $all_updates = \Drupal::service('update.post_update_registry')->getPendingUpdateInformation();
            break;
        }
        foreach ($all_updates as $module => $updates) {
          if (!empty($updates['pending'])) {
            foreach (array_keys($updates['pending']) as $update_name) {
              $this->fail("The $update_name() update function from the $module module did not run.");
            }
          }
        }
      }

      // Ensure that the container is updated if any modules are installed or
      // uninstalled during the update.
      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
      $module_handler = $this->container->get('module_handler');
      $config_module_list = $this->config('core.extension')->get('module');
      $module_handler_list = $module_handler->getModuleList();
      $modules_installed = FALSE;
      // Modules that are in configuration but not the module handler have been
      // installed.
      foreach (array_keys(array_diff_key($config_module_list, $module_handler_list)) as $module) {
        $module_handler->addModule($module, drupal_get_path('module', $module));
        $modules_installed = TRUE;
      }
      $modules_uninstalled = FALSE;
      $module_handler_list = $module_handler->getModuleList();
      // Modules that are in the module handler but not configuration have been
      // uninstalled.
      foreach (array_keys(array_diff_key($module_handler_list, $config_module_list)) as $module) {
        $modules_uninstalled = TRUE;
        unset($module_handler_list[$module]);
      }
      if ($modules_installed || $modules_uninstalled) {
        // Note that resetAll() does not reset the kernel module list so we
        // have to do that manually.
        $this->kernel->updateModules($module_handler_list, $module_handler_list);
      }

      // If we have successfully clicked 'Apply pending updates' then we need to
      // clear the caches in the update test runner as this has occurred as part
      // of the updates.
      $this->resetAll();

      // The config schema can be incorrect while the update functions are being
      // executed. But once the update has been completed, it needs to be valid
      // again. Assert the schema of all configuration objects now.
      $names = $this->container->get('config.storage')->listAll();

      // Allow tests to opt out of checking specific configuration.
      $exclude = $this->getConfigSchemaExclusions();
      /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
      $typed_config = $this->container->get('config.typed');
      foreach ($names as $name) {
        if (in_array($name, $exclude, TRUE)) {
          // Skip checking schema if the config is listed in the
          // $configSchemaCheckerExclusions property.
          continue;
        }
        $config = $this->config($name);
        $this->assertConfigSchema($typed_config, $name, $config->get());
      }

      // Ensure that the update hooks updated all entity schema.
      $needs_updates = \Drupal::entityDefinitionUpdateManager()->needsUpdates();
      if ($needs_updates) {
        foreach (\Drupal::entityDefinitionUpdateManager()->getChangeSummary() as $entity_type_id => $summary) {
          $entity_type_label = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLabel();
          foreach ($summary as $message) {
            $this->fail("$entity_type_label: $message");
          }
        }
        // The above calls to `fail()` should prevent this from ever being
        // called, but it is here in case something goes really wrong.
        $this->assertFalse($needs_updates, 'After all updates ran, entity schema is up to date.');
      }
    }
  }

  /**
   * Tests the selection page.
   */
  protected function doSelectionTest() {
    // No-op. Tests wishing to do test the selection page or the general
    // update.php environment before running update.php can override this method
    // and implement their required tests.
  }

  /**
   * Installs the update_script_test module and makes an update available.
   */
  protected function ensureUpdatesToRun() {
    \Drupal::service('module_installer')->install(['update_script_test']);
    // Reset the schema so there is an update to run.
    \Drupal::database()->update('key_value')
      ->fields(['value' => serialize(8000)])
      ->condition('collection', 'system.schema')
      ->condition('name', 'update_script_test')
      ->execute();
  }

}
