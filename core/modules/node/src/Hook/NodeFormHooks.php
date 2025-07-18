<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Form hook implementations for the node module.
 */
class NodeFormHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Alters the theme form to use the admin theme on node editing.
   *
   * @see self::systemThemesAdminFormSubmit()
   */
  #[Hook('form_system_themes_admin_form_alter')]
  public function formSystemThemesAdminFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $form['admin_theme']['use_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the administration theme when editing or creating content'),
      '#description' => $this->t('Control which roles can "View the administration theme" on the <a href=":permissions">Permissions page</a>.', [
        ':permissions' => Url::fromRoute('user.admin_permissions.module', [
          'modules' => 'system',
        ])->toString(),
      ]),
      '#default_value' => $this->configFactory->getEditable('node.settings')->get('use_admin_theme'),
    ];
    $form['#submit'][] = [self::class, 'systemThemesAdminFormSubmit'];
  }

  /**
   * Form submission handler for system_themes_admin_form().
   *
   * @see self::formSystemThemesAdminFormAlter
   */
  public static function systemThemesAdminFormSubmit($form, FormStateInterface $form_state): void {
    \Drupal::configFactory()->getEditable('node.settings')
      ->set('use_admin_theme', $form_state->getValue('use_admin_theme'))
      ->save();
  }

}
