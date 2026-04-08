<?php

declare(strict_types=1);

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigDependencyDeleteFormTrait;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * Builds a confirmation form to uninstall a theme.
 *
 * @internal
 */
class ThemeUninstallConfirmForm extends ConfirmFormBase {

  use AutowireTrait;
  use ConfigDependencyDeleteFormTrait;

  /**
   * The theme label.
   */
  protected string $themeLabel = '';

  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
    protected ThemeInstallerInterface $themeInstaller,
    protected ConfigManagerInterface $configManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    if ($this->themeLabel) {
      return $this->t('Uninstall %theme theme', ['%theme' => $this->themeLabel]);
    }
    return $this->t('Uninstall theme');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('system.themes_page');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Would you like to continue with uninstalling the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'system_theme_uninstall_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, #[MapQueryParameter] string $theme = ''): RedirectResponse|array {
    if (empty($theme)) {
      throw new AccessDeniedHttpException();
    }

    // Get current list of themes.
    $themes = $this->themeHandler->listInfo();
    if (empty($themes[$theme])) {
      $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      return new RedirectResponse($this->getCancelUrl()->toString());
    }

    $this->themeLabel = $themes[$theme]->info['name'];
    $config = $this->config('system.theme');
    if ($theme === $config->get('default')) {
      $this->messenger()->addError($this->t('%theme is the default theme and cannot be uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
      return new RedirectResponse($this->getCancelUrl()->toString());
    }

    if ($theme === $config->get('admin')) {
      $this->messenger()->addError($this->t('%theme is the admin theme and cannot be uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
      return new RedirectResponse($this->getCancelUrl()->toString());
    }

    $theme_info = $themes[$theme];
    $dependent_themes = [];
    if (!empty($theme_info->sub_themes)) {
      foreach ($theme_info->sub_themes as $sub_theme => $sub_label) {
        if (!empty($themes[$sub_theme]->status)) {
          $dependent_themes[] = $sub_label;
        }
      }
    }

    if (!empty($dependent_themes)) {
      $this->messenger()->addError($this->t('%theme cannot be uninstalled because the following themes depend on it: %themes', [
        '%theme' => $theme_info->info['name'],
        '%themes' => implode(', ', $dependent_themes),
      ]));
      return new RedirectResponse($this->getCancelUrl()->toString());
    }

    $form['text']['#markup'] = '<p>' . $this->t('The <em>%theme</em> theme will be completely uninstalled from your site, and all data from this theme will be lost!', ['%theme' => $theme_info->info['name']]) . '</p>';

    // List the dependent entities.
    $this->addDependencyListsToForm($form, 'theme', [$theme], $this->configManager, $this->entityTypeManager);

    $form['theme'] = [
      '#type' => 'value',
      '#value' => $theme,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $themes = $this->themeHandler->listInfo();
    $theme = $form_state->getValue('theme');
    $this->themeInstaller->uninstall([$form_state->getValue('theme')]);
    $this->messenger()->addStatus($this->t('The %theme theme has been uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
