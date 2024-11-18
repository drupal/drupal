<?php

namespace Drupal\filter\Hook;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for filter.
 */
class FilterHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.filter':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Filter module allows administrators to configure text formats. Text formats change how HTML tags and other text will be <em>processed and displayed</em> in the site. They are used to transform text, and also help to defend your website against potentially damaging input from malicious users. Visual text editors can be associated with text formats by using the <a href=":editor_help">Text Editor module</a>. For more information, see the <a href=":filter_do">online documentation for the Filter module</a>.', [
          ':filter_do' => 'https://www.drupal.org/documentation/modules/filter/',
          ':editor_help' => \Drupal::moduleHandler()->moduleExists('editor') ? Url::fromRoute('help.page', [
            'name' => 'editor',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing text formats') . '</dt>';
        $output .= '<dd>' . t('You can create and edit text formats on the <a href=":formats">Text formats page</a> (if the Text Editor module is installed, this page is named Text formats and editors). One text format is included by default: Plain text (which removes all HTML tags). Additional text formats may be created during installation. You can create a text format by clicking "<a href=":add_format">Add text format</a>".', [
          ':formats' => Url::fromRoute('filter.admin_overview')->toString(),
          ':add_format' => Url::fromRoute('filter.format_add')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Assigning roles to text formats') . '</dt>';
        $output .= '<dd>' . t('You can define which users will be able to use each text format by selecting roles. To ensure security, anonymous and untrusted users should only have access to text formats that restrict them to either plain text or a safe set of HTML tags. This is because HTML tags can allow embedding malicious links or scripts in text. More trusted registered users may be granted permission to use less restrictive text formats in order to create rich text. <strong>Improper text format configuration is a security risk.</strong>') . '</dd>';
        $output .= '<dt>' . t('Selecting filters') . '</dt>';
        $output .= '<dd>' . t('Each text format uses filters that add, remove, or transform elements within user-entered text. For example, one filter removes unapproved HTML tags, while another transforms URLs into clickable links. Filters are applied in a specific order. They do not change the <em>stored</em> content: they define how it is processed and displayed.') . '</dd>';
        $output .= '<dd>' . t('Each filter can have additional configuration options. For example, for the "Limit allowed HTML tags" filter you need to define the list of HTML tags that the filter leaves in the text.') . '</dd>';
        $output .= '<dt>' . t('Using text fields with text formats') . '</dt>';
        $output .= '<dd>' . t('Text fields that allow text formats are those with "formatted" in the description. These are <em>Text (formatted, long, with summary)</em>, <em>Text (formatted)</em>, and <em>Text (formatted, long)</em>. You cannot change the type of field once a field has been created.') . '</dd>';
        $output .= '<dt>' . t('Choosing a text format') . '</dt>';
        $output .= '<dd>' . t('When creating or editing data in a field that has text formats enabled, users can select the format under the field from the Text format select list.') . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'filter.admin_overview':
        $output = '<p>' . t('Text formats define how text is filtered for output and how HTML tags and other text is displayed, replaced, or removed. <strong>Improper text format configuration is a security risk.</strong> Learn more on the <a href=":filter_help">Filter module help page</a>.', [
          ':filter_help' => Url::fromRoute('help.page', [
            'name' => 'filter',
          ])->toString(),
        ]) . '</p>';
        $output .= '<p>' . t('Text formats are presented on content editing pages in the order defined on this page. The first format available to a user will be selected by default.') . '</p>';
        return $output;

      case 'entity.filter_format.edit_form':
        $output = '<p>' . t('A text format contains filters that change the display of user input; for example, stripping out malicious HTML or making URLs clickable. Filters are executed from top to bottom and the order is important, since one filter may prevent another filter from doing its job. For example, when URLs are converted into links before disallowed HTML tags are removed, all links may be removed. When this happens, the order of filters may need to be rearranged.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'filter_tips' => [
        'variables' => [
          'tips' => NULL,
          'long' => FALSE,
        ],
      ],
      'text_format_wrapper' => [
        'variables' => [
          'children' => NULL,
          'description' => NULL,
          'attributes' => [],
        ],
      ],
      'filter_guidelines' => [
        'variables' => [
          'format' => NULL,
        ],
      ],
      'filter_caption' => [
        'variables' => [
          'node' => NULL,
          'tag' => NULL,
          'caption' => NULL,
          'classes' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_filter_secure_image_alter().
   *
   * Formats an image DOM element that has an invalid source.
   *
   * @see _filter_html_image_secure_process()
   */
  #[Hook('filter_secure_image_alter')]
  public function filterSecureImageAlter(&$image): void {
    // Turn an invalid image into an error indicator.
    $image->setAttribute('src', base_path() . 'core/misc/icons/e32700/error.svg');
    $image->setAttribute('alt', t('Image removed.'));
    $image->setAttribute('title', t('This image has been removed. For security reasons, only images from the local domain are allowed.'));
    $image->setAttribute('height', '16');
    $image->setAttribute('width', '16');
    // Add a CSS class to aid in styling.
    $class = $image->getAttribute('class') ? trim($image->getAttribute('class')) . ' ' : '';
    $class .= 'filter-image-invalid';
    $image->setAttribute('class', $class);
  }

}
