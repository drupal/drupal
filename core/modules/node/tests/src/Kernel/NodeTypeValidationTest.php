<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\node\NodePreviewMode;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests validation of node_type entities.
 */
#[Group('node')]
#[Group('#slow')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class NodeTypeValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = [
    'description',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->entity = $this->createContentType();
  }

  /**
   * Tests that a node type's preview mode is constrained to certain values.
   */
  #[IgnoreDeprecations]
  public function testPreviewModeValidation(): void {
    $this->expectDeprecation('Calling Drupal\node\Entity\NodeType::setPreviewMode with an integer $preview_mode parameter is deprecated in drupal:11.3.0 and is removed in drupal:13.0.0. Use the \Drupal\node\NodePreviewMode enum instead. See https://www.drupal.org/node/3538666');
    $this->entity->setPreviewMode(38);
    $this->assertValidationErrors(['preview_mode' => 'The value you selected is not a valid choice.']);

    $this->entity->setPreviewMode(-1);
    $this->assertValidationErrors(['preview_mode' => 'The value you selected is not a valid choice.']);

    $allowed_values = NodePreviewMode::cases();
    foreach ($allowed_values as $allowed_value) {
      $this->entity->setPreviewMode($allowed_value);
      $this->assertValidationErrors([]);
    }
  }

  /**
   * Tests that description and help text can be NULL, but not empty strings.
   */
  public function testDescriptionAndHelpCannotBeEmpty(): void {
    $this->entity->set('description', NULL)->set('help', NULL);
    // The entity's getters should cast NULL values to empty strings.
    $this->assertSame('', $this->entity->getDescription());
    $this->assertSame('', $this->entity->getHelp());
    // But NULL values should be valid at the config level.
    $this->assertValidationErrors([]);

    // But they cannot be empty strings, because that doesn't make sense.
    $this->entity->set('description', '')->set('help', '');
    $this->assertValidationErrors([
      'description' => 'This value should not be blank.',
      'help' => 'This value should not be blank.',
    ]);
  }

  /**
   * Tests third party settings menu UI.
   */
  #[TestWith([TRUE, ["third_party_settings.menu_ui" => "'parent' is a required key."]])]
  #[TestWith([FALSE, []])]
  public function testThirdPartySettingsMenuUi(bool $third_party_settings_menu_ui_fully_validatable, array $expected_validation_errors): void {
    $this->enableModules(['menu_ui']);

    // Set or unset the `FullyValidatable` constraint on
    // `node.type.*.third_party.menu_ui`.
    $this->enableModules(['config_schema_test']);
    \Drupal::state()->set('config_schema_test_menu_ui_third_party_settings_fully_validatable', $third_party_settings_menu_ui_fully_validatable);
    $this->container->get('kernel')->rebuildContainer();
    $this->entity = $this->createContentType();

    // @see system.menu.main.yml
    $this->installConfig(['system']);
    $this->entity->setThirdPartySetting('menu_ui', 'available_menus', ['main']);

    $this->assertValidationErrors($expected_validation_errors);
  }

}
