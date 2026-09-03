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
 * Whenever a method accepts CSS selectors, HTMX extends the selector syntax.
 * @see https://four.htmx.org/docs#targeting-elements
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
 * Inheritance in HTMX attributes
 *
 * When using the `:inherited` modifier, the HTMX attribute will be inherited by
 * child elements. This means that if an element has an HTMX attribute with the
 * `:inherited` modifier, any child elements without their own HTMX attribute
 * will inherit the parent's attribute.
 *
 * When using the `:append` modifier, the HTMX attribute value will be appended
 * to the value of that attribute on a parent element.
 *
 * When both of these modifiers are used together, the HTMX attribute will be
 * appended to the value inherited from the parent element.
 *
 * @see https://four.htmx.org/docs/whats-new-in-htmx-4#explicit-inheritance
 * @see https://four.htmx.org/docs#attribute-inheritance
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
   * A flag value declaring that the HTMX attribute has no modifier.
   */
  const NO_MODIFIER = 0b00;

  /**
   * A flag value declaring that the `:inherited` modifier should be appended.
   */
  const INHERITED = 0b01;

  /**
   * A flag value declaring that the `:append` modifier should be appended.
   */
  const APPEND = 0b10;

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
   * Helper method to generate a key with modifiers applied.
   *
   * @param string $id
   *   The base identifier for the key.
   * @param int $modifiers
   *   The modifiers to apply to the key.
   *
   * @return string
   *   The generated key with modifiers.
   */
  protected function keyWithModifiers(string $id, int $modifiers): string {
    $id = self::ATTRIBUTE_PREFIX . $id;
    if ($modifiers & self::INHERITED) {
      $id = $id . ':inherited';
    }
    if ($modifiers & self::APPEND) {
      $id = $id . ':append';
    }
    return $id;
  }

  /**
   * Utility method to create and store a string value as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param string $value
   *   The attribute value.
   * @param int $modifiers
   *   The modifiers to apply to the attribute id.
   */
  protected function createStringAttribute(string $id, string $value, int $modifiers): void {
    $key = $this->keyWithModifiers($id, $modifiers);
    $this->attributes[$key] = new AttributeString($key, $value);
  }

  /**
   * Utility method to create and store a boolean value as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param bool $value
   *   The attribute value.
   * @param int $modifiers
   *   The modifiers to apply to the attribute id.
   */
  protected function createBooleanAttribute(string $id, bool $value, int $modifiers): void {
    $key = $this->keyWithModifiers($id, $modifiers);
    $this->attributes[$key] = new AttributeBoolean($key, $value);

  }

  /**
   * Utility method to create and store an array as an attribute.
   *
   * @param string $id
   *   The HTMX attribute id.
   * @param array<string, string|int|bool> $value
   *   The attribute values.
   * @param int $modifiers
   *   The modifiers to apply to the attribute id.
   */
  protected function createJsonAttribute(string $id, array $value, int $modifiers): void {
    $key = $this->keyWithModifiers($id, $modifiers);

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
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   */
  protected function buildRequestAttribute(string $method, ?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    if (is_null($url)) {
      $request_url = Url::fromRoute('<none>');
    }
    else {
      // The Htmx helper should not modify the original URL object.
      $request_url = clone $url;
    }
    $this->createStringAttribute($method, $this->urlValue($request_url), $modifiers);
    return $this;
  }

  /**
   * Decides when to use the `drupal_htmx` wrapper format for Htmx requests.
   *
   * @param bool $toggle
   *   Toggle to use the full HTML response or just the main content.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see core/misc/htmx/htmx-assets.js
   */
  public function onlyMainContent(bool $toggle = TRUE): static {
    $this->createBooleanAttribute('hx-drupal-only-main-content', $toggle, self::NO_MODIFIER);
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
   * @see https://four.htmx.org/reference/headers/
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
   * @see https://four.htmx.org/reference/headers/HX-Push-Url
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
   * @see https://four.htmx.org/reference/headers/HX-Replace-Url
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
   * @see https://four.htmx.org/reference/headers/HX-Redirect
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
   * @see https://four.htmx.org/reference/headers/HX-Refresh
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
   * @see https://four.htmx.org/docs/whats-new-in-htmx-4#response-headers
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
   * @see https://four.htmx.org/docs/whats-new-in-htmx-4#response-headers
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
   * @see https://four.htmx.org/docs/whats-new-in-htmx-4#response-headers
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
   * @see https://four.htmx.org/reference/headers/HX-Trigger
   */
  public function triggerHeader(string|array $data): static {
    if (is_array($data)) {
      $data = json_encode($data);
    }
    $this->headers->set('HX-Trigger', $data);
    return $this;
  }

  /**
   * Creates a `data-hx-get` attribute.
   *
   * This attribute instructs HTMX to issue a GET request to the specified URL.
   *
   * This request method also accepts no parameters, which issues a GET
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the GET request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-get
   */
  public function get(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-get', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-post` attribute.
   *
   * This attribute instructs HTMX to issue a POST request to the specified URL.
   *
   * This request method also accepts no parameters, which issues a POST
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the POST request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-post/
   */
  public function post(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-post', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-put` attribute.
   *
   * This attribute instructs HTMX to issue a PUT request to the specified URL.
   *
   * This request method also accepts no parameters, which issues a PUT
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the PUT request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-put/
   */
  public function put(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-put', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-patch` attribute.
   *
   * This attribute instructs HTMX to issue a PATCH request.
   *
   * This request method also accepts no parameters, which issues a PATCH
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the PATCH request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-patch/
   */
  public function patch(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-patch', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-delete` attribute.
   *
   * This attribute instructs HTMX to issue a DELETE request
   * to the specified URL.
   *
   * This request method also accepts no parameters, which issues a DELETE
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the DELETE request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-delete/
   */
  public function delete(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-delete', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-query` attribute.
   *
   * This attribute instructs HTMX to issue a QUERY request
   * to the specified URL.
   *
   * This request method also accepts no parameters, which issues a QUERY
   * request to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the QUERY request. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the request. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-query/
   * @see https://www.rfc-editor.org/info/rfc10008/
   */
  public function query(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-query', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-action` attribute.
   *
   * The hx-action attribute specifies the URL that will receive the request.
   * It mirrors the native action attribute on forms, making it familiar
   * to HTML authors and enabling progressive enhancement.
   *
   * This method also accepts no parameters, which sets the action
   * to the current url. If parameters are used, both are required.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL for the action. If NULL, the current page is used without
   *   the query parameters.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @see https://four.htmx.org/reference/attributes/hx-action/
   */
  public function action(?Url $url = NULL, int $modifiers = self::NO_MODIFIER): static {
    return $this->buildRequestAttribute('hx-action', $url, $modifiers);
  }

  /**
   * Creates a `data-hx-method` attribute.
   *
   * The hx-method attribute specifies the HTTP method (verb) to use for the
   * request. It is typically paired with hx-action to separate the URL from
   * the method.
   *
   * @param string $method
   *   The HTTP method for the request. Accepted values are GET, POST, PUT,
   *   PATCH, and DELETE (case-insensitive).
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns self so that attribute methods may be chained.
   *
   * @throws \ValueError
   *   Thrown when the method is not one of the supported values.
   *
   * @see https://four.htmx.org/reference/attributes/hx-method/
   */
  public function method(string $method, int $modifiers = self::NO_MODIFIER): static {
    if (!in_array(mb_strtolower($method), ['get', 'post', 'put', 'patch', 'delete'], TRUE)) {
      throw new \ValueError('Invalid value for method');
    }
    $this->createStringAttribute('hx-method', $method, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-on` attribute.
   *
   * This attribute instructs HTMX to react to events with inline scripts
   * on elements.
   *
   * `data-hx-on` supports two forms:
   *
   * Single event, action pair:
   * - $event is the name of the JavaScript event.
   * - $action is the JavaScript statement to execute when the event occurs.
   *   This can be a short instruction or call a function in a script that
   *   has been loaded by the page.
   * This form results in `data-hx-on:event="action"` in the HTML.
   *
   * Extended event action pairs:
   * - $event is an associative array with event names and optional modifiers as
   *   keys and JavaScript statements as values.
   * - The $action parameter is ignored.
   * This form results in
   * `data-hx-on="event1 modifier -> action1; event2 -> action2"`
   * in the HTML.
   *
   * Example:
   * @code
   * (new Htmx())->on([
   *   'event1 modifier' => 'action1',
   *   'event2' => 'action2'
   * ])->applyTo($element);
   * @endcode
   *
   * HTMX provides a number of custom events which can be used in either form.
   * The extended form also supports a dozen event modifiers. Developers should
   * review the full documentation using the link below.
   *
   * @param string|array<string, string> $event
   *   HTMX event names are lowercase, colon-separated, of the pattern:
   *   htmx:phase:action[:sub-action].
   * @param string $action
   *   The JavaScript statement.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-on/
   */
  public function on(string|array $event, string $action = '', int $modifiers = self::NO_MODIFIER): static {
    // We either have an array of event strings with associated actions or
    // a single event string and a single action.
    if (is_array($event)) {
      $name = 'hx-on';
      $eventActionPairs = [];
      foreach ($event as $eventName => $value) {
        $eventActionPairs[] = $eventName . ' -> ' . $value;
      }
      $action = implode('; ', $eventActionPairs);
    }
    else {
      $event = str_starts_with($event, ':') ? $event : ':' . $event;
      $name = "hx-on$event";
    }

    $this->createStringAttribute($name, $action, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-push-url/
   */
  public function pushUrl(bool|Url $value, int $modifiers = self::NO_MODIFIER): static {
    $url = $value === FALSE ? 'false' : 'true';
    if ($value instanceof Url) {
      $url = $this->urlValue($value);
    }
    $this->createStringAttribute('hx-push-url', $url, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-select/
   */
  public function select(string $selector, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-select', $selector, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-select-oob/
   */
  public function selectOob(string|array $selectors, int $modifiers = self::NO_MODIFIER): static {
    if (is_array($selectors)) {
      $selectors = implode(',', $selectors);
    }
    $this->createStringAttribute('hx-select-oob', $selectors, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-status` attribute.
   *
   * This attribute allows different swap behavior to be configured per HTTP
   * response status code, overriding the element's default hx-swap
   * behavior for matching responses.
   *
   * The code may be an exact status code (e.g. "422"), a single-digit
   * wildcard (e.g. "50x"), or a range wildcard (e.g. "5xx").
   *
   * The strategy string supports the following configuration keys:
   * - swap: The swap strategy to use (see hx-swap).
   * - target: The target element (see hx-target).
   * - select: The selector to extract from the response (see hx-select).
   * - push: (boolean) Whether to push the request URL into history.
   * - replace: (boolean) Whether to replace the current URL in history.
   * - transition: (boolean) Whether to use the View Transition API.
   *
   * @param string $code
   *   The HTTP status code, or code wildcard, this configuration applies to.
   * @param string $strategy
   *   The status-code-specific configuration, expressed as space-separated
   *   key:value pairs (e.g. "swap:innerHTML target:#errors").
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-status
   */
  public function status(string $code, string $strategy, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute("hx-status:$code", $strategy, $modifiers);
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
   * @param int $attributeModifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-swap/
   */
  public function swap(string $strategy, string $modifiers = '', bool $ignoreTitle = TRUE, int $attributeModifiers = self::NO_MODIFIER): static {
    if ($modifiers !== '') {
      $strategy .= ' ' . $modifiers;
    }
    // HTMX defaults this behavior to FALSE, that is it replaces the page title.
    // We believe our most common use case is to not change the title.
    if ($ignoreTitle) {
      $strategy .= ' ignoreTitle:true';
    }
    $this->createStringAttribute('hx-swap', $strategy, $attributeModifiers);
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
   * @see https://four.htmx.org/reference/attributes/hx-swap-oob/
   */
  public function swapOob(true|string $value): static {
    if ($value === TRUE) {
      $value = 'true';
    }
    $this->createStringAttribute('hx-swap-oob', $value, self::NO_MODIFIER);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-target/
   */
  public function target(string $target, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-target', $target, $modifiers);
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
   * The definition should not be provided by untrusted users. A polling
   * definition with a very low interval could be used to cause a DDOS attack.
   *
   * @param string|string[] $triggerDefinition
   *   The trigger definition.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-trigger/
   */
  public function trigger(string|array $triggerDefinition, int $modifiers = self::NO_MODIFIER): static {
    if (is_array($triggerDefinition)) {
      $triggerDefinition = implode(',', $triggerDefinition);
    }
    $this->createStringAttribute('hx-trigger', $triggerDefinition, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-vals/
   */
  public function vals(array $values, int $modifiers = self::NO_MODIFIER): static {
    $this->createJsonAttribute('hx-vals', $values, $modifiers);
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
   * @see https://four.htmx.org/reference/attributes/hx-boost/
   */
  public function boost(bool $value = TRUE): static {
    $this->createStringAttribute('hx-boost', $value ? 'true' : 'false', self::NO_MODIFIER);
    return $this;
  }

  /**
   * Creates a `data-hx-confirm` attribute.
   *
   * This attribute instructs HTMX to show a window.confirm dialog before
   * issuing a request.
   *
   * @param string $message
   *   The user facing message.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-confirm
   */
  public function confirm(string $message, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-confirm', $message, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-ignore` attribute.
   *
   * This attribute instructs HTMX to disable HTMX processing for the given
   * node and any descendants.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-ignore
   */
  public function ignore(): static {
    $this->createBooleanAttribute('hx-ignore', TRUE, self::NO_MODIFIER);
    return $this;
  }

  /**
   * Creates a `data-hx-disable` attribute.
   *
   * This attribute instructs HTMX to add the disabled attribute to the
   * specified elements during a request.
   *
   * The descriptor syntax is the same as hx-target. See the documentation
   * link below for more details.
   *
   * @param string $descriptor
   *   The attribute value.
   * @param bool $merge
   *   Use the merge modifier to merge parent values for a disabled element.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-disable
   */
  public function disable(string $descriptor, bool $merge = FALSE, int $modifiers = self::NO_MODIFIER): static {
    $name = $merge ? 'hx-disable:merge' : 'hx-disable';
    $this->createStringAttribute($name, $descriptor, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-encoding` attribute.
   *
   * This attribute instructs HTMX to change the request encoding type.
   *
   * @param string $method
   *   The encoding method.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-encoding/
   */
  public function encoding(string $method = 'multipart/form-data', int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-encoding', $method, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-history-elt` attribute.
   *
   * The hx-history-elt attribute marks the element that htmx should swap when
   * restoring a page from history (back/forward navigation). Only this element
   * is replaced — the rest of the page is left untouched.
   *
   * As this attribute always marks a single element, modifiers are not
   * applicable.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/attributes/hx-history-elt/
   */
  public function historyElt(): static {
    $this->createBooleanAttribute('hx-history-elt', TRUE, self::NO_MODIFIER);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-headers/
   */
  public function headers(array $headerValues, int $modifiers = self::NO_MODIFIER): static {
    $this->createJsonAttribute('hx-headers', $headerValues, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-include/
   */
  public function include(string $descriptors, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-include', $descriptors, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-indicator/
   */
  public function indicator(string $selector, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-indicator', $selector, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-preserve` attribute.
   *
   * This attribute instructs HTMX that matching elements should be kept
   * unchanged between requests. Depends on an unchanging id property on the
   * element.
   *
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-preserve/
   */
  public function preserve(int $modifiers = self::NO_MODIFIER): static {
    $this->createBooleanAttribute('hx-preserve', TRUE, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-replace-url/
   */
  public function replaceUrl(bool|Url $value, int $modifiers = self::NO_MODIFIER): static {
    $url = $value ? 'true' : 'false';
    if ($value instanceof Url) {
      $url = $this->urlValue($value);
    }
    $this->createStringAttribute('hx-replace-url', $url, $modifiers);
    return $this;
  }

  /**
   * Creates a `data-hx-config` attribute.
   *
   *  The hx-config attribute configures behavior of the request.
   *
   * The hx-config attribute supports the following configuration values:
   * - timeout: (integer) Request timeout in milliseconds.
   * - credentials: (string) Fetch credentials mode: "omit", "same-origin",
   *   "include".
   * - cache: (string) Fetch cache mode: "default", "no-cache", "reload", etc.
   * - redirect: (string) Fetch redirect mode: "follow", "error", "manual".
   * - referrer: (string) Referrer URL or "no-referrer".
   * - integrity: (string) Sub-resource integrity value.
   * - validate: (boolean) Whether to validate form before submission.
   *
   * @param array<string, int|bool|string> $configValues
   *   The configuration values as name => value.
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-config/
   */
  public function config(array $configValues, int $modifiers = self::NO_MODIFIER): static {
    $this->createJsonAttribute('hx-config', $configValues, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-sync/
   */
  public function sync(string $selector, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-sync', $selector, $modifiers);
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
   * @param int $modifiers
   *   The modifiers to apply to the attribute. Defaults to no modifiers.
   *
   * @return static
   *   Returns this object to allow chaining methods.
   *
   * @see https://four.htmx.org/reference/attributes/hx-validate/
   */
  public function validate(bool $value = TRUE, int $modifiers = self::NO_MODIFIER): static {
    $this->createStringAttribute('hx-validate', $value ? 'true' : 'false', $modifiers);
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
