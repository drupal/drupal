<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionListTrait;

/**
 * @coversDefaultClass \Drupal\layout_builder\SectionListTrait
 *
 * @group layout_builder
 */
class SectionListTraitTest extends SectionListTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
    return new TestSectionList($section_data);
  }

  /**
   * @covers ::addBlankSection
   */
  public function testAddBlankSection(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('A blank section must only be added to an empty list');
    $this->sectionList->addBlankSection();
  }

}

class TestSectionList implements SectionListInterface {

  use SectionListTrait {
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
    // Loop through each section and reconstruct it to ensure that all default
    // values are present.
    foreach ($sections as $section) {
      $this->sections[] = Section::fromArray($section->toArray());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections): array {
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
