<?php

declare(strict_types=1);

namespace Drupal\Core\Theme;

use Drupal\Core\Datetime\DatePreprocess;

/**
 * Provide common theme render elements.
 */
class ThemeCommonElements {

  /**
   * Base theme array.
   *
   * @return array
   *   System theme array.
   */
  public static function commonElements(): array {
    return [
      'html' => [
        'render element' => 'html',
        'initial preprocess' => ThemePreprocess::class . ':preprocessHtml',
      ],
      'page' => [
        'render element' => 'page',
        'initial preprocess' => ThemePreprocess::class . ':preprocessPage',
      ],
      'page_title' => [
        'variables' => [
          'title' => NULL,
        ],
      ],
      'region' => [
        'render element' => 'elements',
      ],
      'time' => [
        'variables' => [
          'timestamp' => NULL,
          'text' => NULL,
          'attributes' => [],
        ],
        'initial preprocess' => DatePreprocess::class . ':preprocessTime',
      ],
      'datetime_form' => [
        'render element' => 'element',
        'initial preprocess' => DatePreprocess::class . ':preprocessDatetimeForm',
      ],
      'datetime_wrapper' => [
        'render element' => 'element',
        'initial preprocess' => DatePreprocess::class . ':preprocessDatetimeWrapper',
      ],
      'status_messages' => [
        'variables' => [
          'status_headings' => [],
          'message_list' => NULL,
        ],
      ],
      'links' => [
        'variables' => [
          'links' => [],
          'attributes' => [
            'class' => ['links'],
          ],
          'heading' => [],
          'set_active_class' => FALSE,
        ],
        'initial preprocess' => ThemePreprocess::class . ':preprocessLinks',
      ],
      'dropbutton_wrapper' => [
        'variables' => [
          'children' => NULL,
        ],
      ],
      'image' => [
        // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5
        // draft allows the alt attribute to be omitted in some cases.
        // Therefore, default the alt attribute to an empty string, but allow
        // code providing variables to image.html.twig templates to pass
        // explicit NULL for it to be omitted. Usually, neither omission nor an
        // empty string satisfies accessibility requirements, so it is strongly
        // encouraged for code building variables for image.html.twig templates
        // to pass a meaningful value for the alt variable.
        // - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
        // - https://www.w3.org/TR/xhtml1/dtds.html
        // - http://dev.w3.org/html5/spec/Overview.html#alt
        // The title attribute is optional in all cases, so it is omitted by
        // default.
        'variables' => [
          'uri' => NULL,
          'width' => NULL,
          'height' => NULL,
          'alt' => '',
          'title' => NULL,
          'attributes' => [],
          'sizes' => NULL,
          'srcset' => [],
          'style_name' => NULL,
        ],
      ],
      'breadcrumb' => [
        'variables' => [
          'links' => [],
        ],
      ],
      'table' => [
        'variables' => [
          'header' => NULL,
          'rows' => NULL,
          'footer' => NULL,
          'attributes' => [],
          'caption' => NULL,
          'colgroups' => [],
          'sticky' => FALSE,
          'responsive' => TRUE,
          'empty' => '',
        ],
      ],
      'tablesort_indicator' => [
        'variables' => [
          'style' => NULL,
        ],
      ],
      'mark' => [
        'variables' => [
          'status' => MARK_NEW,
        ],
      ],
      'item_list' => [
        'variables' => [
          'items' => [],
          'title' => '',
          'list_type' => 'ul',
          'wrapper_attributes' => [],
          'attributes' => [],
          'empty' => NULL,
          'context' => [],
        ],
      ],
      'feed_icon' => [
        'variables' => [
          'url' => NULL,
          'title' => NULL,
          'attributes' => [],
        ],
      ],
      'progress_bar' => [
        'variables' => [
          'label' => NULL,
          'percent' => NULL,
          'message' => NULL,
        ],
      ],
      'indentation' => [
        'variables' => ['size' => 1],
      ],
      // From theme.maintenance.inc.
      'maintenance_page' => [
        'render element' => 'page',
      ],
      'install_page' => [
        'render element' => 'page',
      ],
      'maintenance_task_list' => [
        'variables' => [
          'items' => NULL,
          'active' => NULL,
          'variant' => NULL,
        ],
      ],
      'authorize_report' => [
        'variables' => [
          'messages' => [],
          'attributes' => [],
        ],
        'includes' => ['core/includes/theme.maintenance.inc'],
        'template' => 'authorize-report',
        'deprecated' => 'The "authorize-report" template is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3522119',
      ],
      'pager' => [
        'render element' => 'pager',
      ],
      'menu' => [
        'variables' => [
          'menu_name' => NULL,
          'items' => [],
          'attributes' => [],
        ],
      ],
      'menu_local_task' => [
        'render element' => 'element',
      ],
      'menu_local_action' => [
        'render element' => 'element',
      ],
      'menu_local_tasks' => [
        'variables' => [
          'primary' => [],
          'secondary' => [],
        ],
      ],
      // From form.inc.
      'input' => [
        'render element' => 'element',
      ],
      'select' => [
        'render element' => 'element',
      ],
      'fieldset' => [
        'render element' => 'element',
      ],
      'details' => [
        'render element' => 'element',
      ],
      'radios' => [
        'render element' => 'element',
      ],
      'checkboxes' => [
        'render element' => 'element',
      ],
      'form' => [
        'render element' => 'element',
      ],
      'textarea' => [
        'render element' => 'element',
      ],
      'form_element' => [
        'render element' => 'element',
      ],
      'form_element_label' => [
        'render element' => 'element',
      ],
      'vertical_tabs' => [
        'render element' => 'element',
      ],
      'container' => [
        'render element' => 'element',
        'initial preprocess' => ThemePreprocess::class . ':preprocessContainer',
      ],
      // From field system.
      'field' => [
        'render element' => 'element',
      ],
      'field_multiple_value_form' => [
        'render element' => 'element',
      ],
      'off_canvas_page_wrapper' => [
        'variables' => [
          'children' => NULL,
        ],
      ],
      'status_report_grouped' => [
        'variables' => [
          'grouped_requirements' => NULL,
          'requirements' => NULL,
        ],
      ],
    ];
  }

}
