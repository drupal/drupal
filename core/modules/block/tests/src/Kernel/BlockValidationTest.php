<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of block entities.
 *
 * @group block
 * @group #slow
 */
class BlockValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithRequiredKeys = [
    'settings' => [
      "'id' is a required key.",
      "'label' is a required key.",
      "'label_display' is a required key.",
      "'provider' is a required key.",
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = [
    'provider',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stark']);

    $this->entity = Block::create([
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'system_powered_by_block',
      'settings' => [
        'label' => 'Powered by Drupal 🚀',
      ],
    ]);
    $this->entity->save();
  }

  /**
   * Tests validating a block with an unknown plugin ID.
   */
  public function testInvalidPluginId(): void {
    $this->entity->set('plugin', 'block_content:d7c9d8ba-663f-41b4-8756-86bc55c44653');
    // Block config entities with invalid block plugin IDs automatically fall
    // back to the `broken` block plugin.
    // @see https://www.drupal.org/node/2249303
    // @see \Drupal\Core\Block\BlockManager::getFallbackPluginId()
    // @see \Drupal\Core\Block\Plugin\Block\Broken
    $this->assertValidationErrors([]);

    $this->entity->set('plugin', 'non_existent');
    // @todo Expect error for this in https://www.drupal.org/project/drupal/issues/3377709
    $this->assertValidationErrors([]);
  }

  /**
   * Block names are atypical in that they allow periods in the machine name.
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
   * {@inheritdoc}
   */
  protected static function setLabel(ConfigEntityInterface $block, string $label): void {
    static::assertInstanceOf(Block::class, $block);
    $settings = $block->get('settings');
    static::assertNotEmpty($settings['label']);
    $settings['label'] = $label;
    $block->set('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function testLabelValidation(): void {
    static::setLabel($this->entity, "Multi\nLine");
    // TRICKY: because the Block config entity type does not specify a `label`
    // key, it is impossible for the generic ::testLabelValidation()
    // implementation in the base class to know at which property to expect a
    // validation error. Hence it is hardcoded in this case.
    $this->assertValidationErrors(['settings.label' => "Labels are not allowed to span multiple lines or contain control characters."]);
  }

  /**
   * Tests validating a block with a non-existent theme.
   */
  public function testThemeValidation(): void {
    $this->entity->set('theme', 'non_existent');
    $this->assertValidationErrors([
      'region' => 'This is not a valid region of the <em class="placeholder">non_existent</em> theme.',
      'theme' => "Theme 'non_existent' is not installed.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testRequiredPropertyValuesMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    parent::testRequiredPropertyValuesMissing([
      'region' => [
        'region' => [
          'This is not a valid region of the <em class="placeholder">stark</em> theme.',
          'This value should not be null.',
        ],
      ],
      'theme' => [
        'region' => 'This block does not say which theme it appears in.',
      ],
    ]);
  }

  /**
   * Tests validating a block's region in a theme.
   */
  public function testRegionValidation(): void {
    $this->entity->set('region', 'non_existent');
    $this->assertValidationErrors([
      'region' => 'This is not a valid region of the <em class="placeholder">stark</em> theme.',
    ]);
    // Set a valid region and assert it is saved properly.
    $this->entity->set('region', 'header');
    $this->assertValidationErrors([]);
  }

  /**
   * Tests validating weight.
   */
  public function testWeightValidation(): void {
    $this->entity->set('weight', $this->randomString());
    $this->assertValidationErrors([
      'weight' => [
        'This value should be a valid number.',
        'This value should be of the correct primitive type.',
      ],
    ]);

    $this->entity->set('weight', 10);
    $this->assertValidationErrors([]);
  }

  /**
   * @group legacy
   */
  public function testWeightCannotBeNull(): void {
    $this->entity->set('weight', NULL);
    $this->assertNull($this->entity->getWeight());
    $this->expectDeprecation('Saving a block with a non-integer weight is deprecated in drupal:11.1.0 and removed in drupal:12.0.0. See https://www.drupal.org/node/3462474');
    $this->entity->save();
  }

}
