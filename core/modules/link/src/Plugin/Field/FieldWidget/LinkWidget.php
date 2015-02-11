<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
 */

namespace Drupal\link\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
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
    return array(
      'placeholder_url' => '',
      'placeholder_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * Gets the URI without the 'user-path:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'user-path:' URIs: the scheme is stripped.
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

    // A different displayable string may be chosen in case of the 'user-path:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'user-path') {
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
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would, but only if:
      // - the entity could be loaded, and;
      // - the current user is allowed to view the entity (otherwise we have a
      //   information disclosure security problem).
      $entity_manager = \Drupal::entityManager();
      if ($entity_manager->getDefinition($entity_type, FALSE)) {
        $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id);
        if ($entity) {
          $label = ($entity->access('view')) ? $entity->label() : t('- Restricted access -');
          $displayable_string = $label . ' (' . $entity_id . ')';
        }
      }
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'user-path:' URIs.
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
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //    https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'user-path:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'user-path:' . $string;
    }

    return $uri;
  }

  /**
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value is mapped to a
    // 'user-path:' URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'user-path' && !in_array($element['#value'][0], ['/', '?', '#'], TRUE) && substr($element['#value'], 0, 7) !== '<front>') {
      $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
    }

    // If the URI is empty or not well-formed, the link field type's validation
    // constraint will detect it.
    // @see \Drupal\link\Plugin\Validation\Constraint\LinkTypeConstraint::validate()
    if (!empty($uri) && parse_url($uri)) {
      $url = Url::fromUri($uri);

      // Disallow unrouted internal URLs (i.e. disallow 'base:' URIs).
      $disallowed  = !$url->isRouted() && !$url->isExternal();
      // Disallow URLs if the current user doesn't have the 'link to any page'
      // permission nor can access this URI.
      $disallowed = $disallowed || (!\Drupal::currentUser()->hasPermission('link to any page') && !$url->access());
      // Disallow external URLs using untrusted protocols.
      $disallowed = $disallowed || ($url->isExternal() && !in_array(parse_url($uri, PHP_URL_SCHEME), UrlHelper::getAllowedProtocols()));
      // Disallow routed URLs that don't exist.
      if (!$disallowed && $url->isRouted()) {
        try {
          $url->toString();
        }
        catch (RouteNotFoundException $e) {
          $disallowed = TRUE;
        }
      }

      if ($disallowed) {
        $form_state->setError($element, t("The path '@link_path' is either invalid or you do not have access to it.", ['@link_path' => static::getUriAsDisplayableString($uri)]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];

    $element['uri'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      // The current field value could have been entered by a different user.
      // However, if it is inaccessible to the current user, do not display it
      // to them.
      '#default_value' => (!$item->isEmpty() && (\Drupal::currentUser()->hasPermission('link to any page') || $item->getUrl()->access())) ? static::getUriAsDisplayableString($item->uri) : NULL,
      '#element_validate' => array(array(get_called_class(), 'validateUriElement')),
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($this->supportsInternalLinks()) {
      $element['uri']['#type'] = 'entity_autocomplete';
      // @todo The user should be able to select an entity type. Will be fixed
      //    in https://www.drupal.org/node/2423093.
      $element['uri']['#target_type'] = 'node';
      // Disable autocompletion when the first character is '/', '#' or '?'.
      $element['uri']['#attributes']['data-autocomplete-first-character-blacklist'] = '/#?';
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix.
    if (!$this->supportsExternalLinks()) {
      $element['uri']['#field_prefix'] = rtrim(\Drupal::url('<front>', array(), array('absolute' => TRUE)), '/');
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($this->supportsExternalLinks() && $this->supportsInternalLinks()) {
      $element['uri']['#description'] = $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => '/node/add', '%url' => 'http://example.com'));
    }

    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    );
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = array($this, 'validateTitle');
    }

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = array(
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->options['attributes']) ? $items[$delta]->options['attributes'] : array(),
      '#attributes' => array('class' => array('link-field-widget-attributes')),
    );

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      // If the link title is disabled, use the field definition label as the
      // title of the 'uri' element.
      if ($this->getFieldSetting('title') == DRUPAL_DISABLED) {
        $element['uri']['#title'] = $element['#title'];
      }
      // Otherwise wrap everything in a details element.
      else {
        $element += array(
          '#type' => 'fieldset',
        );
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

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    $elements['placeholder_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $this->getSetting('placeholder_title'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => array(
        'invisible' => array(
          ':input[name="instance[settings][title]"]' => array('value' => DRUPAL_DISABLED),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_title = $this->getSetting('placeholder_title');
    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_title) && empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_title)) {
        $summary[] = $this->t('Title placeholder: @placeholder_title', array('@placeholder_title' => $placeholder_title));
      }
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * Form element validation handler; Validates the title property.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public function validateTitle(&$element, FormStateInterface $form_state, $form) {
    if ($element['uri']['#value'] !== '' && $element['title']['#value'] === '') {
      $element['title']['#required'] = TRUE;
      $form_state->setError($element['title'], $this->t('!name field is required.', array('!name' => $element['title']['#title'])));
    }
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
   * Override the '%uri' message parameter, to ensure that 'user-path:' URIs
   * show a validation error message that doesn't mention that scheme.
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($violations as $offset => $violation) {
      $parameters = $violation->getMessageParameters();
      if (isset($parameters['@uri'])) {
        $parameters['@uri'] = static::getUriAsDisplayableString($parameters['@uri']);
        $violations->set($offset, new ConstraintViolation(
          $this->t($violation->getMessageTemplate(), $parameters),
          $violation->getMessageTemplate(),
          $parameters,
          $violation->getRoot(),
          $violation->getPropertyPath(),
          $violation->getInvalidValue(),
          $violation->getMessagePluralization(),
          $violation->getCode()
        ));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

}
