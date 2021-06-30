<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides a base class for testing implementations of a section list.
 */
abstract class SectionListTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_discovery',
    'layout_test',
  ];

  /**
   * The section list implementation.
   *
   * @var \Drupal\layout_builder\SectionListInterface
   */
  protected $sectionList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $section_data = [
      new Section('layout_test_plugin', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content', ['id' => 'foo']),
      ]),
    ];
    $this->sectionList = $this->getSectionList($section_data);
  }

  /**
   * Sets up the section list.
   *
   * @param array $section_data
   *   An array of section data.
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   */
  abstract protected function getSectionList(array $section_data);

  /**
   * Tests ::getSections().
   */
  public function testGetSections() {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content', ['id' => 'foo']),
      ]),
    ];
    $this->assertSections($expected);
  }

  /**
   * @covers ::getSection
   */
  public function testGetSection() {
    $this->assertInstanceOf(Section::class, $this->sectionList->getSection(0));
  }

  /**
   * @covers ::getSection
   */
  public function testGetSectionInvalidDelta() {
    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('Invalid delta "2"');
    $this->sectionList->getSection(2);
  }

  /**
   * @covers ::insertSection
   */
  public function testInsertSection() {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_onecol'),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content', ['id' => 'foo']),
      ]),
    ];

    $this->sectionList->insertSection(1, new Section('layout_onecol'));
    $this->assertSections($expected);
  }

  /**
   * @covers ::appendSection
   */
  public function testAppendSection() {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_onecol'),
    ];

    $this->sectionList->appendSection(new Section('layout_onecol'));
    $this->assertSections($expected);
  }

  /**
   * @covers ::removeAllSections
   *
   * @dataProvider providerTestRemoveAllSections
   */
  public function testRemoveAllSections($set_blank, $expected) {
    if ($set_blank === NULL) {
      $this->sectionList->removeAllSections();
    }
    else {
      $this->sectionList->removeAllSections($set_blank);
    }
    $this->assertSections($expected);
  }

  /**
   * Provides test data for ::testRemoveAllSections().
   */
  public function providerTestRemoveAllSections() {
    $data = [];
    $data[] = [NULL, []];
    $data[] = [FALSE, []];
    $data[] = [TRUE, [new Section('layout_builder_blank')]];
    return $data;
  }

  /**
   * @covers ::removeSection
   */
  public function testRemoveSection() {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        'second-uuid' => new SectionComponent('second-uuid', 'content', ['id' => 'foo']),
      ]),
    ];

    $this->sectionList->removeSection(0);
    $this->assertSections($expected);
  }

  /**
   * @covers ::removeSection
   */
  public function testRemoveMultipleSections() {
    $expected = [
      new Section('layout_builder_blank'),
    ];

    $this->sectionList->removeSection(0);
    $this->sectionList->removeSection(0);
    $this->assertSections($expected);
  }

  /**
   * Tests __clone().
   */
  public function testClone() {
    $this->assertSame(['setting_1' => 'Default'], $this->sectionList->getSection(0)->getLayoutSettings());

    $new_section_storage = clone $this->sectionList;
    $new_section_storage->getSection(0)->setLayoutSettings(['asdf' => 'qwer']);
    $this->assertSame(['setting_1' => 'Default'], $this->sectionList->getSection(0)->getLayoutSettings());
  }

  /**
   * Asserts that the field list has the expected sections.
   *
   * @param \Drupal\layout_builder\Section[] $expected
   *   The expected sections.
   */
  protected function assertSections(array $expected) {
    $result = $this->sectionList->getSections();
    $this->assertEquals($expected, $result);
    $this->assertSame(array_keys($expected), array_keys($result));
  }

}
