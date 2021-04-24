<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

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
    // Loop through each section and reconstruct it to ensure that all default
    // values are present.
    foreach ($sections as $section) {
      $this->sections[] = Section::fromArray($section->toArray());
    }
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
