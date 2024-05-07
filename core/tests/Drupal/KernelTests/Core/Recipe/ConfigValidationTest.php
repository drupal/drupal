<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Recipe\InvalidConfigException;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Recipe
 */
class ConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * This test depends on us being able to create invalid config, so we can
   * ensure that validatable config is validated by the recipe runner.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Creates a recipe with invalid config data in a particular file.
   *
   * @param string $file
   *   The name of the file (in the recipe's `config` directory) which should
   *   have invalid data.
   *
   * @return \Drupal\Core\Recipe\Recipe
   *   A wrapper around the created recipe.
   */
  private function createRecipeWithInvalidDataInFile(string $file): Recipe {
    $dir = uniqid('public://');
    mkdir($dir . '/config', recursive: TRUE);

    $data = file_get_contents($this->getDrupalRoot() . '/core/modules/config/tests/config_test/config/install/config_test.types.yml');
    assert(is_string($data));
    $data = Yaml::decode($data);
    // The `array` key needs to be an array, not an integer. If the config is
    // validated, this will raise a validation error.
    /** @var mixed[] $data */
    $data['array'] = 39;
    file_put_contents($dir . '/config/' . $file, Yaml::encode($data));

    $recipe = <<<YAML
name: Config validation test
install:
  - config_test
YAML;
    file_put_contents($dir . '/recipe.yml', $recipe);

    return Recipe::createFromDirectory($dir);
  }

  /**
   * Tests that the recipe runner only validates config which is validatable.
   */
  public function testValidatableConfigIsValidated(): void {
    // Since config_test.types is not validatable, there should not be a
    // validation error.
    $recipe = $this->createRecipeWithInvalidDataInFile('config_test.types.yml');
    RecipeRunner::processRecipe($recipe);
    $this->assertFalse($this->config('config_test.types')->isNew());

    // If we create a config object which IS fully validatable, and has invalid
    // data, we should get a validation error.
    $recipe = $this->createRecipeWithInvalidDataInFile('config_test.types.fully_validatable.yml');
    $this->expectException(InvalidConfigException::class);
    $this->expectExceptionMessage('There were validation errors in config_test.types.fully_validatable');
    RecipeRunner::processRecipe($recipe);
  }

}
