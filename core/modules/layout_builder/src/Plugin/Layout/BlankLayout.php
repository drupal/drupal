<?php

namespace Drupal\layout_builder\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a layout plugin that produces no output.
 *
 * @see \Drupal\layout_builder\Field\LayoutSectionItemList::removeSection()
 * @see \Drupal\layout_builder\SectionListTrait::addBlankSection()
 * @see \Drupal\layout_builder\SectionListTrait::hasBlankSection()
 *
 * @internal
 *   This layout plugin is intended for internal use by Layout Builder only.
 */
#[Layout(
  id: 'layout_builder_blank',
  label: new TranslatableMarkup('Blank Layout'),
  category: new TranslatableMarkup('Blank Layout'),
)]
class BlankLayout extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    // Return no output.
    return [];
  }

}
