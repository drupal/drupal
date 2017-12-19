<?php

namespace Drupal\layout_builder\Form;

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class AddBlockForm extends ConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_add_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Add Block');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitBlock(Section $section, $region, $uuid, array $configuration) {
    $section->appendComponent(new SectionComponent($uuid, $region, $configuration));
  }

}
