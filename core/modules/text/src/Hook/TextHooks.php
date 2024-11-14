<?php

namespace Drupal\text\Hook;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for text.
 */
class TextHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.text':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Text module allows you to create short and long text fields with optional summaries. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":text_documentation">online documentation for the Text module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':text_documentation' => 'https://www.drupal.org/documentation/modules/text',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing and displaying text fields') . '</dt>';
        $output .= '<dd>' . t('The <em>settings</em> and <em>display</em> of the text field can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . t('Creating short text fields') . '</dt>';
        $output .= '<dd>' . t('If you choose <em>Text (plain)</em> or <em>Text (formatted)</em> as the field type on the <em>Manage fields</em> page, then a field with a single row is displayed. You can change the maximum text length in the <em>Field settings</em> when you set up the field.') . '</dd>';
        $output .= '<dt>' . t('Creating long text fields') . '</dt>';
        $output .= '<dd>' . t('If you choose <em>Text (plain, long)</em>, <em>Text (formatted, long)</em>, or <em>Text (formatted, long, with summary)</em> on the <em>Manage fields</em> page, then users can insert text of unlimited length. On the <em>Manage form display</em> page, you can set the number of rows that are displayed to users.') . '</dd>';
        $output .= '<dt>' . t('Trimming the text length') . '</dt>';
        $output .= '<dd>' . t('On the <em>Manage display</em> page you can choose to display a trimmed version of the text, and if so, where to cut off the text.') . '</dd>';
        $output .= '<dt>' . t('Displaying summaries instead of trimmed text') . '</dt>';
        $output .= '<dd>' . t('As an alternative to using a trimmed version of the text, you can enter a separate summary by choosing the <em>Text (formatted, long, with summary)</em> field type on the <em>Manage fields</em> page. Even when <em>Summary input</em> is enabled, and summaries are provided, you can display <em>trimmed</em> text nonetheless by choosing the appropriate format on the <em>Manage display</em> page.') . '</dd>';
        $output .= '<dt>' . t('Using text formats and editors') . '</dt>';
        $output .= '<dd>' . t('If you choose <em>Text (plain)</em> or <em>Text (plain, long)</em> you restrict the input to <em>Plain text</em> only. If you choose <em>Text (formatted)</em>, <em>Text (formatted, long)</em>, or <em>Text (formatted, long with summary)</em> you allow users to write formatted text. Which options are available to individual users depends on the settings on the <a href=":formats">Text formats and editors page</a>.', [':formats' => Url::fromRoute('filter.admin_overview')->toString()]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

}
