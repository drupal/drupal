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

    $this->section = new Section('layout_onecol', [], [
      new SectionComponent('existing-uuid', 'some-region', ['id' => 'existing-block-id']),
      (new SectionComponent('second-uuid', 'ordered-region', ['id' => 'second-block-id']))->setWeight(3),
      (new SectionComponent('first-uuid', 'ordered-region', ['id' => 'first-block-id']))->setWeight(2),
    ]);
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
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid UUID "invalid-uuid"');
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
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid preceding UUID "existing-uuid"');
    $this->section->insertAfterComponent('existing-uuid', new SectionComponent('new-uuid', 'ordered-region'));
  }

  /**
   * @covers ::insertAfterComponent
   */
  public function testInsertAfterComponentInvalidUuid() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid preceding UUID "invalid-uuid"');
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
    $this->setExpectedException(\OutOfBoundsException::class, 'Invalid delta "7" for the "new-uuid" component');
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

}
