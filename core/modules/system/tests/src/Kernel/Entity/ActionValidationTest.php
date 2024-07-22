<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests validation of action entities.
 *
 * @group system
 * @group #slow
 */
class ActionValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = ['type'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Action::create([
      'id' => 'test',
      'label' => 'Test',
      'type' => 'test',
      'plugin' => 'action_goto_action',
    ]);
    $this->entity->save();
  }

  /**
   * Action IDs are atypical in that they allow periods in the machine name.
   */
  public static function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();
    // Remove the existing test case that verifies a machine name containing
    // periods is invalid.
    self::assertSame(['period.separated', FALSE], $cases['INVALID: period separated']);
    unset($cases['INVALID: period separated']);
    // And instead add a test case that verifies it is allowed for blocks.
    $cases['VALID: period separated'] = ['period.separated', TRUE];
    return $cases;
  }

  /**
   * Tests that the action plugin ID is validated.
   */
  public function testInvalidPluginId(): void {
    $this->entity->set('plugin', 'non_existent');
    $this->assertValidationErrors([
      'plugin' => "The 'non_existent' plugin does not exist.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $valid_values['id'] = 'test_changed';
    parent::testImmutableProperties($valid_values);
  }

  /**
   * {@inheritdoc}
   */
  public function testLabelValidation(): void {
    static::setLabel($this->entity, "Multi\nLine");
    $this->assertValidationErrors(['label' => "Labels are not allowed to span multiple lines or contain control characters."]);
  }

}
