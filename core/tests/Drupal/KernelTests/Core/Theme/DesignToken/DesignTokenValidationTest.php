<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\DesignToken;

use Drupal\Core\Theme\Entity\DesignToken;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of design_token entities.
 */
#[Group('design_token')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class DesignTokenValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $values = [
      'id' => 'my_id',
      'path' => 'foo.bar.baz',
      'type' => 'dimension',
      'scopes' => [
        ':root' => [
          'value' => 12,
          'unit' => 'px',
        ],
        '.card' => [
          'value' => 14,
          'unit' => 'rem',
        ],
        '%breadcrumb' => [
          'value' => 14,
          'unit' => 'rem',
        ],
      ],
    ];

    $this->entity = DesignToken::create($values);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    // If we don't clear the previous scopes here, we will get unrelated
    // validation errors (in addition to the one we're expecting), because the
    // scopes from the *old* type won't match the config schema for the
    // scopes of the *new* type.
    $this->entity->set('scopes', []);
    parent::testImmutableProperties([
      'type' => 'fontWeight',
    ]);
  }

}
