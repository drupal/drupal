<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\display_extender;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplayExtender;

/**
 * Defines another display extender test plugin.
 */
#[ViewsDisplayExtender(
    id: 'display_extender_test_2',
    title: new TranslatableMarkup('Display extender test number two'),
)]
class DisplayExtenderTest2 extends DisplayExtenderTest {

}
