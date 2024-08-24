<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\display_extender;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplayExtender;

/**
 * Defines the third display extender test plugin.
 */
#[ViewsDisplayExtender(
  id: 'display_extender_test_3',
  title: new TranslatableMarkup('Display extender test number three'),
)]
class DisplayExtenderTest3 extends DisplayExtenderTest {

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return ['Display extender test error.'];
  }

}
