<?php

declare(strict_types=1);

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;

/**
 * Configure layout builder settings for this site.
 *
 * @internal
 */
final class LayoutBuilderSettingsForm extends ConfigFormBase {

  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'layout_builder_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['expose_all_field_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose all fields as blocks to layout builder'),
      '#description' => $this->t('When enabled, this setting exposes all fields for all entity view displays.<br/> When disabled, only entity type bundles that have layout builder enabled will have their fields exposed.<br/> Enabling this setting could <strong>significantly decrease performance</strong> on sites with a large number of entity types and bundles.'),
      '#config_target' => 'layout_builder.settings:expose_all_field_blocks',
    ];
    return parent::buildForm($form, $form_state);
  }

}
