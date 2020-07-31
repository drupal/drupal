<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\Section
 * @group layout_builder
 */
class SectionTest extends UnitTestCase {

  /**
   * The section object to test.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $section;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->section = new Section(
      'layout_onecol',
      [],
      [
        new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']),
        (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
        (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
      ],
      [
        'bad_judgement' => ['blink_speed' => 'fast', 'spin_direction' => 'clockwise'],
        'hunt_and_peck' => ['delay' => '300ms'],
      ]
    );
  }

  /**
   * @covers ::__construct
   * @covers ::setComponent
   * @covers ::getComponents
   */
  public function testGetComponents() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
      'first-uuid' => (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
    ];

    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::getComponent
   */
  public function testGetComponentInvalidUuid() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid UUID "invalid-uuid"');
    $this->section->getComponent('invalid-uuid');
  }

  /**
   * @covers ::getComponent
   */
  public function testGetComponent() {
    $expected = new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']);

    $this->assertEquals($expected, $this->section->getComponent('existing-uuid'));
  }

  /**
   * @covers ::removeComponent
   * @covers ::getComponentsByRegion
   */
  public function testRemoveComponent() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
    ];

    $this->section->removeComponent('first-uuid');
    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::appendComponent
   * @covers ::getNextHighestWeight
   * @covers ::getComponentsByRegion
   */
  public function testAppendComponent() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
      'first-uuid' => (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
      'new-uuid' => (new SectionComponent('new-uuid', 'some-region', []))->setWeight(1),
    ];

    $this->section->appendComponent(new SectionComponent('new-uuid', 'some-region'));
    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::insertAfterComponent
   */
  public function testInsertAfterComponent() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(4),
      'first-uuid' => (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
      'new-uuid' => (new SectionComponent('new-uuid', 'ordered-region', []))->setWeight(3),
    ];

    $this->section->insertAfterComponent('first-uuid', new SectionComponent('new-uuid', 'ordered-region'));
    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::insertAfterComponent
   */
  public function testInsertAfterComponentValidUuidRegionMismatch() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid preceding UUID "existing-uuid"');
    $this->section->insertAfterComponent('existing-uuid', new SectionComponent('new-uuid', 'ordered-region'));
  }

  /**
   * @covers ::insertAfterComponent
   */
  public function testInsertAfterComponentInvalidUuid() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid preceding UUID "invalid-uuid"');
    $this->section->insertAfterComponent('invalid-uuid', new SectionComponent('new-uuid', 'ordered-region'));
  }

  /**
   * @covers ::insertComponent
   * @covers ::getComponentsByRegion
   */
  public function testInsertComponent() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(4),
      'first-uuid' => (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(3),
      'new-uuid' => (new SectionComponent('new-uuid', 'ordered-region', []))->setWeight(2),
    ];

    $this->section->insertComponent(0, new SectionComponent('new-uuid', 'ordered-region'));
    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::insertComponent
   */
  public function testInsertComponentAppend() {
    $expected = [
      'existing-uuid' => (new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']))->setWeight(0),
      'second-uuid' => (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
      'first-uuid' => (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
      'new-uuid' => (new SectionComponent('new-uuid', 'ordered-region', []))->setWeight(4),
    ];

    $this->section->insertComponent(2, new SectionComponent('new-uuid', 'ordered-region'));
    $this->assertComponents($expected, $this->section);
  }

  /**
   * @covers ::insertComponent
   */
  public function testInsertComponentInvalidDelta() {
    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('Invalid delta "7" for the "new-uuid" component');
    $this->section->insertComponent(7, new SectionComponent('new-uuid', 'ordered-region'));
  }

  /**
   * Asserts that the section has the expected components.
   *
   * @param \Drupal\layout_builder\SectionComponent[] $expected
   *   The expected sections.
   * @param \Drupal\layout_builder\Section $section
   *   The section storage to check.
   */
  protected function assertComponents(array $expected, Section $section) {
    $result = $section->getComponents();
    $this->assertEquals($expected, $result);
    $this->assertSame(array_keys($expected), array_keys($result));
  }

  /**
   * @covers ::getThirdPartySettings
   * @dataProvider providerTestGetThirdPartySettings
   */
  public function testGetThirdPartySettings($provider, $expected) {
    $this->assertSame($expected, $this->section->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testGetThirdPartySettings().
   */
  public function providerTestGetThirdPartySettings() {
    $data = [];
    $data[] = [
      'bad_judgement',
      ['blink_speed' => 'fast', 'spin_direction' => 'clockwise'],
    ];
    $data[] = [
      'hunt_and_peck',
      ['delay' => '300ms'],
    ];
    $data[] = [
      'non_existing_provider',
      [],
    ];
    return $data;
  }

  /**
   * @covers ::getThirdPartySetting
   * @dataProvider providerTestGetThirdPartySetting
   */
  public function testGetThirdPartySetting($provider, $key, $expected, $default = FALSE) {
    if ($default) {
      $this->assertSame($expected, $this->section->getThirdPartySetting($provider, $key, $default));
    }
    else {
      $this->assertSame($expected, $this->section->getThirdPartySetting($provider, $key));
    }
  }

  /**
   * Provides test data for ::testGetThirdPartySetting().
   */
  public function providerTestGetThirdPartySetting() {
    $data = [];
    $data[] = [
      'bad_judgement',
      'blink_speed',
      'fast',
    ];
    $data[] = [
      'hunt_and_peck',
      'delay',
      '300ms',
    ];
    $data[] = [
      'hunt_and_peck',
      'non_existing_key',
      NULL,
    ];
    $data[] = [
      'non_existing_provider',
      'non_existing_key',
      NULL,
    ];
    $data[] = [
      'non_existing_provider',
      'non_existing_key',
      'default value',
      'default value',
    ];
    return $data;
  }

  /**
   * @covers ::setThirdPartySetting
   * @dataProvider providerTestSetThirdPartySetting
   */
  public function testSetThirdPartySetting($provider, $key, $value, $expected) {
    $this->section->setThirdPartySetting($provider, $key, $value);
    $this->assertSame($expected, $this->section->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testSetThirdPartySettings().
   */
  public function providerTestSetThirdPartySetting() {
    $data = [];
    $data[] = [
      'bad_judgement',
      'blink_speed',
      'super fast',
      [
        'blink_speed' => 'super fast',
        'spin_direction' => 'clockwise',
      ],
    ];
    $data[] = [
      'bad_judgement',
      'new_setting',
      'new_value',
      [
        'blink_speed' => 'fast',
        'spin_direction' => 'clockwise',
        'new_setting' => 'new_value',
      ],
    ];
    $data[] = [
      'new_provider',
      'new_setting',
      'new_value',
      [
        'new_setting' => 'new_value',
      ],
    ];
    return $data;
  }

  /**
   * @covers ::unsetThirdPartySetting
   * @dataProvider providerTestUnsetThirdPartySetting
   */
  public function testUnsetThirdPartySetting($provider, $key, $expected) {
    $this->section->unsetThirdPartySetting($provider, $key);
    $this->assertSame($expected, $this->section->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testUnsetThirdPartySetting().
   */
  public function providerTestUnsetThirdPartySetting() {
    $data = [];
    $data['Key with values'] = [
      'bad_judgement',
      'blink_speed',
      [
        'spin_direction' => 'clockwise',
      ],
    ];
    $data['Key without values'] = [
      'hunt_and_peck',
      'delay',
      [],
    ];
    $data['Non-existing key'] = [
      'bad_judgement',
      'non_existing_key',
      [
        'blink_speed' => 'fast',
        'spin_direction' => 'clockwise',
      ],
    ];
    $data['Non-existing provider'] = [
      'non_existing_provider',
      'non_existing_key',
      [],
    ];

    return $data;
  }

  /**
   * @covers ::getThirdPartyProviders
   */
  public function testGetThirdPartyProviders() {
    $this->assertSame(['bad_judgement', 'hunt_and_peck'], $this->section->getThirdPartyProviders());
    $this->section->unsetThirdPartySetting('hunt_and_peck', 'delay');
    $this->assertSame(['bad_judgement'], $this->section->getThirdPartyProviders());
  }

}
