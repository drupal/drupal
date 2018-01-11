<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides a base class for testing implementations of section storage.
 */
abstract class SectionStorageTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'layout_discovery',
    'layout_test',
    'user',
    'entity_test',
  ];

  /**
   * The section storage implementation.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $section_data = [
      new Section('layout_test_plugin', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content'),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content'),
      ]),
    ];
    $this->sectionStorage = $this->getSectionStorage($section_data);
  }

  /**
   * Sets up the section storage entity.
   *
   * @param array $section_data
   *   An array of section data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  abstract protected function getSectionStorage(array $section_data);

  /**
   * @covers ::getSections
   */
  public function testGetSections() {
    $expected = [
      new Section('layout_test_plugin', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content'),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content'),
      ]),
    ];
    $this->assertSections($expected);
  }

  /**
   * @covers ::getSection
   */
  public function testGetSection() {
    $this->assertInstanceOf(Section::class, $this->sectionStorage->getSection(0));
  }

  /**
   * @covers ::getSection
   */
  public function testGetSectionInvalidDelta() {
    $this->setExpectedException(\OutOfBoundsException::class, 'Invalid delta "2" for the "The test entity"');
    $this->sectionStorage->getSection(2);
  }

  /**
   * @covers ::insertSection
   */
  public function testInsertSection() {
    $expected = [
      new Section('layout_test_plugin', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content'),
      ]),
      new Section('setting_1'),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content'),
      ]),
    ];

    $this->sectionStorage->insertSection(1, new Section('setting_1'));
    $this->assertSections($expected);
  }

  /**
   * @covers ::appendSection
   */
  public function testAppendSection() {
    $expected = [
      new Section('layout_test_plugin', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content'),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content'),
      ]),
      new Section('foo'),
    ];

    $this->sectionStorage->appendSection(new Section('foo'));
    $this->assertSections($expected);
  }

  /**
   * @covers ::removeSection
   */
  public function testRemoveSection() {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content'),
      ]),
    ];

    $this->sectionStorage->removeSection(0);
    $this->assertSections($expected);
  }

  /**
   * Asserts that the field list has the expected sections.
   *
   * @param \Drupal\layout_builder\Section[] $expected
   *   The expected sections.
   */
  protected function assertSections(array $expected) {
    $result = $this->sectionStorage->getSections();
    $this->assertEquals($expected, $result);
    $this->assertSame(array_keys($expected), array_keys($result));
  }

}
