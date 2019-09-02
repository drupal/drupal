<?php

namespace Drupal\Tests\Core\Display;

use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Display\VariantBase
 * @group Display
 */
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
  public function setUpDisplayVariant($configuration = [], $definition = []) {
    return $this->getMockBuilder('Drupal\Core\Display\VariantBase')
      ->setConstructorArgs([$configuration, 'test', $definition])
      ->setMethods(['build'])
      ->getMock();
  }

  /**
   * Tests the label() method.
   *
   * @covers ::label
   */
  public function testLabel() {
    $display_variant = $this->setUpDisplayVariant(['label' => 'foo']);
    $this->assertSame('foo', $display_variant->label());
  }

  /**
   * Tests the label() method using a default value.
   *
   * @covers ::label
   */
  public function testLabelDefault() {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertSame('', $display_variant->label());
  }

  /**
   * Tests the getWeight() method.
   *
   * @covers ::getWeight
   */
  public function testGetWeight() {
    $display_variant = $this->setUpDisplayVariant(['weight' => 5]);
    $this->assertSame(5, $display_variant->getWeight());
  }

  /**
   * Tests the getWeight() method using a default value.
   *
   * @covers ::getWeight
   */
  public function testGetWeightDefault() {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertSame(0, $display_variant->getWeight());
  }

  /**
   * Tests the getConfiguration() method.
   *
   * @covers ::getConfiguration
   *
   * @dataProvider providerTestGetConfiguration
   */
  public function testGetConfiguration($configuration, $expected) {
    $display_variant = $this->setUpDisplayVariant($configuration);

    $this->assertSame($expected, $display_variant->getConfiguration());
  }

  /**
   * Provides test data for testGetConfiguration().
   */
  public function providerTestGetConfiguration() {
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
   *
   * @covers ::access
   */
  public function testAccess() {
    $display_variant = $this->setUpDisplayVariant();
    $this->assertTrue($display_variant->access());
  }

  /**
   * Tests the submitConfigurationForm() method.
   *
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationForm() {
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
