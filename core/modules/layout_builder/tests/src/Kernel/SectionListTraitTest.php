<?php

namespace Drupal\Tests\layout_builder\Kernel;

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
