<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides a base class for testing implementations of a section list.
 *
 * @coversDefaultClass \Drupal\layout_builder\Plugin\SectionStorage\SectionStorageBase
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
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        '20000000-0000-1000-a000-000000000000' => new SectionComponent('20000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
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
  public function testGetSections(): void {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        '20000000-0000-1000-a000-000000000000' => new SectionComponent('20000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
    ];
    $this->assertSections($expected);
  }

  /**
   * @covers ::getSection
   */
  public function testGetSection(): void {
    $this->assertInstanceOf(Section::class, $this->sectionList->getSection(0));
  }

  /**
   * @covers ::getSection
   */
  public function testGetSectionInvalidDelta(): void {
    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('Invalid delta "2"');
    $this->sectionList->getSection(2);
  }

  /**
   * @covers ::insertSection
   */
  public function testInsertSection(): void {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_onecol'),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        '20000000-0000-1000-a000-000000000000' => new SectionComponent('20000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
    ];

    $this->sectionList->insertSection(1, new Section('layout_onecol'));
    $this->assertSections($expected);
  }

  /**
   * @covers ::appendSection
   */
  public function testAppendSection(): void {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'Default'], [
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        '20000000-0000-1000-a000-000000000000' => new SectionComponent('20000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
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
  public function testRemoveAllSections($set_blank, $expected): void {
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
  public static function providerTestRemoveAllSections() {
    $data = [];
    $data[] = [NULL, []];
    $data[] = [FALSE, []];
    $data[] = [TRUE, [new Section('layout_builder_blank')]];
    return $data;
  }

  /**
   * @covers ::removeSection
   */
  public function testRemoveSection(): void {
    $expected = [
      new Section('layout_test_plugin', ['setting_1' => 'bar'], [
        '20000000-0000-1000-a000-000000000000' => new SectionComponent('20000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
    ];

    $this->sectionList->removeSection(0);
    $this->assertSections($expected);
  }

  /**
   * @covers ::removeSection
   */
  public function testRemoveMultipleSections(): void {
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
  public function testClone(): void {
    $this->assertSame(['setting_1' => 'Default'], $this->sectionList->getSection(0)->getLayoutSettings());

    $new_section_storage = clone $this->sectionList;
    $new_section_storage->getSection(0)->setLayoutSettings(['asdf' => 'foo']);
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
