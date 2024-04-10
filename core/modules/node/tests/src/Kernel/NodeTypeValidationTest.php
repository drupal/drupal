<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of node_type entities.
 *
 * @group node
 */
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
    $this->installConfig('node');
    $this->entity = $this->createContentType();
  }

  /**
   * Tests that a node type's preview mode is constrained to certain values.
   */
  public function testPreviewModeValidation(): void {
    $this->entity->setPreviewMode(38);
    $this->assertValidationErrors(['preview_mode' => 'The value you selected is not a valid choice.']);

    $this->entity->setPreviewMode(-1);
    $this->assertValidationErrors(['preview_mode' => 'The value you selected is not a valid choice.']);

    $allowed_values = [
      DRUPAL_DISABLED,
      DRUPAL_OPTIONAL,
      DRUPAL_REQUIRED,
    ];
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
   * @testWith [true, {"third_party_settings.menu_ui": "'parent' is a required key."}]
   *           [false, {}]
   */
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
