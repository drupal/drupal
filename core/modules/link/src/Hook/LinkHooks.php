<?php

namespace Drupal\link\Hook;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for link.
 */
class LinkHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.link':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Link module allows you to create fields that contain internal or external URLs and optional link text. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":link_documentation">online documentation for the Link module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':link_documentation' => 'https://www.drupal.org/documentation/modules/link',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Managing and displaying link fields') . '</dt>';
        $output .= '<dd>' . $this->t('The <em>settings</em> and the <em>display</em> of the link field can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Setting the allowed link type') . '</dt>';
        $output .= '<dd>' . $this->t('In the field settings you can define the allowed link type to be <em>internal links only</em>, <em>external links only</em>, or <em>both internal and external links</em>. <em>Internal links only</em> and <em>both internal and external links</em> options enable an autocomplete widget for internal links, so a user does not have to copy or remember a URL.') . '</dd>';
        $output .= '<dt>' . $this->t('Adding link text') . '</dt>';
        $output .= '<dd>' . $this->t('In the field settings you can define additional link text to be <em>optional</em> or <em>required</em> in any link field.') . '</dd>';
        $output .= '<dt>' . $this->t('Displaying link text') . '</dt>';
        $output .= '<dd>' . $this->t('If link text has been submitted for a URL, then by default this link text is displayed as a link to the URL. If you want to display both the link text <em>and</em> the URL, choose the appropriate link format from the drop-down menu in the <em>Manage display</em> page. If you only want to display the URL even if link text has been submitted, choose <em>Link</em> as the format, and then change its <em>Format settings</em> to display <em>URL only</em>.') . '</dd>';
        $output .= '<dt>' . $this->t('Adding attributes to links') . '</dt>';
        $output .= '<dd>' . $this->t('You can add attributes to links, by changing the <em>Format settings</em> in the <em>Manage display</em> page. Adding <em>rel="nofollow"</em> notifies search engines that links should not be followed.') . '</dd>';
        $output .= '<dt>' . $this->t('Validating URLs') . '</dt>';
        $output .= '<dd>' . $this->t('All links are validated after a link field is filled in. They can include anchors or query strings.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'link_formatter_link_separate' => [
        'variables' => [
          'title' => NULL,
          'url_title' => NULL,
          'url' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_field_type_category_info_alter().
   */
  #[Hook('field_type_category_info_alter')]
  public function fieldTypeCategoryInfoAlter(&$definitions): void {
    // The `link` field type belongs in the `general` category, so the libraries
    // need to be attached using an alter hook.
    $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'][] = 'link/drupal.link-icon';
  }

}
