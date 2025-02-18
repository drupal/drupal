<?php

namespace Drupal\settings_tray\Hook;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\settings_tray\Block\BlockEntitySettingTrayForm;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for settings_tray.
 */
class SettingsTrayHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?array {
    switch ($route_name) {
      case 'help.page.settings_tray':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Settings Tray module allows users with the <a href=":administer_block_permission">Administer blocks</a> and <a href=":contextual_permission">Use contextual links</a> permissions to edit blocks without visiting a separate page. For more information, see the <a href=":handbook_url">online documentation for the Settings Tray module</a>.', [
          ':handbook_url' => 'https://www.drupal.org/documentation/modules/settings_tray',
          ':administer_block_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'block',
          ])->toString(),
          ':contextual_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'contextual',
          ])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Editing blocks in place') . '</dt>';
        $output .= '<dd>';
        $output .= '<p>' . $this->t('To edit blocks in place, either click the <strong>Edit</strong> button in the toolbar and then click on the block, or choose "Quick edit" from the block\'s contextual link. (See the <a href=":contextual">Contextual Links module help</a> for more information about how to use contextual links.)', [
          ':contextual' => Url::fromRoute('help.page', [
            'name' => 'contextual',
          ])->toString(),
        ]) . '</p>';
        $output .= '<p>' . $this->t('The Settings Tray for the block will open in a sidebar, with a compact form for configuring what the block shows.') . '</p>';
        $output .= '<p>' . $this->t('Save the form and the changes will be immediately visible on the page.') . '</p>';
        $output .= '</dd>';
        $output .= '</dl>';
        return ['#markup' => $output];
    }
    return NULL;
  }

  /**
   * Implements hook_contextual_links_view_alter().
   *
   * Change Configure Blocks into off_canvas links.
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items): void {
    if (isset($element['#links']['settings-trayblock-configure'])) {
      // Place settings_tray link first.
      $settings_tray_link = $element['#links']['settings-trayblock-configure'];
      unset($element['#links']['settings-trayblock-configure']);
      $element['#links'] = ['settings-trayblock-configure' => $settings_tray_link] + $element['#links'];
      // If this is content block change title to avoid duplicate "Quick Edit".
      if (isset($element['#links']['block-contentblock-edit'])) {
        $element['#links']['settings-trayblock-configure']['title'] = $this->t('Quick edit settings');
      }
      $element['#attached']['library'][] = 'core/drupal.dialog.off_canvas';
    }
  }

  /**
   * Implements hook_block_view_alter().
   */
  #[Hook('block_view_alter')]
  public function blockViewAlter(array &$build): void {
    if (isset($build['#contextual_links']['block'])) {
      // Ensure that contextual links vary by whether the block has config
      // overrides or not.
      // @see _contextual_links_to_id()
      $build['#contextual_links']['block']['metadata']['has_overrides'] = _settings_tray_has_block_overrides($build['#block']) ? 1 : 0;
    }
    // Force a new 'data-contextual-id' attribute on blocks when this module is
    // enabled so as not to reuse stale data cached client-side.
    // @todo Remove when https://www.drupal.org/node/2773591 is fixed.
    $build['#contextual_links']['settings_tray'] = ['route_parameters' => []];
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['block']->setFormClass('settings_tray', BlockEntitySettingTrayForm::class)->setLinkTemplate('settings_tray-form', '/admin/structure/block/manage/{block}/settings-tray');
  }

  /**
   * Implements hook_toolbar_alter().
   *
   * Alters the 'contextual' toolbar tab if it exists (meaning the user is
   * allowed to use contextual links) and if they can administer blocks.
   *
   * @todo Remove the "administer blocks" requirement in
   *   https://www.drupal.org/node/2822965.
   *
   * @see contextual_toolbar()
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(&$items): void {
    $items['contextual']['#cache']['contexts'][] = 'user.permissions';
    if (isset($items['contextual']['tab']) && \Drupal::currentUser()->hasPermission('administer blocks')) {
      $items['contextual']['#weight'] = -1000;
      $items['contextual']['#attached']['library'][] = 'settings_tray/drupal.settings_tray';
      $items['contextual']['tab']['#attributes']['data-drupal-settingstray'] = 'toggle';
      // Set a class on items to mark whether they should be active in edit
      // mode.
      // @todo Create a dynamic method for modules to set their own items.
      //   https://www.drupal.org/node/2784589.
      $edit_mode_items = ['contextual'];
      foreach ($items as $key => $item) {
        if (!in_array($key, $edit_mode_items) && (!isset($items[$key]['#wrapper_attributes']['class']) || !in_array('hidden', $items[$key]['#wrapper_attributes']['class']))) {
          $items[$key]['#wrapper_attributes']['class'][] = 'edit-mode-inactive';
        }
      }
    }
  }

  /**
   * Implements hook_block_alter().
   *
   * Ensures every block plugin definition has an 'settings_tray' form
   * specified.
   *
   * @see \Drupal\settings_tray\Access\BlockPluginHasSettingsTrayFormAccessCheck
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    foreach ($definitions as &$definition) {
      // If a block plugin does not define its own 'settings_tray' form, use the
      // plugin class itself.
      if (!isset($definition['forms']['settings_tray'])) {
        $definition['forms']['settings_tray'] = $definition['class'];
      }
    }
  }

  /**
   * Implements hook_css_alter().
   */
  #[Hook('css_alter')]
  public function cssAlter(&$css, AttachedAssetsInterface $assets, LanguageInterface $language): void {
    // @todo Remove once conditional ordering is introduced in
    //   https://www.drupal.org/node/1945262.
    $path = \Drupal::service('extension.list.module')->getPath('settings_tray') . '/css/settings_tray.theme.css';
    if (isset($css[$path])) {
      // Use 200 to come after CSS_AGGREGATE_THEME.
      $css[$path]['group'] = 200;
    }
  }

}
