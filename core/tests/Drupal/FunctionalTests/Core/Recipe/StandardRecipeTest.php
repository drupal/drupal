<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\Tests\standard\Functional\StandardTest;
use Drupal\user\RoleInterface;

/**
 * Tests Standard recipe installation expectations.
 *
 * @group #slow
 * @group Recipe
 */
class StandardRecipeTest extends StandardTest {

  use RecipeTestTrait;

  /**
   * Tests Standard installation recipe.
   */
  public function testStandard(): void {
    // Install some modules that Standard has optional integrations with.
    \Drupal::service('module_installer')->install(['media_library', 'content_moderation']);

    // Export all the configuration so we can compare later.
    $this->copyConfig(\Drupal::service('config.storage'), \Drupal::service('config.storage.sync'));

    // Set theme to stark and uninstall the other themes.
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['stark']);
    $this->config('system.theme')->set('admin', '')->set('default', 'stark')->save();
    $theme_installer->uninstall(['claro', 'olivero']);

    // Determine which modules to uninstall.
    // If the database module has dependencies, they are expected too.
    $database_module_extension = \Drupal::service(ModuleExtensionList::class)->get(\Drupal::database()->getProvider());
    $database_modules = $database_module_extension->requires ? array_keys($database_module_extension->requires) : [];
    $database_modules[] = \Drupal::database()->getProvider();
    $keep = array_merge(['user', 'system', 'path_alias'], $database_modules);
    $uninstall = array_diff(array_keys(\Drupal::moduleHandler()->getModuleList()), $keep);
    foreach (['shortcut', 'field_config', 'filter_format', 'field_storage_config'] as $entity_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $storage->delete($storage->loadMultiple());
    }

    // Uninstall all the modules including the Standard profile.
    \Drupal::service('module_installer')->uninstall($uninstall);

    // Clean up entity displays before recipe import.
    foreach (['entity_form_display', 'entity_view_display'] as $entity_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $storage->delete($storage->loadMultiple());
    }

    // Clean up roles before recipe import.
    $storage = \Drupal::entityTypeManager()->getStorage('user_role');
    $roles = $storage->loadMultiple();
    // Do not delete the administrator role. There would be no user with the
    // permissions to create content.
    unset($roles[RoleInterface::ANONYMOUS_ID], $roles[RoleInterface::AUTHENTICATED_ID], $roles['administrator']);
    $storage->delete($roles);

    $this->applyRecipe('core/recipes/standard');
    // These recipes provide functionality that is only optionally part of the
    // Standard profile, so we need to explicitly apply them.
    $this->applyRecipe('core/recipes/editorial_workflow');
    $this->applyRecipe('core/recipes/audio_media_type');
    $this->applyRecipe('core/recipes/document_media_type');
    $this->applyRecipe('core/recipes/image_media_type');
    $this->applyRecipe('core/recipes/local_video_media_type');
    $this->applyRecipe('core/recipes/remote_video_media_type');

    // Remove the theme we had to install.
    \Drupal::service('theme_installer')->uninstall(['stark']);

    // Add a Home link to the main menu as Standard expects "Main navigation"
    // block on the page.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $this->submitForm([
      'title[0][value]' => 'Home',
      'link[0][uri]' => '<front>',
    ], 'Save');

    // Update sync directory config to have the same UUIDs so we can compare.
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = \Drupal::service('config.storage.sync');
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = \Drupal::service('config.storage');
    // @todo https://www.drupal.org/i/3439749 Determine if the the _core unset
    //   is correct.
    foreach ($active->listAll() as $name) {
      /** @var mixed[] $active_data */
      $active_data = $active->read($name);
      if ($sync->exists($name)) {
        /** @var mixed[] $sync_data */
        $sync_data = $sync->read($name);
        if (isset($sync_data['uuid'])) {
          $sync_data['uuid'] = $active_data['uuid'];
        }
        if (isset($sync_data['_core'])) {
          unset($sync_data['_core']);
        }
        /** @var array $sync_data */
        $sync->write($name, $sync_data);
      }
      if (isset($active_data['_core'])) {
        unset($active_data['_core']);
        $active->write($name, $active_data);
      }
      // @todo Remove this once https://drupal.org/i/3427564 lands.
      if ($name === 'node.settings') {
        unset($active_data['langcode']);
        $active->write($name, $active_data);
      }
    }

    // Ensure we have truly rebuilt the standard profile using recipes.
    // Uncomment the code below to see the differences in a single file.
    // phpcs:ignore Drupal.Files.LineLength
    // $this->assertSame($sync->read('node.settings'), $active->read('node.settings'));
    $comparer = $this->configImporter()->getStorageComparer();
    $expected_list = $comparer->getEmptyChangelist();
    // We expect core.extension to be different because standard is no longer
    // installed.
    $expected_list['update'] = ['core.extension'];
    $this->assertSame($expected_list, $comparer->getChangelist());

    // Standard ships two shortcuts; ensure they exist.
    $this->assertCount(2, Shortcut::loadMultiple());

    parent::testStandard();
  }

  /**
   * {@inheritdoc}
   */
  protected function installResponsiveImage(): void {
    // Overrides StandardTest::installResponsiveImage() in order to use the
    // recipe.
    $this->applyRecipe('core/recipes/standard_responsive_images');
  }

}
