<?php

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsRow;

/**
 * Renders an RSS item based on fields.
 */
#[ViewsRow(
  id: "rss_fields",
  title: new TranslatableMarkup("Fields"),
  help: new TranslatableMarkup("Display fields as RSS items."),
  theme: "views_view_row_rss",
  display_types: ["feed"]
)]
class RssFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title_field'] = ['default' => ''];
    $options['link_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['creator_field'] = ['default' => ''];
    $options['date_field'] = ['default' => ''];
    $options['guid_field_options']['contains']['guid_field'] = ['default' => ''];
    $options['guid_field_options']['contains']['guid_field_is_permalink'] = ['default' => TRUE];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that is going to be used as the RSS item title for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#description' => $this->t('The field that is going to be used as the RSS item link for each row. This must either be an internal unprocessed path like "node/123" or a processed, root-relative URL as produced by fields like "Link to content".'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => TRUE,
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#description' => $this->t('The field that is going to be used as the RSS item description for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#required' => TRUE,
    ];
    $form['creator_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Creator field'),
      '#description' => $this->t('The field that is going to be used as the RSS item creator for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['creator_field'],
      '#required' => TRUE,
    ];
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication date field'),
      '#description' => $this->t('The field that is going to be used as the RSS item pubDate for each row. It needs to be in RFC 2822 format.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['date_field'],
      '#required' => TRUE,
    ];
    $form['guid_field_options'] = [
      '#type' => 'details',
      '#title' => $this->t('GUID settings'),
      '#open' => TRUE,
    ];
    $form['guid_field_options']['guid_field'] = [
      '#type' => 'select',
      '#title' => $this->t('GUID field'),
      '#description' => $this->t('The globally unique identifier of the RSS item.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['guid_field_options']['guid_field'],
      '#required' => TRUE,
    ];
    $form['guid_field_options']['guid_field_is_permalink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('GUID is permalink'),
      '#description' => $this->t('The RSS item GUID is a permalink.'),
      '#default_value' => $this->options['guid_field_options']['guid_field_is_permalink'],
    ];
  }

  public function validate() {
    $errors = parent::validate();
    $required_options = ['title_field', 'link_field', 'description_field', 'creator_field', 'date_field'];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
        break;
      }
    }
    // Once more for guid.
    if (empty($this->options['guid_field_options']['guid_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
    }
    return $errors;
  }

  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    // Create the RSS item object.
    $item = new \stdClass();
    $item->title = $this->getField($row_index, $this->options['title_field']);
    $item->link = $this->getAbsoluteUrl($this->getField($row_index, $this->options['link_field']));

    $field = $this->getField($row_index, $this->options['description_field']);
    $item->description = is_array($field) ? $field : ['#markup' => $field];

    $item->elements = [
      // Default rendering of date fields adds a <time> tag and whitespace, we
      // want to remove these because this breaks RSS feeds.
      ['key' => 'pubDate', 'value' => trim(strip_tags($this->getField($row_index, $this->options['date_field'])))],
      [
        'key' => 'dc:creator',
        'value' => $this->getField($row_index, $this->options['creator_field']),
        'namespace' => ['xmlns:dc' => 'http://purl.org/dc/elements/1.1/'],
      ],
    ];
    $guid_is_permalink_string = 'false';
    $item_guid = $this->getField($row_index, $this->options['guid_field_options']['guid_field']);
    if ($this->options['guid_field_options']['guid_field_is_permalink']) {
      $guid_is_permalink_string = 'true';
      $item_guid = $this->getAbsoluteUrl($item_guid);
    }
    $item->elements[] = [
      'key' => 'guid',
      'value' => $item_guid,
      'attributes' => ['isPermaLink' => $guid_is_permalink_string],
    ];

    $row_index++;

    foreach ($item->elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => $this->field_alias ?? '',
    ];

    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string|null|\Drupal\Component\Render\MarkupInterface
   *   An empty string if there is no style plugin, or the field ID is empty.
   *   NULL if the field value is empty. If neither of these conditions apply,
   *   a MarkupInterface object containing the rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return $this->view->style_plugin->getField($index, $field_id);
  }

  /**
   * Convert a rendered URL string to an absolute URL.
   *
   * @param string $url_string
   *   The rendered field value ready for display in a normal view.
   *
   * @return string
   *   A string with an absolute URL.
   */
  protected function getAbsoluteUrl($url_string) {
    // If the given URL already starts with a leading slash, it's been processed
    // and we need to simply make it an absolute path by prepending the host.
    if (str_starts_with($url_string, '/')) {
      $host = \Drupal::request()->getSchemeAndHttpHost();
      // @todo Views should expect and store a leading /.
      // @see https://www.drupal.org/node/2423913
      return $host . $url_string;
    }
    // Otherwise, this is an unprocessed path (e.g. node/123) and we need to run
    // it through a Url object to allow outbound path processors to run (path
    // aliases, language prefixes, etc).
    else {
      return Url::fromUserInput('/' . $url_string)->setAbsolute()->toString();
    }
  }

}
