<?php

namespace Drupal\views\Plugin\views\wizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsWizard;
use Drupal\views\Plugin\Derivative\DefaultWizardDeriver;

/**
 * Standard Views wizard plugin.
 *
 * @ingroup views_wizard_plugins
 */
#[ViewsWizard(
  id: 'standard',
  title: new TranslatableMarkup('Default wizard'),
  deriver: DefaultWizardDeriver::class,
)]
class Standard extends WizardPluginBase {

}
