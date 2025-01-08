<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Recipe\ConsoleInputCollector;
use Drupal\Core\Recipe\InputCollectorInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @group Recipe
 * @covers \Drupal\Core\Recipe\InputConfigurator
 */
class InputTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

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

    $this->recipe = Recipe::createFromDirectory($this->getDrupalRoot() . '/core/recipes/feedback_contact_form');
  }

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

    $this->assertSame(['ben@deep.space'], ContactForm::load('feedback')?->getRecipients());
  }

  public function testInputIsValidated(): void {
    $collector = $this->createMock(InputCollectorInterface::class);
    $collector->expects($this->atLeastOnce())
      ->method('collectValue')
      ->with('feedback_contact_form.recipient', $this->isInstanceOf(DataDefinitionInterface::class), $this->anything())
      ->willReturn('not-an-email-address');

    try {
      $this->recipe->input->collectAll($collector);
      $this->fail('Expected an exception due to validation failure, but none was thrown.');
    }
    catch (ValidationFailedException $e) {
      $value = $e->getValue();
      $this->assertInstanceOf(TypedDataInterface::class, $value);
      $this->assertSame('not-an-email-address', $value->getValue());
      $this->assertSame('This value is not a valid email address.', (string) $e->getViolations()->get(0)->getMessage());
    }
  }

  /**
   * @covers \Drupal\Core\Recipe\ConsoleInputCollector::collectValue
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
   * @covers \Drupal\Core\Recipe\ConsoleInputCollector::collectValue
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

  public function testDefaultValueFromNonExistentConfig(): void {
    $recipe = $this->createRecipe(<<<YAML
name: 'Default value from non-existent config'
input:
  capital:
    data_type: string
    description: This will be erroneous.
    default:
      source: config
      config: ['foo.baz', 'bar']
YAML
    );
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("The 'foo.baz' config object does not exist.");
    $recipe->input->collectAll($this->createMock(InputCollectorInterface::class));
  }

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

}
