<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeFileException;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Recipe
 * @group #slow
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
    yield 'config strict is not a boolean or array' => [
      <<<YAML
name: Invalid strict flag
config:
  strict: 40
YAML,
      [
        '[config][strict]' => ['This value must be a boolean, or a list of config names.'],
      ],
    ];
    yield 'config strict is an array of not-strings' => [
      <<<YAML
name: Invalid item in strict list
config:
  strict:
    - 40
YAML,
      [
        '[config][strict]' => ['This value must be a boolean, or a list of config names.'],
      ],
    ];
    yield 'config strict list contains blank strings' => [
      <<<YAML
name: Invalid item in strict list
config:
  strict:
    - ''
YAML,
      [
        '[config][strict]' => ['This value must be a boolean, or a list of config names.'],
      ],
    ];
    yield 'config strict list item does not have a period' => [
      <<<YAML
name: Invalid item in strict list
config:
  strict:
    - 'something'
YAML,
      [
        '[config][strict]' => ['This value must be a boolean, or a list of config names.'],
      ],
    ];
    yield 'valid strict list' => [
      <<<YAML
name: Valid strict list
config:
  strict:
    - system.menu.foo
YAML,
      NULL,
    ];
    yield 'config actions list is valid' => [
      <<<YAML
name: 'Correct config actions list'
install:
  - config_test
config:
  actions:
    config_test.dynamic.recipe:
      createIfNotExists:
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
    yield 'input definitions are an indexed array' => [
      <<<YAML
name: Bad input definitions
input:
  - data_type: string
    description: A valid enough input, but in an indexed array.
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'input data type is missing' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    description: What's my data type?
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][data_type]' => ['This field is missing.'],
      ],
    ];
    yield 'input description is not a string' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 3.141
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][description]' => ['This value should be of type string.'],
      ],
    ];
    yield 'input description is blank' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: ''
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][description]' => ['This value should not be blank.'],
      ],
    ];
    yield 'input constraints are an indexed array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Constraints need to be associative'
    constraints:
      - Type: string
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][constraints]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'input data type is unknown' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: power_tool
    description: 'Bad data type'
    prompt:
      method: ask
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][data_type]' => ["The 'power_tool' plugin does not exist."],
      ],
    ];
    yield 'data type is not a primitive' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: list
    description: 'Non-primitive data type'
    default:
      source: value
      value: [Yeah, No]
YAML,
      [
        '[input][foo][data_type]' => [
          "The 'list' plugin must implement or extend " . PrimitiveInterface::class . '.',
        ],
      ],
    ];
    yield 'prompt definition is not an array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Prompt info must be an array'
    prompt: ask
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][prompt]' => ['This value should be of type array|(Traversable&ArrayAccess).'],
      ],
    ];
    yield 'invalid prompt method' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Bad prompt type'
    prompt:
      method: whoops
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][prompt][method]' => ['The value you selected is not a valid choice.'],
      ],
    ];
    yield 'prompt arguments are an indexed array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Prompt arguments must be associative'
    prompt:
      method: ask
      arguments: [1, 2]
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][prompt][arguments]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'form element is not an array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Form element must be array'
    form: true
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][form]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'form element is an indexed array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Form element must be associative'
    form: [text]
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][form]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'form element is an empty array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Form elements cannot be empty'
    form: []
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][form]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'form element has children' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Form elements cannot have children'
    form:
      '#type': textfield
      child:
        '#type': select
    default:
      source: value
      value: Here be dragons
YAML,
      [
        '[input][foo][form]' => ['Form elements for recipe inputs cannot have child elements.'],
      ],
    ];
    yield 'Valid form element' => [
      <<<YAML
name: Form input definitions
input:
  foo:
    data_type: string
    description: 'This has a valid form element'
    form:
      '#type': textfield
    default:
      source: value
      value: Here be dragons
YAML,
      NULL,
    ];
    yield 'input definition without default value' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'No default'
    prompt:
      method: ask
YAML,
      [
        '[input][foo][default]' => ['This field is missing.'],
      ],
    ];
    yield 'default value from config is not defined' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
YAML,
      [
        '[input][foo][default]' => ["The 'config' key is required."],
      ],
    ];
    yield 'default value from config is not an array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: email
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
      config: 'system.site:mail'
YAML,
      [
        '[input][foo][default][config]' => ['This value should be of type list.'],
      ],
    ];
    yield 'default value from config has too few values' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: email
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
      config: ['system.site:mail']
YAML,
      [
        '[input][foo][default][config]' => ['This collection should contain exactly 2 elements.'],
      ],
    ];
    yield 'default value from config is an associative array' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: email
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
      config:
        name: system.site
        key: mail
YAML,
      [
        '[input][foo][default][config]' => ['This value should be of type list.'],
      ],
    ];
    yield 'default value from config has non-string values' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: string
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
      config: ['system.site', 39]
YAML,
      [
        '[input][foo][default][config][1]' => ['This value should be of type string.'],
      ],
    ];
    yield 'default value from config has empty strings' => [
      <<<YAML
name: Bad input definitions
input:
  foo:
    data_type: email
    description: 'Bad default definition'
    prompt:
      method: ask
    default:
      source: config
      config: ['', 'mail']
YAML,
      [
        '[input][foo][default][config][0]' => ['This value should not be blank.'],
      ],
    ];
    yield 'valid default value from config' => [
      <<<YAML
name: Good input definitions
input:
  foo:
    data_type: email
    description: 'Good default definition'
    prompt:
      method: ask
    default:
      source: config
      config: ['system.site', 'mail']
YAML,
      NULL,
    ];
    yield 'extra is present and not an array' => [
      <<<YAML
name: Bad extra
extra: 'yes!'
YAML,
      [
        '[extra]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'extra is an indexed array' => [
      <<<YAML
name: Bad extra
extra:
  - one
  - two
YAML,
      [
        '[extra]' => ['This value should be of type associative_array.'],
      ],
    ];
    yield 'invalid key in extra' => [
      <<<YAML
name: Bad extra
extra:
  'not a valid extension name': true
YAML,
      [
        '[extra]' => ['not a valid extension name is not a valid extension name.'],
      ],
    ];
    yield 'valid extra' => [
      <<<YAML
name: Bad extra
extra:
  project_browser:
    yes: sir
YAML,
      NULL,
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
