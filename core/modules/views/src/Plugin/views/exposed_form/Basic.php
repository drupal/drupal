<?php

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsExposedForm;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 */
#[ViewsExposedForm(
  id: 'basic',
  title: new TranslatableMarkup('Basic'),
  help: new TranslatableMarkup('Basic exposed form')
)]
class Basic extends ExposedFormPluginBase {

}
