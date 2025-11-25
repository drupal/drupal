<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Recipe\ConsoleInputCollector;
use Drupal\Core\Recipe\InputCollectorInterface;
use Drupal\Core\Recipe\InputConfigurator;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Tests Input.
 */
#[Group('Recipe')]
#[CoversClass(InputConfigurator::class)]
#[RunTestsInSeparateProcesses]
class InputTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * The recipe.
   */
  private readonly Recipe $recipe;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');
    $this->config('core.menu.static_menu_link_overrides')
      ->set('definitions', [])
      ->save();
    $this->config('system.site')
      ->set('langcode', 'en')
      ->set('name', 'Testing!')
      ->set('mail', 'ben@deep.space')
      ->set('uuid', $this->container->get(UuidInterface::class)->generate())
      ->save();

    $this->recipe = Recipe::createFromDirectory($this->getDrupalRoot() . '/core/tests/fixtures/recipes/input_test');
  }

  /**
   * Tests getting the default value from configuration.
   */
  public function testDefaultValueFromConfig(): void {
    // Collect the input values before processing the recipe, using a mocked
    // collector that will always return the default value.
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->any())
      ->method('collectValue')
      ->withAnyParameters()
      ->willReturnArgument(2);

    $this->recipe->input->collectAll($collector);
    RecipeRunner::processRecipe($this->recipe);

    $this->assertSame("Dries Buytaert's Turf", $this->config('system.site')->get('name'));
  }

  /**
   * Tests input validation.
   */
  public function testInputIsValidated(): void {
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->atLeastOnce())
      ->method('collectValue')
      ->willReturnCallback(function (string $name) {
        return match($name) {
          'create_node_type.node_type' => 'test',
          'input_test.owner' => 'hack',
        };
      });

    try {
      $this->recipe->input->collectAll($collector);
      $this->fail('Expected an exception due to validation failure, but none was thrown.');
    }
    catch (ValidationFailedException $e) {
      $value = $e->getValue();
      $this->assertInstanceOf(TypedDataInterface::class, $value);
      $this->assertSame('hack', $value->getValue());
      $this->assertSame("I don't think you should be owning sites.", (string) $e->getViolations()->get(0)->getMessage());
    }
  }

  /**
   * Tests prompt arguments are forwarded.
   *
   * @legacy-covers \Drupal\Core\Recipe\ConsoleInputCollector::collectValue
   */
  public function testPromptArgumentsAreForwarded(): void {
    $io = $this->createMock(StyleInterface::class);
    $io->expects($this->once())
      ->method('ask')
      ->with('What is the capital of Assyria?', "I don't know that!")
      ->willReturn('<scream>');

    $recipe = $this->createRecipe(<<<YAML
name: 'Collecting prompt input'
input:
  capital:
    data_type: string
    description: The capital of a long-defunct country.
    prompt:
      method: ask
      arguments:
        question: What is the capital of Assyria?
        # This argument should be discarded.
        validator: 'test_validator'
    default:
      source: value
      value: "I don't know that!"
YAML
    );
    $collector = new ConsoleInputCollector(
      $this->createMock(InputInterface::class),
      $io,
    );
    $recipe->input->collectAll($collector);
    $this->assertSame(['capital' => '<scream>'], $recipe->input->getValues());
  }

  /**
   * Tests missing arguments throws exception.
   *
   * @legacy-covers \Drupal\Core\Recipe\ConsoleInputCollector::collectValue
   */
  public function testMissingArgumentsThrowsException(): void {
    $recipe = $this->createRecipe(<<<YAML
name: 'Collecting prompt input'
input:
  capital:
    data_type: string
    description: The capital of a long-defunct country.
    prompt:
      method: ask
    default:
      source: value
      value: "I don't know that!"
YAML
    );
    $collector = new ConsoleInputCollector(
      $this->createMock(InputInterface::class),
      $this->createMock(StyleInterface::class),
    );

    $this->expectException(\ArgumentCountError::class);
    $this->expectExceptionMessage('Argument #1 ($question) not passed');
    $recipe->input->collectAll($collector);
  }

  /**
   * Tests getting the fallback default value from non-existing configuration.
   *
   * @legacy-covers \Drupal\Core\Recipe\InputConfigurator::getDefaultValue
   */
  public function testDefaultValueFromNonExistentConfigWithFallback(): void {
    $recipe_data = [
      'name' => 'Default value from non-existent config',
      'input' => [
        'capital' => [
          'data_type' => 'string',
          'description' => 'This will use the fallback value.',
          'default' => [
            'source' => 'config',
            'config' => ['foo.baz', 'bar'],
            'fallback' => 'fallback',
          ],
        ],
      ],
    ];
    $recipe = $this->createRecipe($recipe_data);
    // Mock an input collector that will return the default value.
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->atLeastOnce())
      ->method('collectValue')
      ->withAnyParameters()
      ->willReturnArgument(2);

    $recipe->input->collectAll($collector);
    $this->assertSame(['capital' => 'fallback'], $recipe->input->getValues());

    // NULL is an allowable fallback value.
    $recipe_data['input']['capital']['default']['fallback'] = NULL;
    $recipe = $this->createRecipe($recipe_data);
    $recipe->input->collectAll($collector);
    $this->assertSame(['capital' => NULL], $recipe->input->getValues());

    // If there's no fallback value at all, we should get an exception.
    unset($recipe_data['input']['capital']['default']['fallback']);
    $recipe = $this->createRecipe($recipe_data);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("The 'foo.baz' config object does not exist.");
    $recipe->input->collectAll($collector);
  }

  /**
   * Tests input with literals.
   */
  public function testLiterals(): void {
    $recipe = $this->createRecipe(<<<YAML
name: Literals as input
install:
  - config_test
input:
  capital:
    data_type: string
    description: Your favorite state capital.
    default:
      source: value
      value: Boston
  some_int:
    data_type: integer
    description: This is an integer and should be stored as an integer.
    default:
      source: value
      value: 1234
  some_bool:
    data_type: boolean
    description: This is a boolean and should be stored as a boolean.
    default:
      source: value
      value: false
  some_float:
    data_type: float
    description: Pi is a float, should be stored as a float.
    default:
      source: value
      value: 3.141
config:
  actions:
    config_test.types:
      simpleConfigUpdate:
        int: \${some_int}
        boolean: \${some_bool}
        float: \${some_float}
    system.site:
      simpleConfigUpdate:
        name: '\${capital} rocks!'
        slogan: int is \${some_int}, bool is \${some_bool} and float is \${some_float}
YAML
    );
    // Mock a collector that only returns the default value.
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->any())
      ->method('collectValue')
      ->withAnyParameters()
      ->willReturnArgument(2);
    $recipe->input->collectAll($collector);

    RecipeRunner::processRecipe($recipe);

    $config = $this->config('config_test.types');
    $this->assertSame(1234, $config->get('int'));
    $this->assertFalse($config->get('boolean'));
    $this->assertSame(3.141, $config->get('float'));

    $config = $this->config('system.site');
    $this->assertSame("Boston rocks!", $config->get('name'));
    $this->assertSame('int is 1234, bool is  and float is 3.141', $config->get('slogan'));
  }

  /**
   * Tests using input values in entity IDs for config actions.
   */
  public function testInputInConfigEntityIds(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('node'));

    $collector = new class () implements InputCollectorInterface {

      /**
       * {@inheritdoc}
       */
      public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed {
        return $default_value;
      }

    };
    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/input_test');
    $recipe->input->collectAll($collector);
    RecipeRunner::processRecipe($recipe);
    $this->assertInstanceOf(NodeType::class, NodeType::load('test'));

    // Using an input placeholder in a non-identifying part of the config entity
    // ID should cause an exception.
    $recipe = $this->createRecipe([
      'name' => 'Invalid use of an input in config entity ID',
      'config' => [
        'actions' => [
          'node.${anything}.test' => [
            'createIfNotExists' => [
              'id' => 'test',
            ],
          ],
        ],
      ],
    ]);
    $recipe->input->collectAll($collector);
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage("The entity type for the config name 'node.\${anything}.test' could not be identified.");
    RecipeRunner::processRecipe($recipe);
  }

  /**
   * Tests that the askHidden prompt forwards arguments correctly.
   */
  public function testAskHiddenPromptArgumentsForwarded(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $io = new SymfonyStyle($input, $output);

    $recipe = $this->createRecipe(<<<YAML
name: 'Prompt askHidden Test'
input:
  foo:
    data_type: string
    description: Foo
    prompt:
      method: askHidden
    default:
      source: value
      value: bar
YAML
    );
    $collector = new ConsoleInputCollector($input, $io);
    // askHidden prompt should have an ArgumentCountError rather than a general
    // error.
    $this->expectException(\ArgumentCountError::class);
    $recipe->input->collectAll($collector);
  }

  /**
   * Tests getting default input values from environment variables.
   */
  public function testDefaultInputFromEnvironmentVariables(): void {
    $this->config('system.site')
      ->set('name', 'Hello Thar')
      ->set('slogan', 'Very important')
      ->save();

    $recipe = $this->createRecipe(<<<YAML
name: 'Input from environment variables'
input:
  name:
    data_type: string
    description: The name of the site.
    default:
      source: env
      env: SITE_NAME
config:
  actions:
    system.site:
      simpleConfigUpdate:
        name: \${name}
YAML
    );
    putenv('SITE_NAME=Input Test');

    // Mock a collector that only returns the default value.
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->any())
      ->method('collectValue')
      ->withAnyParameters()
      ->willReturnArgument(2);
    $recipe->input->collectAll($collector);

    RecipeRunner::processRecipe($recipe);
    $config = $this->config('system.site');
    $this->assertSame('Input Test', $config->get('name'));
  }

  /**
   * Tests getting a fallback value for an undefined environment variable.
   *
   * @legacy-covers \Drupal\Core\Recipe\InputConfigurator::getDefaultValue
   */
  public function testFallbackValueForUndefinedEnvironmentVariable(): void {
    $recipe_data = [
      'name' => 'Default value from undefined environment variable',
      'input' => [
        'capital' => [
          'data_type' => 'string',
          'description' => 'This will use the fallback value.',
          'default' => [
            'source' => 'env',
            'env' => 'NO_SUCH_THING',
            'fallback' => 'fallback',
          ],
        ],
      ],
    ];
    // Mock an input collector that will return the default value.
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->atLeastOnce())
      ->method('collectValue')
      ->withAnyParameters()
      ->willReturnArgument(2);

    $recipe = $this->createRecipe($recipe_data);
    $recipe->input->collectAll($collector);
    $this->assertSame(['capital' => 'fallback'], $recipe->input->getValues());

    // NULL is an allowable fallback value.
    $recipe_data['input']['capital']['default']['fallback'] = NULL;
    $recipe = $this->createRecipe($recipe_data);
    $recipe->input->collectAll($collector);
    $this->assertSame(['capital' => NULL], $recipe->input->getValues());

    // If there's no fallback value at all, we should get an exception.
    unset($recipe_data['input']['capital']['default']['fallback']);
    $recipe = $this->createRecipe($recipe_data);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("The 'NO_SUCH_THING' environment variable is not defined.");
    $recipe->input->collectAll($collector);
  }

  /**
   * Tests that the ask prompt for integer value doesn't fail with an error.
   */
  public function testAskPromptArgumentsInteger(): void {
    $input = $this->createMock(InputInterface::class);
    $io = $this->createMock(StyleInterface::class);
    $io->expects($this->once())
      ->method('ask')
      ->with('Who are you?', '123', NULL);

    $data_definition = DataDefinition::create('string')
      ->setSetting('prompt', [
        'method' => 'ask',
        'arguments' => [
          'question' => 'Who are you?',
        ],
      ]);

    (new ConsoleInputCollector($input, $io))
      ->collectValue('test.one', $data_definition, 123);
  }

}
