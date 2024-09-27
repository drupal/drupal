<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @covers \Drupal\Core\Config\Action\Plugin\ConfigAction\PermissionsPerBundle
 * @covers \Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\PermissionsPerBundleDeriver
 *
 * @group Recipe
 */
class PermissionsPerBundleTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use MediaTypeCreationTrait;
  use RecipeTestTrait;
  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'media',
    'media_test_source',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('node');

    $this->createRole([], 'super_editor');

    $this->createContentType(['type' => 'article']);
    $this->createContentType(['type' => 'blog']);
    $this->createContentType(['type' => 'landing_page']);

    $this->createMediaType('test', ['id' => 'beautiful']);
    $this->createMediaType('test', ['id' => 'controversial']);
    $this->createMediaType('test', ['id' => 'special']);

    $this->createVocabulary(['vid' => 'tags']);
    $this->createVocabulary(['vid' => 'categories']);
  }

  /**
   * Tests granting multiple bundle-specific permissions.
   */
  public function testGrantPermissionsPerBundle(): void {
    $recipe_data = <<<YAML
name: 'Multi permissions!'
config:
  actions:
    user.role.super_editor:
      grantPermissionsForEachNodeType:
        - create %bundle content
        - edit own %bundle content
      grantPermissionsForEachMediaType:
        permissions:
          - create %bundle media
          - edit own %bundle media
      grantPermissionsForEachTaxonomyVocabulary: create terms in %bundle
YAML;
    $this->applyRecipeFromString($recipe_data);

    $expected_permissions = [
      'create article content',
      'create blog content',
      'create landing_page content',
      'edit own article content',
      'edit own blog content',
      'edit own landing_page content',
      'create beautiful media',
      'create controversial media',
      'create special media',
      'edit own beautiful media',
      'edit own controversial media',
      'edit own special media',
      'create terms in tags',
      'create terms in categories',
    ];
    $role = Role::load('super_editor');
    assert($role instanceof RoleInterface);
    foreach ($expected_permissions as $permission) {
      $this->assertTrue($role->hasPermission($permission));
    }
  }

  /**
   * Tests that the permissions-per-bundle action can only be applied to roles.
   */
  public function testActionIsOnlyAvailableToUserRoles(): void {
    $recipe_data = <<<YAML
name: 'Only for roles...'
config:
  actions:
    field.storage.node.body:
      grantPermissionsForEachNodeType:
        - create %bundle content
        - edit own %bundle content
YAML;

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "field_storage_config" entity does not support the "grantPermissionsForEachNodeType" config action.');
    $this->applyRecipeFromString($recipe_data);
  }

  /**
   * Tests granting permissions for one bundle, then all of them.
   */
  public function testGrantPermissionsOnOneBundleThenAll(): void {
    $recipe_data = <<<YAML
name: 'All bundles except one'
config:
  actions:
    user.role.super_editor:
      grantPermissions:
        - create beautiful media
        - edit own beautiful media
      grantPermissionsForEachMediaType:
        - create %bundle media
        - edit own %bundle media
YAML;
    $this->applyRecipeFromString($recipe_data);

    $role = Role::load('super_editor');
    $this->assertInstanceOf(Role::class, $role);
    $this->assertTrue($role->hasPermission('create beautiful media'));
    $this->assertTrue($role->hasPermission('edit own beautiful media'));
    $this->assertTrue($role->hasPermission('create controversial media'));
    $this->assertTrue($role->hasPermission('edit own beautiful media'));
  }

  /**
   * Tests granting permissions for all bundles except certain ones.
   */
  public function testGrantPermissionsToAllBundlesExceptSome(): void {
    $recipe_data = <<<YAML
name: 'Bundle specific permissions with some exceptions'
config:
  actions:
    user.role.super_editor:
      grantPermissionsForEachNodeType:
        permissions:
          - view %bundle revisions
        except:
          - article
          - blog
      grantPermissionsForEachMediaType:
        permissions: view any %bundle media revisions
        except:
          - controversial
      grantPermissionsForEachTaxonomyVocabulary:
        permissions:
          - view term revisions in %bundle
        except: tags
YAML;
    $this->applyRecipeFromString($recipe_data);

    $role = Role::load('super_editor');
    $this->assertInstanceOf(Role::class, $role);
    $this->assertTrue($role->hasPermission('view landing_page revisions'));
    $this->assertFalse($role->hasPermission('view article revisions'));
    $this->assertFalse($role->hasPermission('view blog revisions'));
    $this->assertTrue($role->hasPermission('view any beautiful media revisions'));
    $this->assertTrue($role->hasPermission('view any special media revisions'));
    $this->assertFalse($role->hasPermission('view any controversial media revisions'));
    $this->assertTrue($role->hasPermission('view term revisions in categories'));
    $this->assertFalse($role->hasPermission('view term revisions in tags'));
  }

  /**
   * Tests that there is an exception if the permission templates are invalid.
   *
   * @param mixed $value
   *   The permission template which should raise an error.
   *
   * @testWith [["a %Bundle permission"]]
   *   [""]
   *   [[]]
   */
  public function testInvalidValue(mixed $value): void {
    $value = Json::encode($value);

    $recipe_data = <<<YAML
name: 'Bad permission value'
config:
  actions:
    user.role.super_editor:
      grantPermissionsForEachMediaType: $value
YAML;
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage(" must be an array of strings that contain '%bundle'.");
    $this->applyRecipeFromString($recipe_data);
  }

  /**
   * Given a string of `recipe.yml` contents, applies it to the site.
   *
   * @param string $recipe_data
   *   The contents of `recipe.yml`.
   */
  private function applyRecipeFromString(string $recipe_data): void {
    $recipe = $this->createRecipe($recipe_data);
    RecipeRunner::processRecipe($recipe);
  }

}
