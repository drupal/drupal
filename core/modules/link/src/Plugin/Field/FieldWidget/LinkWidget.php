<?php

namespace Drupal\link\Plugin\Field\FieldWidget;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_default",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder_url' => '',
      'placeholder_title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *
   * @see static::getUserEnteredStringAsUri()
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      [$entity_type, $entity_id] = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      if ($entity_type == 'node' && $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }
    elseif ($scheme === 'route') {
      $displayable_string = ltrim($displayable_string, 'route:');
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is a URI.
    $uri = trim($string);

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Support linking to nothing.
    elseif (in_array($string, ['<nolink>', '<none>', '<button>'], TRUE)) {
      $uri = 'route:' . $string;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (str_starts_with($string, '<front>')) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to an 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0], ['/', '?', '#'], TRUE) && !str_starts_with($element['#value'], '<front>')) {
      $form_state->setError($element, new TranslatableMarkup('Manually entered paths should start with one of the following characters: / ? #'));
      return;
    }
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public static function validateTitleElement(&$element, FormStateInterface $form_state, $form) {
    if ($element['uri']['#value'] !== '' && $element['title']['#value'] === '') {
      // We expect the field name placeholder value to be wrapped in $this->t() here,
      // so it won't be escaped again as it's already marked safe.
      $form_state->setError($element['title'], new TranslatableMarkup('@title field is required if there is @uri input.', ['@title' => $element['title']['#title'], '@uri' => $element['uri']['#title']]));
    }
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Requires the URL value if a link title was filled in.
   */
  public static function validateTitleNoLink(&$element, FormStateInterface $form_state, $form) {
    if ($element['uri']['#value'] === '' && $element['title']['#value'] !== '') {
      $form_state->setError($element['uri'], new TranslatableMarkup('The @uri field is required when the @title field is specified.', ['@title' => $element['title']['#title'], '@uri' => $element['uri']['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];

    $display_uri = NULL;
    if (!$item->isEmpty()) {
      try {
        // The current field value could have been entered by a different user.
        // However, if it is inaccessible to the current user, do not display it
        // to them.
        if (\Drupal::currentUser()->hasPermission('link to any page') || $item->getUrl()->access()) {
          $display_uri = static::getUriAsDisplayableString($item->uri);
        }
      }
      catch (\InvalidArgumentException $e) {
        // If $item->uri is invalid, show value as is, so the user can see what
        // to edit.
        // @todo Add logging here in https://www.drupal.org/project/drupal/issues/3348020
        $display_uri = $item->uri;
      }
    }
    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $display_uri,
      '#element_validate' => [[static::class, 'validateUriElement']],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#link_type' => $this->getFieldSetting('link_type'),
    ];

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($this->supportsInternalLinks()) {
      $element['uri']['#type'] = 'entity_autocomplete';
      // @todo The user should be able to select an entity type. Will be fixed
      //   in https://www.drupal.org/node/2423093.
      $element['uri']['#target_type'] = 'node';
      // Disable autocompletion when the first character is '/', '#' or '?'.
      $element['uri']['#attributes']['data-autocomplete-first-character-blacklist'] = '/#?';

      // The link widget is doing its own processing in
      // static::getUriAsDisplayableString().
      $element['uri']['#process_default_value'] = FALSE;
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix and description.
    if (!$this->supportsExternalLinks()) {
      $element['uri']['#field_prefix'] = rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), '/');
      $element['uri']['#description'] = $this->t('This must be an internal path such as %add-node. You can also start typing the title of a piece of content to select it. Enter %front to link to the front page. Enter %nolink to display link text only. Enter %button to display keyboard-accessible link text only.', ['%add-node' => '/node/add', '%front' => '<front>', '%nolink' => '<nolink>', '%button' => '<button>']);
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($this->supportsExternalLinks() && $this->supportsInternalLinks()) {
      $element['uri']['#description'] = $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page. Enter %nolink to display link text only. Enter %button to display keyboard-accessible link text only.', ['%front' => '<front>', '%add-node' => '/node/add', '%url' => 'http://example.com', '%nolink' => '<nolink>', '%button' => '<button>']);
    }
    // If the field is configured to allow only external links, show a useful
    // description.
    elseif ($this->supportsExternalLinks() && !$this->supportsInternalLinks()) {
      $element['uri']['#description'] = $this->t('This must be an external URL such as %url.', ['%url' => 'http://example.com']);
    }

    // Make uri required on the front-end when title filled-in.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') !== DRUPAL_DISABLED && !$element['uri']['#required']) {
      $parents = $element['#field_parents'];
      $parents[] = $this->fieldDefinition->getName();
      $selector = $root = array_shift($parents);
      if ($parents) {
        $selector = $root . '[' . implode('][', $parents) . ']';
      }

      $element['uri']['#states']['required'] = [
        ':input[name="' . $selector . '[' . $delta . '][title]"]' => ['filled' => TRUE],
      ];
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => $items[$delta]->title ?? NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
      '#required' => $this->getFieldSetting('title') === DRUPAL_REQUIRED && $element['#required'],
    ];
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    //
    // Validate that title field is filled out (regardless of uri) when it is a
    // required field.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') === DRUPAL_REQUIRED) {
      $element['#element_validate'][] = [static::class, 'validateTitleElement'];
      $element['#element_validate'][] = [static::class, 'validateTitleNoLink'];

      if (!$element['title']['#required']) {
        // Make title required on the front-end when URI filled-in.

        $parents = $element['#field_parents'];
        $parents[] = $this->fieldDefinition->getName();
        $selector = $root = array_shift($parents);
        if ($parents) {
          $selector = $root . '[' . implode('][', $parents) . ']';
        }

        $element['title']['#states']['required'] = [
          ':input[name="' . $selector . '[' . $delta . '][uri]"]' => ['filled' => TRUE],
        ];
      }
    }

    // Ensure that a URI is always entered when an optional title field is
    // submitted.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') == DRUPAL_OPTIONAL) {
      $element['#element_validate'][] = [static::class, 'validateTitleNoLink'];
    }

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = [
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->options['attributes']) ? $items[$delta]->options['attributes'] : [],
      '#attributes' => ['class' => ['link-field-widget-attributes']],
    ];

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      // If the link title is disabled, use the field definition label as the
      // title of the 'uri' element.
      if ($this->getFieldSetting('title') == DRUPAL_DISABLED) {
        $element['uri']['#title'] = $element['#title'];
        // By default the field description is added to the title field. Since
        // the title field is disabled, we add the description, if given, to the
        // uri element instead.
        if (!empty($element['#description'])) {
          if (empty($element['uri']['#description'])) {
            $element['uri']['#description'] = $element['#description'];
          }
          else {
            // If we have the description of the type of field together with
            // the user provided description, we want to make a distinction
            // between "core help text" and "user entered help text". To make
            // this distinction more clear, we put them in an unordered list.
            $element['uri']['#description'] = [
              '#theme' => 'item_list',
              '#items' => [
                // Assume the user-specified description has the most relevance,
                // so place it first.
                $element['#description'],
                $element['uri']['#description'],
              ],
            ];
          }
        }
      }
      // Otherwise wrap everything in a details element.
      else {
        $element += [
          '#type' => 'fieldset',
        ];
      }
    }

    return $element;
  }

  /**
   * Indicates enabled support for link to routes.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   routes, otherwise FALSE.
   */
  protected function supportsInternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_INTERNAL);
  }

  /**
   * Indicates enabled support for link to external URLs.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   external URLs, otherwise FALSE.
   */
  protected function supportsExternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & LinkItemInterface::LINK_EXTERNAL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $elements['placeholder_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => [
        'invisible' => [
          ':input[name="instance[settings][title]"]' => ['value' => DRUPAL_DISABLED],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', ['@placeholder_title' => $placeholder_title]);
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', ['@placeholder_url' => $placeholder_url]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
      $value += ['options' => []];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * Override the '%uri' message parameter, to ensure that 'internal:' URIs
   * show a validation error message that doesn't mention that scheme.
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($violations as $offset => $violation) {
      $parameters = $violation->getParameters();
      if (isset($parameters['@uri'])) {
        $parameters['@uri'] = static::getUriAsDisplayableString($parameters['@uri']);
        $violations->set($offset, new ConstraintViolation(
          $this->t($violation->getMessageTemplate(), $parameters),
          $violation->getMessageTemplate(),
          $parameters,
          $violation->getRoot(),
          $violation->getPropertyPath(),
          $violation->getInvalidValue(),
          $violation->getPlural(),
          $violation->getCode()
        ));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

}
