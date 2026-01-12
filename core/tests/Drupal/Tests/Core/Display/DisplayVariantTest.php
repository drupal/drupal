<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Display;

use Drupal\Core\Display\VariantBase;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\Core\Display\VariantBase.
 */
#[CoversClass(VariantBase::class)]
#[Group('Display')]
class DisplayVariantTest extends UnitTestCase {

  /**
   * Sets up a display variant plugin for testing.
   *
   * @param array $configuration
   *   An array of plugin configuration.
   * @param array $definition
   *   The plugin definition array.
   *
   * @return \Drupal\Core\Display\VariantBase|\PHPUnit\Framework\MockObject\MockObject
   *   A mocked display variant plugin.
   */
  public function setUpDisplayVariant($configuration = [], $definition = []): VariantBase&MockObject {
    return $this->getMockBuilder(VariantBase::class)
      ->setConstructorArgs([$configuration, 'test', $definition])
      ->onlyMethods(['build'])
      ->getMock();
  }

  /**
   * Tests the label() method.
   */
  public function testLabel(): void {
    $display_variant = $this->setUpDisplayVariant(['label' => 'foo']);
    $this->assertSame('foo', $display_variant->label());
  }

  /**
   * Tests the label() method using a default value.
   */
  public function testLabelDefault(): void {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertSame('', $display_variant->label());
  }

  /**
   * Tests the getWeight() method.
   */
  public function testGetWeight(): void {
    $display_variant = $this->setUpDisplayVariant(['weight' => 5]);
    $this->assertSame(5, $display_variant->getWeight());
  }

  /**
   * Tests the getWeight() method using a default value.
   */
  public function testGetWeightDefault(): void {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertSame(0, $display_variant->getWeight());
  }

  /**
   * Tests the getConfiguration() method.
   */
  #[DataProvider('providerTestGetConfiguration')]
  public function testGetConfiguration($configuration, $expected): void {
    $display_variant = $this->setUpDisplayVariant($configuration);

    $this->assertSame($expected, $display_variant->getConfiguration());
  }

  /**
   * Provides test data for testGetConfiguration().
   */
  public static function providerTestGetConfiguration(): array {
    $data = [];
    $data[] = [
      [],
      [
        'id' => 'test',
        'label' => '',
        'uuid' => '',
        'weight' => 0,
      ],
    ];
    $data[] = [
      ['label' => 'Test'],
      [
        'id' => 'test',
        'label' => 'Test',
        'uuid' => '',
        'weight' => 0,
      ],
    ];
    $data[] = [
      ['id' => 'foo'],
      [
        'id' => 'test',
        'label' => '',
        'uuid' => '',
        'weight' => 0,
      ],
    ];
    return $data;
  }

  /**
   * Tests the access() method.
   */
  public function testAccess(): void {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertTrue($display_variant->access());
  }

  /**
   * Tests the submitConfigurationForm() method.
   */
  public function testSubmitConfigurationForm(): void {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertSame('', $display_variant->label());

    $form = [];
    $label = $this->randomMachineName();
    $form_state = new FormState();
    $form_state->setValue('label', $label);
    $display_variant->submitConfigurationForm($form, $form_state);
    $this->assertSame($label, $display_variant->label());
  }

}
