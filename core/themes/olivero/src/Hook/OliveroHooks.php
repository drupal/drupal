<?php

namespace Drupal\olivero\Hook;

use Drupal\Core\Template\Attribute;
use Drupal\olivero\OliveroPreRender;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for olivero.
 */
class OliveroHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_HOOK() for page-title.
   */
  #[Hook('preprocess_page_title')]
  public function preprocessPageTitle(array &$variables): void {
    // Since the title and the shortcut link are both block level elements,
    // positioning them next to each other is much simpler with a wrapper div.
    if (!empty($variables['title_suffix']['add_or_remove_shortcut']) && $variables['title']) {
      // Add a wrapper div using the title_prefix and title_suffix render
      // elements.
      $variables['title_prefix']['shortcut_wrapper'] = [
        '#markup' => '<div class="shortcut-wrapper">',
        '#weight' => 100,
      ];
      $variables['title_suffix']['shortcut_wrapper'] = [
        '#markup' => '</div>',
        '#weight' => -99,
      ];
      // Make sure the shortcut link is the first item in title_suffix.
      $variables['title_suffix']['add_or_remove_shortcut']['#weight'] = -100;
    }
    // Unset shortcut link on front page.
    $variables['is_front'] = \Drupal::service('path.matcher')->isFrontPage();
    if ($variables['is_front'] === TRUE) {
      unset($variables['title_suffix']['add_or_remove_shortcut']);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for maintenance-page.
   */
  #[Hook('preprocess_maintenance_page')]
  public function preprocessMaintenancePage(array &$variables): void {
    // By default, site_name is set to Drupal if no db connection is available
    // or during site installation. Setting site_name to an empty string makes
    // the site and update pages look cleaner.
    // @see \Drupal\Core\Theme\ThemePreprocess::preprocessMaintenancePage()
    if (!$variables['db_is_active']) {
      $variables['site_name'] = '';
    }
    // Olivero has custom styling for the maintenance page.
    $variables['#attached']['library'][] = 'olivero/maintenance-page';
  }

  /**
   * Implements hook_preprocess_HOOK() for node.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    // Remove the "Add new comment" link on teasers or when the comment form is
    // displayed on the page.
    if ($variables['view_mode'] === 'teaser' || !empty($variables['content']['comments']['comment_form'])) {
      unset($variables['content']['links']['comment']['#links']['comment-add']);
    }
    // Apply custom date formatter to "date" field.
    if (!empty($variables['date']) && !empty($variables['display_submitted']) && $variables['display_submitted'] === TRUE) {
      $variables['date'] = \Drupal::service('date.formatter')->format($variables['node']->getCreatedTime(), 'olivero_medium');
    }
    // Pass layout variable to template if content type is article in full view
    // mode. This is then used in the template to create a BEM style CSS class
    // to control the layout.
    if ($variables['node']->bundle() === 'article' && $variables['view_mode'] === 'full') {
      $variables['layout'] = 'content-narrow';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if (!empty($variables['elements']['#id'])) {
      /** @var \Drupal\block\BlockInterface $block */
      $block = \Drupal::entityTypeManager()->getStorage('block')->load($variables['elements']['#id']);
      if ($block) {
        $region = $block->getRegion();
        if ($variables['base_plugin_id'] === 'system_menu_block') {
          $variables['content']['#attributes']['region'] = $region;
          if ($region === 'sidebar') {
            $variables['#attached']['library'][] = 'olivero/menu-sidebar';
          }
        }
        if ($variables['base_plugin_id'] === 'search_form_block') {
          if ($region === 'primary_menu') {
            $variables['#attached']['library'][] = 'olivero/search-narrow';
            $variables['content']['actions']['submit']['#theme_wrappers'] = [
              'input__submit__header_search',
            ];
          }
          elseif ($region === 'secondary_menu') {
            $variables['#attached']['library'][] = 'olivero/search-wide';
            $variables['content']['actions']['submit']['#theme_wrappers'] = [
              'input__submit__header_search',
            ];
          }
        }
      }
    }
    if ($variables['plugin_id'] === 'system_branding_block') {
      $site_branding_color = \Drupal::service(ThemeSettingsProvider::class)->getSetting('site_branding_bg_color');
      if ($site_branding_color && $site_branding_color !== 'default') {
        $variables['attributes']['class'][] = 'site-branding--bg-' . $site_branding_color;
      }
    }
    // Add a primary-nav class to main menu navigation block.
    if ($variables['plugin_id'] === 'system_menu_block:main') {
      $variables['attributes']['class'][] = 'primary-nav';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for menu.
   */
  #[Hook('theme_suggestions_menu_alter')]
  public function themeSuggestionsMenuAlter(array &$suggestions, array $variables): void {
    if (isset($variables['attributes']['region'])) {
      $suggestions[] = 'menu__' . $variables['attributes']['region'];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu.
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(array &$variables): void {
    if (isset($variables['attributes']['region'])) {
      if ($variables['attributes']['region'] === 'sidebar') {
        $variables['attributes']['class'][] = 'menu--sidebar';
      }
      unset($variables['attributes']['region']);
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for form templates.
   */
  #[Hook('theme_suggestions_form_alter')]
  public function themeSuggestionsFormAlter(array &$suggestions, array $variables): void {
    if ($variables['element']['#form_id'] === 'search_block_form') {
      $suggestions[] = 'form__search_block_form';
    }
  }

  /**
   * Implements hook_form_alter() for adding classes and placeholder text to the search forms.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    if (isset($form['actions']['submit']) && count($form['actions']) <= 2) {
      $form['actions']['submit']['#attributes']['class'][] = 'button--primary';
    }
    switch ($form_id) {
      case 'search_block_form':
        // Add placeholder text to keys input.
        $form['keys']['#attributes']['placeholder'] = $this->t('Search by keyword or phrase.');
        // Add classes to the search form submit input.
        $form['actions']['submit']['#attributes']['class'][] = 'search-form__submit';
        break;

      case 'search_form':
        $form['basic']['keys']['#attributes']['placeholder'] = $this->t('Search by keyword or phrase.');
        $form['basic']['submit']['#attributes']['class'][] = 'button--primary';
        $form['advanced']['submit']['#attributes']['class'][] = 'button--primary';
        break;
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for block().
   */
  #[Hook('theme_suggestions_block_alter')]
  public function themeSuggestionsBlockAlter(array &$suggestions, array $variables): void {
    if (!empty($variables['elements']['#id'])) {
      /** @var \Drupal\block\BlockInterface $block */
      $block = \Drupal::entityTypeManager()->getStorage('block')->load($variables['elements']['#id']);
      if ($block) {
        // Add region-specific block theme suggestions.
        $region = $block->getRegion();
        $suggestions[] = 'block__' . $region;
        $suggestions[] = 'block__' . $region . '__plugin_id__' . $variables['elements']['#plugin_id'];
        $suggestions[] = 'block__' . $region . '__id__' . $variables['elements']['#id'];
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-tasks.
   */
  #[Hook('preprocess_menu_local_tasks')]
  public function preprocessMenuLocalTasks(array &$variables): void {
    foreach (Element::children($variables['primary']) as $key) {
      $variables['primary'][$key]['#level'] = 'primary';
    }
    foreach (Element::children($variables['secondary']) as $key) {
      $variables['secondary'][$key]['#level'] = 'secondary';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for form-element.
   */
  #[Hook('preprocess_form_element')]
  public function preprocessFormElement(array &$variables): void {
    if (in_array($variables['element']['#type'] ?? FALSE, [
      'checkbox',
      'radio',
    ], TRUE)) {
      $variables['attributes']['class'][] = 'form-type-boolean';
    }
    if (!empty($variables['description']['attributes'])) {
      $variables['description']['attributes']->addClass('form-item__description');
    }
    if ($variables['disabled']) {
      $variables['label']['#attributes']['class'][] = 'is-disabled';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for form-element-label.
   */
  #[Hook('preprocess_form_element_label')]
  public function preprocessFormElementLabel(array &$variables): void {
    $variables['attributes']['class'][] = 'form-item__label';
  }

  /**
   * Implements hook_preprocess_HOOK() for input.
   */
  #[Hook('preprocess_input')]
  public function preprocessInput(array &$variables): void {
    if (!empty($variables['element']['#title_display']) && $variables['element']['#title_display'] === 'attribute' && !empty((string) $variables['element']['#title'])) {
      $variables['attributes']['title'] = (string) $variables['element']['#title'];
    }
    $type_api = $variables['element']['#type'];
    $type_html = $variables['attributes']['type'];
    $text_types_html = [
      'text',
      'email',
      'tel',
      'number',
      'search',
      'password',
      'date',
      'time',
      'file',
      'color',
      'datetime-local',
      'url',
      'month',
      'week',
    ];
    if (in_array($type_html, $text_types_html, TRUE)) {
      $variables['attributes']['class'][] = 'form-element';
      $variables['attributes']['class'][] = Html::getClass('form-element--type-' . $type_html);
      $variables['attributes']['class'][] = Html::getClass('form-element--api-' . $type_api);
      // This logic is functioning as expected, but there is nothing in the
      // theme that renders the result. As a result it can't currently be
      // covered by a functional test.
      if (!empty($variables['element']['#autocomplete_route_name'])) {
        $variables['autocomplete_message'] = $this->t('Loading…');
      }
    }
    if (in_array($type_html, [
      'checkbox',
      'radio',
    ], TRUE)) {
      $variables['attributes']['class'][] = 'form-boolean';
      $variables['attributes']['class'][] = Html::getClass('form-boolean--type-' . $type_html);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for textarea.
   */
  #[Hook('preprocess_textarea')]
  public function preprocessTextarea(array &$variables): void {
    $variables['attributes']['class'][] = 'form-element';
    $variables['attributes']['class'][] = 'form-element--type-textarea';
    $variables['attributes']['class'][] = 'form-element--api-textarea';
  }

  /**
   * Implements hook_preprocess_HOOK() for select.
   */
  #[Hook('preprocess_select')]
  public function preprocessSelect(array &$variables): void {
    $variables['attributes']['class'][] = 'form-element';
    $variables['attributes']['class'][] = $variables['element']['#multiple'] ? 'form-element--type-select-multiple' : 'form-element--type-select';
  }

  /**
   * Implements hook_preprocess_HOOK() for checkboxes.
   */
  #[Hook('preprocess_checkboxes')]
  public function preprocessCheckboxes(array &$variables): void {
    $variables['attributes']['class'][] = 'form-boolean-group';
  }

  /**
   * Implements hook_preprocess_HOOK() for radios.
   */
  #[Hook('preprocess_radios')]
  public function preprocessRadios(array &$variables): void {
    $variables['attributes']['class'][] = 'form-boolean-group';
  }

  /**
   * Implements hook_preprocess_HOOK() for field.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    $rich_field_types = [
      'text_with_summary',
      'text',
      'text_long',
    ];
    if (in_array($variables['field_type'], $rich_field_types, TRUE)) {
      $variables['attributes']['class'][] = 'text-content';
      $variables['#attached']['library'][] = 'olivero/olivero.table';
    }
    if ($variables['field_type'] == 'image' && $variables['element']['#view_mode'] == 'full' && !$variables["element"]["#is_multiple"] && $variables['field_name'] !== 'user_picture') {
      $variables['attributes']['class'][] = 'wide-content';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field-multiple-value-form.
   */
  #[Hook('preprocess_field_multiple_value_form')]
  public function preprocessFieldMultipleValueForm(array &$variables): void {
    // Make disabled available for the template.
    $variables['disabled'] = !empty($variables['element']['#disabled']);
    if (!empty($variables['multiple'])) {
      // Add an additional CSS class for the field label table cell.
      // This repeats the logic of
      // \Drupal\Core\Field\FieldPreprocess::preprocessFieldMultipleValueForm()
      // without using '#prefix' and '#suffix' for the wrapper element.
      //
      // If the field is multiple, we don't have to check the existence of the
      // table header cell.
      $header_attributes = [
        'class' => [
          'form-item__label',
          'form-item__label--multiple-value-form',
        ],
      ];
      if (!empty($variables['element']['#required'])) {
        $header_attributes['class'][] = 'js-form-required';
        $header_attributes['class'][] = 'form-required';
      }
      // Using array_key_first() for addressing the first header cell would be
      // more elegant here, but we can rely on the related theme.inc preprocess.
      // @todo change this after https://www.drupal.org/node/3099026 has landed.
      $variables['table']['#header'][0]['data'] = [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $variables['element']['#title'],
        '#attributes' => $header_attributes,
      ];
      if ($variables['disabled']) {
        $variables['table']['#attributes']['class'][] = 'tabledrag-disabled';
        $variables['table']['#attributes']['class'][] = 'js-tabledrag-disabled';
        // We will add the 'is-disabled' CSS class to the disabled table header
        // cells.
        $header_attributes['class'][] = 'is-disabled';
        foreach ($variables['table']['#header'] as &$cell) {
          if (is_array($cell) && isset($cell['data'])) {
            $cell = $cell + [
              'class' => [],
            ];
            $cell['class'][] = 'is-disabled';
          }
          else {
            // We have to modify the structure of this header cell.
            $cell = [
              'data' => $cell,
              'class' => [
                'is-disabled',
              ],
            ];
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-task.
   */
  #[Hook('preprocess_menu_local_task')]
  public function preprocessMenuLocalTask(array &$variables): void {
    $variables['link']['#options']['attributes']['class'][] = 'tabs__link';
    $variables['link']['#options']['attributes']['class'][] = 'js-tabs-link';
    // Ensure is-active class is set when the tab is active. The generic active
    // link handler applies stricter comparison rules than what is necessary for
    // tabs.
    if (isset($variables['is_active']) && $variables['is_active'] === TRUE) {
      $variables['link']['#options']['attributes']['class'][] = 'is-active';
    }
    if (isset($variables['element']['#level'])) {
      $variables['level'] = $variables['element']['#level'];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for fieldset.
   */
  #[Hook('preprocess_fieldset')]
  public function preprocessFieldset(array &$variables): void {
    $element = $variables['element'];
    $composite_types = [
      'checkboxes',
      'radios',
    ];
    if (!empty($element['#type']) && in_array($element['#type'], $composite_types) && !empty($variables['element']['#children_errors'])) {
      $variables['legend_span']['attributes']->addClass('has-error');
    }
    if (!empty($element['#disabled'])) {
      $variables['legend_span']['attributes']->addClass('is-disabled');
      if (!empty($variables['description']) && !empty($variables['description']['attributes'])) {
        $variables['description']['attributes']->addClass('is-disabled');
      }
    }
    // Remove 'container-inline' class from the main attributes and add a flag
    // instead.
    // @todo remove this after https://www.drupal.org/node/3059593 has been
    //   resolved.
    if (!empty($variables['attributes']['class'])) {
      $container_inline_key = array_search('container-inline', $variables['attributes']['class']);
      if ($container_inline_key !== FALSE) {
        unset($variables['attributes']['class'][$container_inline_key]);
        $variables['inline_items'] = TRUE;
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_user_alter')]
  public function themeSuggestionsUserAlter(array &$suggestions, array $variables): void {
    $suggestions[] = 'user__' . $variables['elements']['#view_mode'];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field__node__created')]
  public function preprocessFieldNodeCreated(array &$variables): void {
    foreach (Element::children($variables['items']) as $item) {
      unset($variables['items'][$item]['content']['#prefix']);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for setting classes.
   */
  #[Hook('preprocess_filter_caption')]
  public function preprocessFilterCaption(array &$variables): void {
    $variables['classes'] = isset($variables['classes']) && !empty($variables['classes']) ? $variables['classes'] . ' caption' : 'caption';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for node_preview_form_select.
   */
  #[Hook('form_node_preview_form_select_alter')]
  public function formNodePreviewFormSelectAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $form['backlink']['#options']['attributes']['class'][] = 'button';
    $form['backlink']['#options']['attributes']['class'][] = 'button--small';
    $form['backlink']['#options']['attributes']['class'][] = 'button--icon-back';
    $form['backlink']['#options']['attributes']['class'][] = 'button--primary';
    $form['view_mode']['#attributes']['class'][] = 'form-element--small';
  }

  /**
   * Implements hook_preprocess_HOOK() for comment.
   */
  #[Hook('preprocess_comment')]
  public function preprocessComment(array &$variables): void {
    // Getting the node creation time stamp from the comment object.
    $date = $variables['comment']->getCreatedTime();
    // Formatting "created" as "X days ago".
    $variables['created'] = $this->t('@time ago', [
      '@time' => \Drupal::service('date.formatter')->formatInterval(\Drupal::time()->getRequestTime() - $date),
    ]);
  }

  /**
   * Implements hook_preprocess_HOOK() for field--comment.
   */
  #[Hook('preprocess_field__comment')]
  public function preprocessFieldComment(array &$variables): void {
    // Add a comment_count.
    $variables['comment_count'] = count(array_filter($variables['comments'], 'is_numeric', ARRAY_FILTER_USE_KEY));
    // Add user.compact to field-comment if profile's avatar of current user
    // exist.
    $user = \Drupal::currentUser();
    if ($user->isAuthenticated() && $user instanceof UserInterface) {
      if ($user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
        $variables['user_picture'] = \Drupal::entityTypeManager()->getViewBuilder('user')->view($user, 'compact');
      }
      $variables['#cache']['contexts'][] = 'user';
    }
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    if (array_key_exists('text_format', $info)) {
      $info['text_format']['#pre_render'][] = [
        OliveroPreRender::class,
        'textFormat',
      ];
    }
    if (isset($info['status_messages'])) {
      $info['status_messages']['#pre_render'][] = [
        OliveroPreRender::class,
        'messagePlaceholder',
      ];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for text-format-wrapper.
   *
   * @todo Remove when https://www.drupal.org/node/3016343 is fixed.
   */
  #[Hook('preprocess_text_format_wrapper')]
  public function preprocessTextFormatWrapper(array &$variables): void {
    $description_attributes = [];
    if (!empty($variables['attributes']['id'])) {
      $description_attributes['id'] = $variables['attributes']['aria-describedby'] = $variables['attributes']['id'];
      unset($variables['attributes']['id']);
    }
    $variables['description_attributes'] = new Attribute($description_attributes);
  }

  /**
   * Implements hook_preprocess_search_result().
   */
  #[Hook('preprocess_search_result')]
  public function preprocessSearchResult(array &$variables): void {
    // Apply custom date formatter to "date" field.
    if (!empty($variables['result']['date'])) {
      $variables['info_date'] = \Drupal::service('date.formatter')->format($variables['result']['node']->getCreatedTime(), 'olivero_medium');
    }
  }

  /**
   * Implements hook_preprocess_item_list__search_results().
   */
  #[Hook('preprocess_item_list__search_results')]
  public function preprocessItemListSearchResults(array &$variables): void {
    if (isset($variables['empty'])) {
      $variables['empty']['#attributes']['class'][] = 'empty-search-results-text';
      $variables['empty']['#attached']['library'][] = 'olivero/search-results';
    }
  }

  /**
   * Implements hook_preprocess_links__comment().
   */
  #[Hook('preprocess_links__comment')]
  public function preprocessLinksComment(array &$variables): void {
    foreach ($variables['links'] as &$link) {
      $link['link']['#options']['attributes']['class'][] = 'comment__links-link';
    }
  }

  /**
   * Implements hook_preprocess_table().
   */
  #[Hook('preprocess_table')]
  public function preprocessTable(array &$variables): void {
    // Mark the whole table and the first cells if rows are draggable.
    if (!empty($variables['rows'])) {
      $draggable_row_found = FALSE;
      foreach ($variables['rows'] as &$row) {
        /** @var \Drupal\Core\Template\Attribute $row['attributes'] */
        if (!empty($row['attributes']) && $row['attributes']->hasClass('draggable')) {
          if (!$draggable_row_found) {
            $variables['attributes']['class'][] = 'draggable-table';
            $draggable_row_found = TRUE;
          }
        }
      }
    }
    $variables['#attached']['library'][] = 'olivero/olivero.table';
  }

  /**
   * Implements hook_preprocess_HOOK() for views-view-table.
   */
  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(array &$variables): void {
    $variables['#attached']['library'][] = 'olivero/olivero.table';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for views_exposed_form.
   */
  #[Hook('form_views_exposed_form_alter')]
  public function formViewsExposedFormAlter(array &$form): void {
    $form['#attributes']['class'][] = 'form--inline';
  }

}
