<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

/**
 * @coversDefaultClass \Drupal\layout_builder\SectionStorage\SectionStorageTrait
 *
 * @group layout_builder
 */
class SectionListTraitTest extends SectionStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionStorage(array $section_data) {
    return new TestSectionList($section_data);
  }

  /**
   * @covers ::addBlankSection
   */
  public function testAddBlankSection() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('A blank section must only be added to an empty list');
    $this->sectionStorage->addBlankSection();
  }

}

class TestSectionList implements SectionListInterface {

  use SectionStorageTrait {
    addBlankSection as public;
  }

  /**
   * An array of sections.
   *
   * @var \Drupal\layout_builder\Section[]
   */
  protected $sections;

  /**
   * TestSectionList constructor.
   */
  public function __construct(array $sections) {
    $this->setSections($sections);
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections) {
    $this->sections = array_values($sections);
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    return $this->sections;
  }

}
