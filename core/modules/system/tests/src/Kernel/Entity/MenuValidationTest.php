<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests validation of menu entities.
 *
 * @group system
 */
class MenuValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = [
    'description',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Menu::create([
      'id' => 'test-menu',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * Menu IDs are atypical: they allow dashes and disallow underscores.
   */
  public static function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();

    // Remove the existing test case that verifies a machine name containing
    // dashes is invalid.
    self::assertSame(['dash-separated', FALSE], $cases['INVALID: dash separated']);
    unset($cases['INVALID: dash separated']);
    // And instead add a test case that verifies it is allowed for menus.
    $cases['VALID: dash separated'] = ['dash-separated', TRUE];

    // Remove the existing test case that verifies a machine name containing
    // underscores is valid.
    self::assertSame(['underscore_separated', TRUE], $cases['VALID: underscore separated']);
    unset($cases['VALID: underscore separated']);
    // And instead add a test case that verifies it is disallowed for menus.
    $cases['INVALID: underscore separated'] = ['underscore_separated', FALSE];

    return $cases;
  }

  /**
   * Tests that description is optional, and limited to 512 characters.
   *
   * phpcs:disable Drupal.Commenting
   * cspell:disable
   *
   * @testWith [null, {}]
   *           ["", {}]
   *           ["This is an ASCII description.", {}]
   *           ["This is an emoji in a description: 🕺.", []]
   *           ["Iste et sunt ut cum. Suscipit officia molestias amet provident et sunt sit. Tenetur doloribus odit sapiente doloremque sequi id dignissimos. In rerum nihil voluptatibus architecto laborum. Repellendus eligendi laborum id nesciunt alias incidunt non. Tenetur deserunt facere voluptas nisi id. Aut ab eaque eligendi. Nihil quasi illum sit provident voluptatem repellat temporibus autem. Mollitia quisquam error facilis quasi voluptate. Dignissimos quis culpa nobis veritatis ut vel laudantium cumque. Rerum mollitia deleniti possimus placeat rerum. Reiciendis distinctio soluta voluptatem.", {"description": "This value is too long. It should have <em class=\"placeholder\">512</em> characters or less."}]
   *
   * cspell:enable
   * phpcs:enable Drupal.Commenting
   */
  public function testDescription(?string $description, array $expected_errors): void {
    $this->entity->set('description', $description);
    $this->assertValidationErrors($expected_errors);
  }

}
