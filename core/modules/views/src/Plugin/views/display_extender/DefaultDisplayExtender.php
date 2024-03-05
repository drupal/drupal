<?php

namespace Drupal\views\Plugin\views\display_extender;

use Drupal\views\Attribute\ViewsDisplayExtender;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Default display extender plugin; does nothing.
 *
 * @ingroup views_display_extender_plugins
 */
#[ViewsDisplayExtender(
    id: 'default',
    title: new TranslatableMarkup('Empty display extender'),
    help: new TranslatableMarkup('Default settings for this view.'),
    no_ui: TRUE
)]
class DefaultDisplayExtender extends DisplayExtenderPluginBase {

}
