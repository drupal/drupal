<?php

declare(strict_types=1);

namespace Drupal\Core\Htmx;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\AttributeBoolean;
use Drupal\Core\Template\AttributeHelper;
use Drupal\Core\Template\AttributeString;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\HeaderBag;
use function Symfony\Component\String\u;

/**
 * Presents the HTMX controls for developers to use with render arrays.
 *
 * HTMX is designed as an extension of HTML. It is therefore a declarative
 * markup system that uses attributes.
 *
 * @code
 * <button hx-get="/contacts/1" hx-target="#contact-ui"> <1>
 *   Fetch Contact
 * </button>
 * @endcode
 *
 * HTMX is just as happy with `data-hx-get` and so we will
 * maintain standard markup in our implementation.
 *
 * HTMX uses over 30 such attributes. The other control surface for HTMX is a
 * set of response headers. HTMX supports 11 custom response headers.
 *
 * For example, to make a select element interactive so that it will:
 *  - Send a POST request to the form URL.
 *  - Select the wrapper element of the new <select> element from the response.
 *  - Target the wrapper element of the current <select> in the rendered form
 *    for replacement.
 *  - Use the outerHTML strategy, which is to replace the whole tag.
 *
 * @code
 * use Drupal\Core\Htmx\Htmx;
 * use Drupal\Core\Url;
 *
 * $form['config_type'] = [
 *   '#title' => $this->t('Configuration type'),
 *   '#type' => 'select',
 *   '#options' => $config_types,
 *   '#default_value' => $config_type,
 * ];
 *
 * $htmx = new Htmx();
 *
 * $htmx->post()
 *   ->select('*:has(>select[name="config_name"])')
 *   ->target('*:has(>select[name="config_name"])')
 *   ->swap('outerHTML');
 * $htmx->applyTo($form['config_type']);
 * }
 * @endcode
 *
 * To dynamically update the url in the browser using a response header when
 * the config_name selector is returned:
 *
 * @code
 *  if (!empty($default_type) && !empty($default_name)) {
 *    $push = Url::fromRoute('config.export_single', [
 *      'config_type' => $default_type,
 *      'config_name' => $default_name,
 *    ]);
 *    $htmx = new Htmx();
 *    $htmx->pushUrlHeader($push);
 *    $htmx->applyTo($form['config_name']);
 *  }
 * @endcode
 *
 * Whenever a method calls for a Url object, the cacheable metadata emitted by
 * rendering the object to string is also collected and merged to the render
 * array by the `::applyTo` method.
 *
 * A static method `Htmx::createFromRenderArray` is provided which
 * takes a render array as input and builds a new instance of Htmx with all
 * the HTMX specific attributes and headers loaded from the array.
 *
 * @see https://htmx.org/reference/
 * @see https://hypermedia.systems/book/contents/
 */
class Htmx {

  /**
   * All HTMX attributes begin with this string.
   */
  protected const string ATTRIBUTE_PREFIX = 'data-';

  /**
   * Initialize empty storage.
   *
   * Allows for passing a populated HeaderBag to support merging.
   */
  public function __construct(
    protected Attribute $attributes = new Attribute(),
    protected HeaderBag $headers = new HeaderBag(),
    protected CacheableMetadata $cacheableMetadata = new CacheableMetadata(),
  ) {
  }

  /**
   * Utility method to transform camelCase strings to kebab-case strings.
   *
   * Passes kebab-case strings through without any transformation.
   *
   * @param string $identifier
   *   The string to verify or transform.
   *
   * @return string
   *   The original or transformed string.
   */
  protected function ensureKebabCase(string $identifier): string {
    // Check for existing kebab case.
    $kebabParts = explode('-', $identifier);
    // If the number of lower case parts matches the number of parts, then
    // all the parts are lower case.
    $isKebab = count($kebabParts) === count(array_filter($kebabParts, function ($part) {
        return ctype_lower($part);
    }));
    if ($isKebab) {
      return $identifier;
    }
    return (string) u($identifier)->snake()->replaceMatches('#[_:]#', '-');
  }

  /**
   * Helper method to get the url string and store cache metadata.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to generate.
   *
   * @return string
   *   The url string.
   */
  protected function urlValue(Url $url): string {
    $generatedUrl = $url->toString(TRUE);
    $this->cacheableMetadata->addCacheableDependency($generatedUrl);
    return $generatedUrl->getGeneratedUrl();
  }

  /**
   * Utility method to create and store a string value as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param string $value
   *   The attribute value.
   */
  protected function createStringAttribute(string $id, string $value): void {
    $key = self::ATTRIBUTE_PREFIX . $id;
    $this->attributes[$key] = new AttributeString($key, $value);
  }

  /**
   * Utility method to create and store a boolean value as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param bool $value
   *   The attribute value.
   */
  protected function createBooleanAttribute(string $id, bool $value): void {
    $key = self::ATTRIBUTE_PREFIX . $id;
    $this->attributes[$key] = new AttributeBoolean($key, $value);

  }

  /**
   * Utility method to create and store an array as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param array<string, string|int|bool> $value
   *   The attribute values.
   */
  protected function createJsonAttribute(string $id, array $value): void {
    $key = self::ATTRIBUTE_PREFIX . $id;

    // Ensure the object format HTMX shows in documentation.
    // Ensure numeric strings are encoded as numbers.
    $json = json_encode($value, JSON_FORCE_OBJECT | JSON_NUMERIC_CHECK);
    $this->attributes[$key] = new AttributeString($key, $json);
  }

  /**
   * Utility function for the request attributes.
   *
   * Provides the logic for the request attribute methods.  Separate public
   * methods are maintained for clear correspondence with the attributes of
   * HTMX.
   *
   * @param string $method
   *   The request method.
   * @param \Drupal\Core\Url|null $url
   *   The URL for the request. If NULL, is passed it will use the current URL
   *   without any query parameter.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   */
  protected function buildRequestAttribute(string $method, ?Url $url = NULL): static {
    if (is_null($url)) {
      $request_url = Url::fromRoute('<none>');
    }
    else {
      // The Htmx helper should not modify the original URL object.
      $request_url = clone $url;
    }
    $this->createStringAttribute($method, $this->urlValue($request_url));
    return $this;
  }

  /**
   * Decides when to use the `drupal_htmx` wrapper format for Htmx requests.
   *
   * @param bool $toggle
   *   Toggle to use the full HTML response or just the main content.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see core/misc/htmx/htmx-assets.js
   */
  public function onlyMainContent(bool $toggle = TRUE) {
    $this->createBooleanAttribute('hx-drupal-only-main-content', $toggle);
    return $this;
  }

  /**
   * Apply the header values to the render array.
   */
  protected function applyHeaders(): array {
    $drupalHeaders = [];
    foreach ($this->headers as $name => $values) {
      foreach ($values as $value) {
        // Set replace to true.
        $drupalHeaders[] = [$name, $value, TRUE];
      }
    }
    return $drupalHeaders;
  }

  /**
   * Checks if a header is set.
   *
   * @param string $name
   *   The name of the header.
   *
   * @return bool
   *   True if header is stored.
   */
  public function hasHeader(string $name): bool {
    return $this->headers->has($name);
  }

  /**
   * Checks if an attribute is set.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return bool
   *   True if attribute is stored.
   */
  public function hasAttribute(string $name): bool {
    return $this->attributes->hasAttribute($name);
  }

  /**
   * Removes a header from the header store.
   *
   * @param string $name
   *   The header name to remove.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   */
  public function removeHeader(string $name): static {
    $this->headers->remove($name);
    return $this;
  }

  /**
   * Removes an attribute from the attribute store.
   *
   * @param string $name
   *   The attribute name to remove.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   */
  public function removeAttribute(string $name): static {
    $this->attributes->removeAttribute($name);
    return $this;
  }

  /**
   * Get the attribute storage.
   *
   * @return \Drupal\Core\Template\Attribute
   *   The attribute storage.
   */
  public function getAttributes(): Attribute {
    return $this->attributes;
  }

  /**
   * Get the header storage.
   *
   * @return \Symfony\Component\HttpFoundation\HeaderBag
   *   The header storage.
   */
  public function getHeaders(): HeaderBag {
    return $this->headers;
  }

  /**
   * Set HX-Location header.
   *
   * @param \Drupal\Core\Url|\Drupal\Core\Htmx\HtmxLocationResponseData $data
   *   Use Url if only a path is needed.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-location/
   */
  public function locationHeader(Url|HtmxLocationResponseData $data): static {
    if ($data instanceof HtmxLocationResponseData) {
      $value = (string) $data;
      $this->cacheableMetadata->addCacheableDependency($data->getCacheableMetadata());
    }
    else {
      $value = $this->urlValue($data);
    }
    $this->headers->set('HX-Location', $value);
    return $this;
  }

  /**
   * Set HX-Push-Url header.
   *
   * @param \Drupal\Core\Url|false $value
   *   URL to push to the location bar or false to prevent a history update.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-push-url/
   */
  public function pushUrlHeader(Url|false $value): static {
    $url = 'false';
    if ($value instanceof Url) {
      $url = $this->urlValue($value);
    }
    $this->headers->set('HX-Push-Url', $url);
    return $this;
  }

  /**
   * Set HX-Replace-Url header.
   *
   * @param \Drupal\Core\Url|false $data
   *   URL for history replacement, false  prevents updates to the current URL.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-replace-url/
   */
  public function replaceUrlHeader(Url|false $data): static {
    $value = 'false';
    if ($data instanceof Url) {
      $value = $this->urlValue($data);
    }
    $this->headers->set('HX-Replace-Url', $value);
    return $this;
  }

  /**
   * Set HX-Redirect header.
   *
   * @param \Drupal\Core\Url $url
   *   Destination for a client side redirection.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-redirect/
   */
  public function redirectHeader(Url $url): static {
    $this->headers->set('HX-Redirect', $this->urlValue($url));
    return $this;
  }

  /**
   * Set HX-Refresh header.
   *
   * @param bool $refresh
   *   If set to “true” the client-side will do a full refresh of the page.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/reference/#response_headers
   */
  public function refreshHeader(bool $refresh): static {
    $this->headers->set('HX-Refresh', $refresh ? 'true' : 'false');
    return $this;
  }

  /**
   * Set HX-Reswap header.
   *
   * @param string $strategy
   *   Specify how the response will be swapped (see hx-swap).
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/reference/#response_headers
   */
  public function reswapHeader(string $strategy): static {
    $this->headers->set('HX-Reswap', $strategy);
    return $this;
  }

  /**
   * Set HX-Retarget header.
   *
   * @param string $strategy
   *   CSS selector that replaces the target to a different element on the page.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/reference/#response_headers
   */
  public function retargetHeader(string $strategy): static {
    $this->headers->set('HX-Retarget', $strategy);
    return $this;
  }

  /**
   * Set HX-Reselect header.
   *
   * @param string $strategy
   *   CSS selector that changes the selection taken from the response.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/reference/#response_headers
   */
  public function reselectHeader(string $strategy): static {
    $this->headers->set('HX-Reselect', $strategy);
    return $this;
  }

  /**
   * Set HX-Trigger header.
   *
   * See the documentation for the structure of the array.
   *
   * @param string|array $data
   *   An event name or an array which will be JSON encoded.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-trigger/
   */
  public function triggerHeader(string|array $data): static {
    if (is_array($data)) {
      $data = json_encode($data);
    }
    $this->headers->set('HX-Trigger', $data);
    return $this;
  }

  /**
   * Set HX-Trigger-After-Settle header.
   *
   * See the documentation for the structure of the array.
   *
   * @param string|array $data
   *   An event name or an array which will be JSON encoded.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-trigger/
   */
  public function triggerAfterSettleHeader(string|array $data): static {
    if (is_array($data)) {
      $data = json_encode($data);
    }
    $this->headers->set('HX-Trigger-After-Settle', $data);
    return $this;
  }

  /**
   * Set HX-Trigger-After-Swap header.
   *
   * See the documentation for the structure of the array.
   *
   * @param string|array $data
   *   An event name or an array which will be JSON encoded.
   *
   * @return static
   *   Self for chaining.
   *
   * @see https://htmx.org/headers/hx-trigger/
   */
  public function triggerAfterSwapHeader(string|array $data): static {
    if (is_array($data)) {
      $data = json_encode($data);
    }
    $this->headers->set('HX-Trigger-After-Swap', $data);
    return $this;
  }

  /**
   * Creates a `data-hx-get` attribute.
   *
   * This attribute instructs HTMX to issue a GET request to the specified URL.
   *
   * This request method also accepts no parameters, which issues a get
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see https://htmx.org/attributes/hx-get/
   */
  public function get(?Url $url = NULL): static {
    return $this->buildRequestAttribute('hx-get', $url);
  }

  /**
   * Creates a `data-hx-post` attribute.
   *
   * This attribute instructs HTMX to issue a POST request to the specified URL.
   *
   *  This request method also accepts no parameters, which issues a post
   *  request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see https://htmx.org/attributes/hx-post/
   */
  public function post(?Url $url = NULL): static {
    return $this->buildRequestAttribute('hx-post', $url);
  }

  /**
   * Creates a `data-hx-put` attribute.
   *
   * This attribute instructs HTMX to issue a PUT request to the specified URL.
   *
   *  This request method also accepts no parameters, which issues a put
   *  request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see https://htmx.org/attributes/hx-put/
   */
  public function put(?Url $url = NULL): static {
    return $this->buildRequestAttribute('hx-put', $url);
  }

  /**
   * Creates a `data-hx-patch` attribute.
   *
   * This attribute instructs HTMX to issue a PATCH request.
   *
   *  This request method also accepts no parameters, which issues a patch
   *  request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see https://htmx.org/attributes/hx-patch/
   */
  public function patch(?Url $url = NULL): static {
    return $this->buildRequestAttribute('hx-patch', $url);
  }

  /**
   * Creates a `data-hx-delete` attribute.
   *
   * This attribute instructs HTMX to issue a DELETE request
   * to the specified URL.
   *
   *  This request method also accepts no parameters, which issues a delete
   *  request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   *
   * @return static
   *   returns self so that attribute methods may be chained.
   *
   * @see https://htmx.org/attributes/hx-delete/
   */
  public function delete(?Url $url = NULL): static {
    return $this->buildRequestAttribute('hx-delete', $url);
  }

  /**
   * Creates a `data-hx-on` attribute.
   *
   * This attribute instructs HTMX to react to events with inline scripts
   * on elements.
   * - $event is the name of the JavaScript event.
   * - $action is the JavaScript statement to execute when the event occurs.
   *   This can be a short instruction or call a function in a script that
   *   has been loaded by the page.
   *
   * @param string $event
   *   An event in either camelCase or kebab-case.
   * @param string $action
   *   The JavaScript statement.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-on/
   */
  public function on(string $event, string $action): static {
    // Special case: the `::EventName` shorthand for `htmx:EventName`.
    // Prepare a leading '-' so that our final attribute is
    // `data-hx--event-name` rather than `data-hx-event-name`.
    $extra = str_starts_with($event, '::') ? '-' : '';
    $formattedEvent = 'hx-on-' . $extra . $this->ensureKebabCase($event);
    $this->createStringAttribute($formattedEvent, $action);
    return $this;
  }

  /**
   * Creates a `data-hx-push-url` attribute.
   *
   * This attribute instructs HTMX to control URLs in the browser history.
   *
   * Use a boolean when this attribute is added along with ::get
   * - true: pushes the fetched URL into history.
   * - false: disables pushing the fetched URL if it would otherwise be pushed
   *   due to inheritance or hx-boost.
   *
   * Use a URL to cause a push into the location bar. This may be relative or
   * absolute, as per history.pushState()
   *
   * @param bool|\Drupal\Core\Url $value
   *   Use a Url object or a boolean, depending on the use case.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-push-url/
   */
  public function pushUrl(bool|Url $value): static {
    $url = $value === FALSE ? 'false' : 'true';
    if ($value instanceof Url) {
      $url = $this->urlValue($value);
    }
    $this->createStringAttribute('hx-push-url', $url);
    return $this;
  }

  /**
   * Creates a `data-hx-select` attribute.
   *
   * This attribute instructs HTMX which content to swap in from a response.
   * HTMX uses the given selector to select elements from the response.
   * For example, passing 'data-drupal-selector="edit-theme-settings"' will
   * instruct HTMX to select the element with this data attribute and value.
   *
   * @param string $selector
   *   A CSS selector string.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-select/
   */
  public function select(string $selector): static {
    $this->createStringAttribute('hx-select', $selector);
    return $this;
  }

  /**
   * Creates a `data-hx-select-oob` attribute.
   *
   * This attribute instructs HTMX to select content for an out-of-band swap
   * from a response. Each value can specify any valid hx-swap strategy by
   * separating the selector and the swap strategy with a colon,
   * such as #alert:afterbegin.
   *
   * @param string|string[] $selectors
   *   A value or array of values.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-select-oob/
   */
  public function selectOob(string|array $selectors): static {
    if (is_array($selectors)) {
      $selectors = implode(',', $selectors);
    }
    $this->createStringAttribute('hx-select-oob', $selectors);
    return $this;
  }

  /**
   * Creates a `data-hx-swap` attribute.
   *
   * This attribute allows you to specify how the response will be
   * swapped into the DOM relative to the target of an AJAX request.
   *
   * @param string $strategy
   *   The swap strategy.
   * @param string $modifiers
   *   Optional modifiers for changing the behavior of the swap.
   * @param bool $ignoreTitle
   *   Instruct HTMX not to swap in the page title from the request.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-swap/
   */
  public function swap(string $strategy, string $modifiers = '', bool $ignoreTitle = TRUE): static {
    if ($modifiers !== '') {
      $strategy .= ' ' . $modifiers;
    }
    // HTMX defaults this behavior to FALSE, that is it replaces the page title.
    // We believe our most common use case is to not change the title.
    if ($ignoreTitle) {
      $strategy .= ' ignoreTitle:true';
    }
    $this->createStringAttribute('hx-swap', $strategy);
    return $this;
  }

  /**
   * Creates a `data-hx-swap-oob` attribute.
   *
   * This attribute is used in the markup of the returned response. It
   * specifies that some content in a response should be swapped into the DOM
   * somewhere other than the target, that is “Out of Band”. This allows you to
   * piggyback updates to other elements on a response.
   *
   * @param true|string $value
   *   Either true, a swap strategy, or strategy:CSS-selector.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-swap-oob/
   */
  public function swapOob(true|string $value): static {
    if ($value === TRUE) {
      $value = 'true';
    }
    $this->createStringAttribute('hx-swap-oob', $value);
    return $this;
  }

  /**
   * Creates a `data-hx-target` attribute.
   *
   * This attribute allows you to target a different element for
   * swapping than the one issuing the AJAX request. There are a variety
   * of target string syntaxes.  See the URL below for details.
   *
   * @param string $target
   *   The target descriptor.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-target/
   */
  public function target(string $target): static {
    $this->createStringAttribute('hx-target', $target);
    return $this;
  }

  /**
   * Creates a `data-hx-trigger` attribute.
   *
   * This attribute instructs HTMX when to trigger a request.
   *
   * Used with an HTMX request attribute. Allows:
   * - An event name (e.g. “click” or “myCustomEvent”) followed by an event
   *   filter and a set of event modifiers
   * - A polling definition of the form every <timing declaration>
   * - A comma-separated list of such events.
   *
   * @param string|string[] $triggerDefinition
   *   The trigger definition.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-trigger/
   */
  public function trigger(string|array $triggerDefinition): static {
    if (is_array($triggerDefinition)) {
      $triggerDefinition = implode(',', $triggerDefinition);
    }
    $this->createStringAttribute('hx-trigger', $triggerDefinition);
    return $this;
  }

  /**
   * Creates a `data-hx-vals` attribute.
   *
   * This attribute instructs HTMX to add values to the parameters that will be
   * submitted with an HTMX request.
   *
   * The value of this attribute is a list of name-expression values
   * which will be converted to JSON (JavaScript Object Notation) format.
   *
   * @param array<string, string> $values
   *   The values in an array of 'name' => 'value' pairs.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-vals/
   */
  public function vals(array $values): static {
    $this->createJsonAttribute('hx-vals', $values);
    return $this;
  }

  /**
   * Creates a `data-hx-boost` attribute.
   *
   * This attribute instructs HTMX to add progressive enhancement
   * to links or forms. The attribute allows you to “boost” normal anchors and
   * form tags to use AJAX instead. This has the nice fallback that, if the
   * user does not have javascript enabled, the site will continue to work.
   *
   * @param bool $value
   *   Should the element and its descendants be "boosted"?
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-boost/
   */
  public function boost(bool $value = TRUE): static {
    $this->createStringAttribute('hx-boost', $value ? 'true' : 'false');
    return $this;
  }

  /**
   * Creates a `data-hx-confirm` attribute.
   *
   * This attribute instructs HTMX to shows a confirm() dialog before issuing
   * a request.
   *
   * @param string $message
   *   The user facing message.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-confirm/
   */
  public function confirm(string $message): static {
    $this->createStringAttribute('hx-confirm', $message);
    return $this;
  }

  /**
   * Creates a `data-hx-disable` attribute.
   *
   * This attribute instructs HTMX to disable HTMX processing for the given
   * node and any descendants.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-disable/
   */
  public function disable(): static {
    $this->createBooleanAttribute('hx-disable', TRUE);
    return $this;
  }

  /**
   * Creates a `data-hx-disabled-elt` attribute.
   *
   * This attribute instructs HTMX to add the disabled attribute to the
   * specified elements during a request.
   *
   * The descriptor syntax is the same as hx-target. See the documentation
   * link below for more details.
   *
   * @param string $descriptor
   *   The attribute value.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-disabled-elt/
   */
  public function disabledElt(string $descriptor): static {
    $this->createStringAttribute('hx-disabled-elt', $descriptor);
    return $this;
  }

  /**
   * Creates a `data-hx-disinherit` attribute.
   *
   * This attribute instructs HTMX to control and disable automatic HTMX
   * attribute inheritance for child nodes.
   *
   * @param string $names
   *   The attribute names to disinherit or * for all.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-disinherit/
   */
  public function disinherit(string $names): static {
    $this->createStringAttribute('hx-disinherit', $names);
    return $this;
  }

  /**
   * Creates a `data-hx-encoding` attribute.
   *
   * This attribute instructs HTMX to change the request encoding type.
   *
   * @param string $method
   *   The encoding method.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-encoding/
   */
  public function encoding(string $method = 'multipart/form-data'): static {
    $this->createStringAttribute('hx-encoding', $method);
    return $this;
  }

  /**
   * Creates a `data-hx-ext` attribute.
   *
   * This attribute instructs HTMX to enable HTMX extensions for an element
   * and descendants.
   *
   * @param string $names
   *   An extension name, or a comma separated list of names.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-ext/
   */
  public function ext(string $names): static {
    $this->createStringAttribute('hx-ext', $names);
    return $this;
  }

  /**
   * Creates a `data-hx-headers` attribute.
   *
   * This attribute instructs HTMX to add to the headers that will be submitted
   * with an HTMX request.
   *
   * @param array<string, string> $headerValues
   *   The header values as name => value.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-headers/
   */
  public function headers(array $headerValues): static {
    $this->createJsonAttribute('hx-headers', $headerValues);
    return $this;
  }

  /**
   * Creates a `data-hx-history` attribute.
   *
   * The attribute value is set to false. This attribute prevents sensitive
   * data from being saved to the localStorage cache when htmx takes a snapshot
   * of the page state. This attribute is effective when set on any element in
   * the current document, or any html fragment loaded into the current document
   * by htmx.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-history/
   */
  public function history(): static {
    $this->createStringAttribute('hx-history', 'false');
    return $this;
  }

  /**
   * Creates a `data-hx-history-elt` attribute.
   *
   * This attribute instructs HTMX which element to snapshot and restore
   * during history navigation.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-history-elt/
   */
  public function historyElt(): static {
    $this->createBooleanAttribute('hx-history-elt', TRUE);
    return $this;
  }

  /**
   * Creates a `data-hx-include` attribute.
   *
   * This attribute instructs HTMX to include additional element values
   * in HTMX requests.
   *
   * The descriptor syntax is the same as hx-target. See the documentation
   * link below for more details.
   *
   * @param string $descriptors
   *   The element descriptors.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-include/
   */
  public function include(string $descriptors): static {
    $this->createStringAttribute('hx-include', $descriptors);
    return $this;
  }

  /**
   * Creates a `data-hx-indicator` attribute.
   *
   * This attribute instructs HTMX which element should receive the
   * htmx-request class on during the request.
   *
   * @param string $selector
   *   The element CSS selector value. Selector may be prefixed with `closest`.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-indicator/
   */
  public function indicator(string $selector): static {
    $this->createStringAttribute('hx-indicator', $selector);
    return $this;
  }

  /**
   * Creates a `data-hx-inherit` attribute.
   *
   * This attribute instructs HTMX how to control automatic attribute
   * inheritance for child nodes.
   *
   * HTMX evaluates attribute inheritance with hx-inherit in two ways when
   * hx-inherit is set on a parent node:
   *  - data-hx-inherit="*"
   *    All attribute inheritance for this element will be enabled.
   * - data-hx-hx-inherit="hx-select hx-get hx-target"
   *   Enable inheritance for only one or multiple specified attributes.
   *
   * @param string $attributes
   *   The attributes to inherit.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-inherit/
   */
  public function inherit(string $attributes): static {
    $this->createStringAttribute('hx-inherit', $attributes);
    return $this;
  }

  /**
   * Creates a `data-hx-params` attribute.
   *
   * This attribute instructs HTMX to filter the parameters that will
   * be submitted with a request.
   *
   * Verify the current filter syntax at the link below.
   * - To include all parameters, use the string '*'.
   * - To pass no parameters use 'none'.
   * - To exclude some parameters use an array of parameters names, prefixing
   *   the first name with `not`, as in ['not param1', 'param2', 'param3'].
   * - To only submit some parameters, use an array of parameter names as in
   *   ['param1', 'param2', 'param3'].
   * parameters use an array, pre
   *
   * @param string|string[] $filter
   *   The filter string or strings.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-params/
   */
  public function params(string|array $filter): static {
    if (is_array($filter)) {
      $filter = implode(',', $filter);
    }
    $this->createStringAttribute('hx-params', $filter);
    return $this;
  }

  /**
   * Creates a `data-hx-preserve` attribute.
   *
   * This attribute instructs HTMX that matching elements should be kept
   * unchanged between requests. Depends on an unchanging id property on the
   * element.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-preserve/
   */
  public function preserve(): static {
    $this->createBooleanAttribute('hx-preserve', TRUE);
    return $this;
  }

  /**
   * Creates a `data-hx-prompt` attribute.
   *
   * This attribute instructs HTMX to show a prompt() before
   * submitting a request.
   *
   * @param string $message
   *   The message to display in the prompt.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-prompt/
   */
  public function prompt(string $message): static {
    $this->createStringAttribute('hx-prompt', $message);
    return $this;
  }

  /**
   * Creates a `data-hx-replace-url` attribute.
   *
   * This attribute instructs HTMX to control URLs in the browser location bar.
   *
   * Use a boolean when this attribute is added along with a request:
   * - true: replaces the fetched URL in the browser navigation bar.
   * - false: disables replacing the fetched URL if it would otherwise be
   *   replaced due to inheritance.
   *
   * Use a URL to replace the value in the location bar. This may be relative or
   * absolute, as per history.replaceState().
   *
   * @param bool|\Drupal\Core\Url $value
   *   A Url object, or a boolean, depending on the use case. See details above.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-replace-url/
   */
  public function replaceUrl(bool|Url $value): static {
    $url = $value ? 'true' : 'false';
    if ($value instanceof Url) {
      $url = $this->urlValue($value);
    }
    $this->createStringAttribute('hx-replace-url', $url);
    return $this;
  }

  /**
   * Creates a `data-hx-request` attribute.
   *
   *  This attribute instructs HTMX to configure various aspects of the request.
   *
   * The hx-request attribute supports the following configuration values:
   * - timeout: (integer) the timeout for the request, in milliseconds.
   * - credentials: (boolean) if the request will send credentials.
   * - noHeaders: (boolean) strips all headers from the request.
   *
   * Dynamic javascript values are not supported for security and for
   * simplicity.  If you need calculated values you should do determine them
   * here on the server-side
   *
   * @param array<string, int|bool> $configValues
   *   The configuration values as name => value.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-request/
   */
  public function request(array $configValues): static {
    $this->createJsonAttribute('hx-request', $configValues);
    return $this;
  }

  /**
   * Creates a `data-hx-sync` attribute.
   *
   * This attribute instructs HTMX to synchronize AJAX requests
   * between multiple elements.
   *
   * @param string $selector
   *   A CSS selector followed by a strategy.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-sync/
   */
  public function sync(string $selector): static {
    $this->createStringAttribute('hx-sync', $selector);
    return $this;
  }

  /**
   * Creates a `data-hx-validate` attribute.
   *
   * This attribute instructs HTMX to cause an element to validate itself
   * before it submits a request.
   *
   * @param bool $value
   *   Should the element validate before the request.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://htmx.org/attributes/hx-validate/
   */
  public function validate(bool $value = TRUE): static {
    $this->createStringAttribute('hx-validate', $value ? 'true' : 'false');
    return $this;
  }

  /**
   * Exports data from internal storage to a render array.
   *
   * @param mixed[] $element
   *   The render array for the element.
   * @param string $attributeKey
   *   Optional target key for attribute output: defaults to '#attributes'.
   */
  public function applyTo(array &$element, string $attributeKey = '#attributes'): void {
    // Attach HTMX and Drupal integration javascript.
    if (!in_array('core/drupal.htmx', $element['#attached']['library'] ?? [])) {
      $element['#attached']['library'][] = 'core/drupal.htmx';
    }

    // Consolidate headers.
    if ($this->headers->count() !== 0) {
      $element['#attached']['http_header'] = $element['#attached']['http_header'] ?? [];
      $element['#attached']['http_header'] = NestedArray::mergeDeep($element['#attached']['http_header'], $this->applyHeaders());
    }
    if (count($this->attributes->storage()) !== 0) {
      // Consolidate attributes.
      $element[$attributeKey] = $element[$attributeKey] ?? [];
      $element[$attributeKey] = AttributeHelper::mergeCollections($element[$attributeKey], $this->attributes);
    }
    $this->cacheableMetadata->applyTo($element);
  }

  /**
   * Creates an Htmx object with values taken from a render array.
   *
   * @param array $element
   *   A render array.
   * @param string $attributeKey
   *   Optional target key for attribute output: defaults to '#attributes'.
   *
   * @return static
   *   A new instance of this class.
   */
  public static function createFromRenderArray(array $element, string $attributeKey = '#attributes'): static {
    $incomingAttributes = $element[$attributeKey] ?? [];
    $incomingHeaders = $element['#attached']['http_header'] ?? [];
    // Filter for HTMX values.
    $incomingAttributes = array_filter(
      $incomingAttributes,
      function (string $key) {
        return str_starts_with($key, 'data-hx-');
      },
      ARRAY_FILTER_USE_KEY,
    );
    $preparedHeaders = [];
    foreach ($incomingHeaders as $value) {
      if (is_array($value) && str_starts_with($value[0], 'hx-')) {
        // Header value array may have 3 values, we want the first two.
        $preparedHeaders[$value[0]] = $value[1];
      }
    }
    $attributes = new Attribute($incomingAttributes);
    $headers = new HeaderBag($preparedHeaders);
    $cacheableMetadata = CacheableMetadata::createFromRenderArray($element);
    return new static($attributes, $headers, $cacheableMetadata);
  }

}
