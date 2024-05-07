<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Recipe\Recipe;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\Core\Recipe\ConfigConfigurator
 * @group Recipe
 */
class ConfigConfiguratorTest extends KernelTestBase {

  public function testExistingConfigWithKeysInDifferentOrder(): void {
    $recipe_dir = uniqid('public://recipe_test_');
    mkdir($recipe_dir . '/config', recursive: TRUE);

    $this->enableModules(['system']);
    $this->installConfig('system');
    /** @var mixed[][] $original_data */
    $original_data = $this->config('system.site')->get();
    // Remove keys that are ignored during the comparison.
    unset($original_data['uuid'], $original_data['_core']);
    $recipe_data = $original_data;
    // Reorder an inner array, to ensure keys are sorted recursively.
    $recipe_data['page'] = array_reverse($original_data['page'], TRUE);
    $this->assertNotSame($original_data, $recipe_data);
    file_put_contents($recipe_dir . '/config/system.site.yml', Yaml::encode($recipe_data));

    $recipe = [
      'name' => 'Same config, different order',
      'type' => 'Testing',
    ];
    file_put_contents($recipe_dir . '/recipe.yml', Yaml::encode($recipe));

    // If there was a conflict with the pre-existing config, ConfigConfigurator
    // would throw an exception and the recipe would not be created. So all we
    // need to do here is assert that, in fact, we were able to create a recipe
    // object.
    $this->assertInstanceOf(Recipe::class, Recipe::createFromDirectory($recipe_dir));
  }

}
