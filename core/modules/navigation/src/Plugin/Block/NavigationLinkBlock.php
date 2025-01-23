<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Defines a link navigation block.
 *
 * @internal
 */
#[Block(
  id: 'navigation_link',
  admin_label: new TranslatableMarkup('Link'),
)]
final class NavigationLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'title' => '',
      'uri' => '',
      'icon_class' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->configuration;

    $display_uri = NULL;
    if (!empty($config['uri'])) {
      try {
        // The current field value could have been entered by a different user.
        // However, if it is inaccessible to the current user, do not display it
        // to them.
        $url = Url::fromUri($config['uri']);
        if (\Drupal::currentUser()->hasPermission('link to any page') || $url->access()) {
          $display_uri = static::getUriAsDisplayableString($config['uri']);
        }
      }
      catch (\InvalidArgumentException) {
        // If $item->uri is invalid, show value as is, so the user can see what
        // to edit.
        $display_uri = $config['uri'];
      }
    }

    // @todo Logic related to the uri component has been borrowed from
    //   Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
    //   Will be fixed in https://www.drupal.org/project/drupal/issues/3450518.
    $form['uri'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('URL'),
      '#default_value' => $display_uri,
      '#element_validate' => [[static::class, 'validateUriElement']],
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      // @todo The user should be able to select an entity type. Will be fixed
      //   in https://www.drupal.org/node/2423093.
      '#target_type' => 'node',
      '#maxlength' => 2048,
      '#required' => TRUE,
      '#process_default_value' => FALSE,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['icon_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon CSS class'),
      '#default_value' => $config['icon_class'],
      '#element_validate' => [[static::class, 'validateIconClassElement']],
      '#required' => TRUE,
      '#maxlength' => 64,
    ];

    return $form;
  }

  /**
   * Form element validation handler for the 'icon_class' element.
   *
   * Disallows saving invalid class values.
   */
  public static function validateIconClassElement(array $element, FormStateInterface $form_state, array $form): void {
    $icon = $element['#value'];

    if (!preg_match('/^[a-z0-9_-]+$/', $icon)) {
      $form_state->setError($element, t('The machine-readable name must contain only lowercase letters, numbers, underscores and hyphens.'));
    }
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form): void {
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
  protected static function getUserEnteredStringAsUri($string):string {
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
  protected static function getUriAsDisplayableString($uri): string {
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
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['uri'] = $form_state->getValue('uri');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['icon_class'] = $form_state->getValue('icon_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->configuration;
    $build = [];
    // Ensure that user has access to link before rendering it.
    try {
      $url = Url::fromUri($config['uri']);
      // Internal routes must exist.
      if (!$url->isExternal() && !$url->isRouted()) {
        return $build;
      }
      $access = $url->access(NULL, TRUE);
      if (!$access->isAllowed()) {
        // Cacheable dependency is explicitly added when access is not granted.
        // It is bubbled when the link is rendered.
        $cacheable_metadata = new CacheableMetadata();
        $cacheable_metadata->addCacheableDependency($access);
        $cacheable_metadata->applyTo($build);
        return $build;
      }
    }
    catch (\InvalidArgumentException) {
      return $build;
    }

    return $build + [
      '#title' => $config['label'],
      '#theme' => 'navigation_menu',
      '#menu_name' => 'link',
      '#items' => [
        [
          'title' => $config['title'],
          'class' => $config['icon_class'],
          'url' => $url,
        ],
      ],
    ];
  }

}
