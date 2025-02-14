<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a confirmation form for enabling experimental themes.
 *
 * @internal
 */
class ThemeExperimentalConfirmForm extends ConfirmFormBase {

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * Constructs a ThemeExperimentalConfirmForm object.
   *
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   *   The theme installer.
   */
  public function __construct(ThemeExtensionList $theme_list, ThemeInstallerInterface $theme_installer) {
    $this->themeList = $theme_list;
    $this->themeInstaller = $theme_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.theme'),
      $container->get('theme_installer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you wish to install an experimental theme?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.themes_page');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Would you like to continue with the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_themes_experimental_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $theme = $form_state->getBuildInfo()['args'][0] ?: NULL;
    $all_themes = $this->themeList->getList();
    if (!isset($all_themes[$theme])) {
      return $this->redirect('system.themes_page');
    }
    $this->messenger()->addWarning($this->t('Experimental themes are provided for testing purposes only. Use at your own risk.'));

    $dependencies = array_keys($all_themes[$theme]->requires);
    $themes = array_merge([$theme], $dependencies);
    $is_experimental = function ($theme) use ($all_themes) {
      return isset($all_themes[$theme]) && $all_themes[$theme]->isExperimental();
    };
    $get_label = function ($theme) use ($all_themes) {
      return $all_themes[$theme]->info['name'];
    };

    $items = [];
    if (!empty($dependencies)) {
      // Display a list of required themes that have to be installed as well.
      $items[] = $this->formatPlural(count($dependencies), 'You must install the @required theme to install @theme.', 'You must install the @required themes to install @theme.', [
        '@theme' => $get_label($theme),
        // It is safe to implode this because theme names are not translated
        // markup and so will not be double-escaped.
        '@required' => implode(', ', array_map($get_label, $dependencies)),
      ]);
    }
    // Add the list of experimental themes after any other messages.
    $items[] = $this->t('The following themes are experimental: @themes', ['@themes' => implode(', ', array_map($get_label, array_filter($themes, $is_experimental)))]);
    $form['message'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];
    $theme = $args[0] ?? NULL;
    $set_default = $args[1] ?? FALSE;
    $themes = $this->themeList->getList();
    $config = $this->configFactory()->getEditable('system.theme');
    try {
      if ($this->themeInstaller->install([$theme])) {
        if ($set_default) {
          // Set the default theme.
          $config->set('default', $theme)->save();

          // The status message depends on whether an admin theme is currently
          // in use: an empty string means the admin theme is set to be the
          // default theme.
          $admin_theme = $config->get('admin');
          if (!empty($admin_theme) && $admin_theme !== $theme) {
            $this->messenger()
              ->addStatus($this->t('Note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', [
                '%admin_theme' => $themes[$admin_theme]->info['name'],
                '%selected_theme' => $themes[$theme]->info['name'],
              ]));
          }
          else {
            $this->messenger()->addStatus($this->t('%theme is now the default theme.', ['%theme' => $themes[$theme]->info['name']]));
          }
        }
        else {
          $this->messenger()->addStatus($this->t('The %theme theme has been installed.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }
    }
    catch (PreExistingConfigException $e) {
      $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
      $this->messenger()->addError(
        $this->formatPlural(
          count($config_objects),
          'Unable to install @extension, %config_names already exists in active configuration.',
          'Unable to install @extension, %config_names already exist in active configuration.',
          [
            '%config_names' => implode(', ', $config_objects),
            '@extension' => $theme,
          ])
      );
    }
    catch (UnmetDependenciesException $e) {
      $this->messenger()->addError($e->getTranslatedMessage($this->getStringTranslation(), $theme));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
