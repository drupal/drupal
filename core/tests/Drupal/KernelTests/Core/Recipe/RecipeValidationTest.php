<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeFileException;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Recipe
 */
class RecipeValidationTest extends KernelTestBase {

  /**
   * Data provider for ::testRecipeValidation().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerRecipeValidation(): iterable {
    yield 'name is correct' => [
      'name: Correct name',
      NULL,
    ];
    yield 'name missing' => [
      '{}',
      [
        '[name]' => ['This field is missing.'],
      ],
    ];
    yield 'name is not a string' => [
      'name: 39',
      [
        '[name]' => ['This value should be of type string.'],
      ],
    ];
    yield 'name is null' => [
      'name: ~',
      [
        '[name]' => ['This value should not be blank.'],
      ],
    ];
    yield 'name is blank' => [
      "name: ''",
      [
        '[name]' => ['This value should not be blank.'],
      ],
    ];
    yield 'name has invalid characters' => [
      <<<YAML
name: |
  My
  Amazing Recipe
YAML,
      [
        '[name]' => ['Recipe names cannot span multiple lines or contain control characters.'],
      ],
    ];
    yield 'description is correct' => [
      <<<YAML
name: Correct description
description: 'This is the correct description of a recipe.'
YAML,
      NULL,
    ];
    yield 'description is not a string' => [
      <<<YAML
name: Bad description
description: [Nope!]
YAML,
      [
        '[description]' => ['This value should be of type string.'],
      ],
    ];
    yield 'description is blank' => [
      <<<YAML
name: Blank description
description: ''
YAML,
      [
        '[description]' => ['This value should not be blank.'],
      ],
    ];
    yield 'description is null' => [
      <<<YAML
name: Null description
description: ~
YAML,
      [
        '[description]' => ['This value should not be blank.'],
      ],
    ];
    yield 'description contains control characters' => [
      <<<YAML
name: Bad description
description: "I have a\b bad character."
YAML,
      [
        '[description]' => ['The recipe description cannot contain control characters, only visible characters.'],
      ],
    ];
    yield 'type is correct' => [
      <<<YAML
name: Correct type
type: Testing
YAML,
      NULL,
    ];
    yield 'type is not a string' => [
      <<<YAML
name: Bad type
type: 39
YAML,
      [
        '[type]' => ['This value should be of type string.'],
      ],
    ];
    yield 'type is blank' => [
      <<<YAML
name: Blank type
type: ''
YAML,
      [
        '[type]' => ['This value should not be blank.'],
      ],
    ];
    yield 'type is null' => [
      <<<YAML
name: Null type
type: ~
YAML,
      [
        '[type]' => ['This value should not be blank.'],
      ],
    ];
    yield 'type has invalid characters' => [
      <<<YAML
name: Invalid type
type: |
  My
  Amazing Recipe
YAML,
      [
        '[type]' => ['Recipe type cannot span multiple lines or contain control characters.'],
      ],
    ];
    // @todo Test valid recipe once https://www.drupal.org/i/3421197 is in.
    yield 'recipes list is scalar' => [
      <<<YAML
name: Bad recipe list
recipes: 39
YAML,
      [
        '[recipes]' => ['This value should be of type iterable.'],
      ],
    ];
    yield 'recipes list has a blank entry' => [
      <<<YAML
name: Invalid recipe
recipes: ['']
YAML,
      [
        '[recipes][0]' => ['This value should not be blank.'],
      ],
    ];
    yield 'recipes list has a non-existent recipe' => [
      <<<YAML
name: Non-existent recipe
recipes:
  - vaporware
YAML,
      [
        '[recipes][0]' => ['The vaporware recipe does not exist.'],
      ],
    ];
    yield 'recipe depends on itself' => [
      <<<YAML
name: 'Inception'
recipes:
  - no_extensions
YAML,
      [
        '[recipes][0]' => ['The "no_extensions" recipe cannot depend on itself.'],
      ],
      'no_extensions',
    ];
    yield 'extension list is scalar' => [
      <<<YAML
name: Bad extension list
install: 39
YAML,
      [
        '[install]' => ['This value should be of type iterable.'],
      ],
    ];
    yield 'extension list has a blank entry' => [
      <<<YAML
name: Blank extension list
install: ['']
YAML,
      [
        '[install][0]' => ['This value should not be blank.'],
      ],
    ];
    yield 'installing unknown extensions' => [
      <<<YAML
name: 'Unknown extensions'
install:
  - config test
  - drupal:color
YAML,
      [
        '[install][0]' => ['"config test" is not a known module or theme.'],
        '[install][1]' => ['"color" is not a known module or theme.'],
      ],
    ];
    yield 'only installs extension' => [
      <<<YAML
name: 'Only installs extensions'
install:
  - filter
  - drupal:claro
YAML,
      NULL,
    ];
    yield 'config import list is valid' => [
      <<<YAML
name: 'Correct config import list'
config:
  import:
    config_test: '*'
    claro:
      - claro.settings
YAML,
      NULL,
    ];
    yield 'config import list is scalar' => [
      <<<YAML
name: 'Bad config import list'
config:
  import: 23
YAML,
      [
        '[config][import]' => ['This value should be of type iterable.'],
      ],
    ];
    yield 'config import list has a blank entry' => [
      <<<YAML
name: Blank config import list
config:
  import: ['']
YAML,
      [
        '[config][import][0]' => ['This value should satisfy at least one of the following constraints: [1] This value should be identical to string "*". [2] Each element of this collection should satisfy its own set of constraints.'],
      ],
    ];
    yield 'config actions list is valid' => [
      <<<YAML
name: 'Correct config actions list'
install:
  - config_test
config:
  actions:
    config_test.dynamic.recipe:
      ensure_exists:
        label: 'Created by recipe'
      setProtectedProperty: 'Set by recipe'
YAML,
      NULL,
    ];
    yield 'config actions list is scalar' => [
      <<<YAML
name: 'Bad config actions list'
config:
  actions: 23
YAML,
      [
        '[config][actions]' => ['This value should be of type iterable.'],
      ],
    ];
    yield 'config actions list has a blank entry' => [
      <<<YAML
name: Blank config actions list
config:
  actions: ['']
YAML,
      [
        '[config][actions][0]' => [
          'This value should be of type array.',
          'This value should not be blank.',
          'Config actions cannot be applied to 0 because the 0 extension is not installed, and is not installed by this recipe or any of the recipes it depends on.',
        ],
      ],
    ];
  }

  /**
   * Tests the validation of recipe.yml file.
   *
   * @param string $recipe
   *   The contents of the `recipe.yml` file.
   * @param string[][]|null $expected_violations
   *   (Optional) The expected validation violations, keyed by property path.
   *   Each value should be an array of error messages expected for that
   *   property.
   * @param string|null $recipe_name
   *   (optional) The name of the directory containing `recipe.yml`, or NULL to
   *   randomly generate one.
   *
   * @dataProvider providerRecipeValidation
   */
  public function testRecipeValidation(string $recipe, ?array $expected_violations, ?string $recipe_name = NULL): void {
    $dir = 'public://' . ($recipe_name ?? uniqid());
    mkdir($dir);
    file_put_contents($dir . '/recipe.yml', $recipe);

    try {
      Recipe::createFromDirectory($dir);
      // If there was no error, we'd better not have been expecting any.
      $this->assertNull($expected_violations, 'Validation errors were expected, but there were none.');
    }
    catch (RecipeFileException $e) {
      $this->assertIsArray($expected_violations, 'There were validation errors, but none were expected.');
      $this->assertIsObject($e->violations);

      $actual_violations = [];
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      foreach ($e->violations as $violation) {
        $property_path = $violation->getPropertyPath();
        $actual_violations[$property_path][] = (string) $violation->getMessage();
      }
      ksort($actual_violations);
      ksort($expected_violations);
      $this->assertSame($expected_violations, $actual_violations);
    }
  }

}
