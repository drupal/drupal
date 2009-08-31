<?php
// $Id$

/**
 * @file
 * Browser API class.
 */

/**
 * @defgroup browser Browser
 * @{
 * Provides a powerful text based browser through a class based API.
 * The browser supports two HTTP backends natively: 1) PHP streams, and
 * 2) curl. The browser also supports arbitrary HTTP request types in addtion
 * to GET and POST, given that the backend supports them.
 *
 * The browser can be used to make a simple GET request to example.com as
 * shown below.
 * @code
 *   $browser = new Browser();
 *   $browser->get('http://example.com');
 * @endcode
 * The result of the GET request can be accessed in two ways: 1) the get()
 * method returns an array defining the result of the request, or 2) the
 * individual properties can be accessed from the browser instance via their
 * respective access methods. The following demonstrates the properties that
 * are avaialable and how to access them.
 * @code
 *   $browser->getUrl();
 *   $browser->getResponseHeaders();
 *   $browser->getContent();
 * @endcode
 *
 * When performing a POST request the following format is used.
 * @code
 *   $browser = new Browser();
 *   $post = array(
 *     'field_name1' => 'foo',
 *     'checkbox1' => TRUE,
 *     'multipleselect1[]' => array(
 *       'value1',
 *       'value2',
 *     ),
 *   );
 *   $browser->post('http://example.com/form', $post, 'Submit button text');
 * @endcode
 * To submit a multi-step form or to post to the current page the URL passed to
 * post() may be set to NULL. If there were two steps on the form shown in the
 * example above with the mutliple select field on the second page and a submit
 * button with the title "Next" on the first page the code be as follows.
 * @code
 *   $browser = new Browser();
 *   $post = array(
 *     'field_name1' => 'foo',
 *     'checkbox1' => TRUE,
 *   );
 *   $browser->post('http://example.com/form', $post, 'Next');
 *
 *   $post = array(
 *     'multipleselect1[]' => array(
 *       'value1',
 *       'value2',
 *     ),
 *   );
 *   $browser->post(NULL, $post, 'Final');
 * @endcode
 */

/**
 * Browser API class.
 *
 * All browser functionality is provided by this main class which manages the
 * various aspects of the browser.
 */
class Browser {

  /**
   * Flag indicating if curl is available.
   *
   * @var boolean
   */
  protected $curl;

  /**
   * The handle of the current curl connection.
   *
   * @var resource
   */
  protected $handle;

  /**
   * The current cookie file used by curl.
   *
   * Cookies are not reused so they can be stored in memory instead of a file.
   *
   * @var mixed
   */
  protected $cookieFile = NULL;

  /**
   * The request headers.
   *
   * @var array
   */
  protected $requestHeaders = array();

  /**
   * The URL of the current page.
   *
   * @var string
   */
  protected $url;

  /**
   * The response headers of the current page.
   *
   * @var Array
   */
  protected $headers = array();

  /**
   * The raw content of the current page.
   *
   * @var string
   */
  protected $content;

  /**
   * The BrowserPage class representing to the current page.
   *
   * @var BrowserPage
   */
  protected $page;

  /**
   * Initialize the browser.
   *
   * @param $force_stream
   *   Force the use of the PHP stream wrappers insead of CURL. This is used
   *   during testing to force the use of the stream wrapper so it can be
   *   tested.
   */
  public function __construct($force_stream = FALSE) {
    $this->curl = $force_stream ? FALSE : function_exists('curl_init');
    $this->setUserAgent('Drupal (+http://drupal.org/)');

    if ($this->curl) {
      $this->handle = curl_init();
      curl_setopt_array($this->handle, $this->curlOptions());
    }
    else {
      $this->handle = stream_context_create();
    }
  }

  /**
   * Check the the method is supported by the backend.
   *
   * @param $method
   *   The method string identifier.
   */
  public function isMethodSupported($method) {
    return $method == 'GET' || $method == 'POST';
  }

  /**
   * Get the request headers.
   *
   * The request headers are sent in every request made by the browser with a
   * few changes made the the individual request methods.
   *
   * @return
   *   Associative array of request headers.
   */
  public function getRequestHeaders() {
    return $this->requestHeaders;
  }

  /**
   * Set the request headers.
   *
   * @param $headers
   *   Associative array of request headers.
   */
  public function setRequestHeaders(array $headers) {
    $this->requestHeaders = $headers;
  }

  /**
   * Get the user-agent that the browser is identifying itself as.
   *
   * @return
   *   Browser user-agent.
   */
  public function getUserAgent() {
    return $this->requestHeaders['User-Agent'];
  }

  /**
   * Set the user-agent that the browser will identify itself as.
   *
   * @param $agent
   *   User-agent to to identify as.
   */
  public function setUserAgent($agent) {
    $this->requestHeaders['User-Agent'] = $agent;
  }

  /**
   * Get HTTP authentication information.
   *
   * @return
   *   Authentication information in the format, username:password.
   */
  public function getHttpAuthentication() {
    if (isset($this->requestHeaders['Authorization'])) {
      return base64_decode($this->requestHeaders['Authorization']);
    }
    return NULL;
  }

  /**
   * Set HTTP authentication information.
   *
   * @param $username
   *   HTTP authentication username, which cannot contain a ":".
   * @param $password
   *   HTTP authentication password.
   */
  public function setHttpAuthentication($username, $password) {
    $this->requestHeaders['Authorization'] = base64_encode("$username:$password");
  }

  /**
   * Get the URL of the current page.
   *
   * @return
   *   The URL of the current page.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Get the response headers of the current page.
   *
   * @return
   *   The response headers of the current page.
   */
  public function getResponseHeaders() {
    return $this->headers;
  }

  /**
   * Get the raw content of the current page.
   *
   * @return
   *   The raw content for the current page.
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Get the BrowserPage instance for the current page.
   *
   * If the raw content is new and the page has not yet been parsed then parse
   * the content and ensure that it is valid.
   *
   * @return
   *   BrowserPage instance for the current page.
   */
  public function getPage() {
    if (!isset($this->page)) {
      $this->page = new BrowserPage($this->url, $this->headers, $this->content);
    }
    return $this->page;
  }

  /**
   * Get the current state of the browser.
   *
   * @return
   *   An associative array containing state information, including: 1) url, 2)
   *   headers, 3) content.
   * @see getUrl()
   * @see getResponseHeaders()
   * @see getContent()
   */
  public function getState() {
    return array(
      'url' => $this->url,
      'headers' => $this->headers,
      'content' => $this->content,
    );
  }

  /**
   * Set the state of the browser.
   *
   * @param $url
   *   The URL of the current page.
   * @param $headers
   *   The response headers of the current page.
   * @param $content
   *   The raw content of the current page.
   */
  public function setState($url, $headers, $content) {
    $this->url = $url;
    $this->headers = $headers;
    $this->content = $content;

    // Clear the page variable since the content has change.
    unset($this->page);

    $this->refreshCheck();
  }

  /**
   * Perform a GET request.
   *
   * @param $url
   *   Absolute URL to request.
   * @return
   *   Associative array of state information, as returned by getState().
   * @see getState().
   */
  public function get($url) {
    if ($this->curl) {
      $this->curlExecute(array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_URL => $url,
        CURLOPT_NOBODY => FALSE,
      ));
    }
    else {
      $this->streamExecute($url, array(
        'method' => 'GET',
        'header'  => array(
          'Content-Type' => 'application/x-www-form-urlencoded',
        ),
      ));
    }

    $this->refreshCheck();

    return $this->getState();
  }

  /**
   * Perform a POST request.
   *
   * @param $url
   *   Absolute URL to request, or NULL to submit the current page.
   * @param $fields
   *   Associative array of fields to submit as POST variables.
   * @param $submit
   *   Text contained in 'value' properly of submit button of which to press.
   * @return
   *   Associative array of state information, as returned by
   *   browser_state_get().
   * @see browser_state_get()
   */
  public function post($url, array $fields, $submit) {
    // If URL is set then request the page, otherwise use the current page.
    if ($url) {
      $this->get($url);
    }
    else {
      $url = $this->url;
    }

    if (($page = $this->getPage()) === FALSE) {
      return FALSE;
    }

    if (($form = $this->findForm($fields, $submit)) === FALSE) {
      return FALSE;
    }

    // If form specified action then use that for the post url.
    if ($form['action']) {
      $url = $page->getAbsoluteUrl($form['action']);
    }

    if ($this->curl) {
      $this->curlExecute(array(
        CURLOPT_POST => TRUE,
        CURLOPT_URL => $url,
        CURLOPT_POSTFIELDS => http_build_query($form['post'], NULL, '&'),
      ));
    }
    else {
      $this->streamExecute($url, array(
        'method'  => 'POST',
        'header'  => array(
          'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'content' => http_build_query($form['post'], NULL, '&'),
      ));
    }

    $this->refreshCheck();

    return $this->getState();
  }

  /**
   * Find the the form that patches the conditions.
   *
   * @param $fields
   *   Associative array of fields to submit as POST variables.
   * @param $submit
   *   Text contained in 'value' properly of submit button of which to press.
   * @return
   *   Form action and the complete post array containing default values if not
   *   overridden, or FALSE if no form matching the conditions was found.
   */
  protected function findForm(array $fields, $submit) {
    $page = $this->getPage();

    $forms = $page->getForms();
    foreach ($forms as $form) {
      if (($post = $this->processForm($form, $fields, $submit)) !== FALSE) {
        $action = (isset($form['action']) ? (string) $form['action'] : FALSE);
        return array(
          'action' => $action,
          'post' => $post,
        );
      }
    }
    return FALSE;
  }

  /**
   * Check the conditions against the specified form and process values.
   *
   * @param $form
   *   Form SimpleXMLElement object.
   * @param $fields
   *   Associative array of fields to submit as POST variables.
   * @param $submit
   *   Text contained in 'value' properly of submit button of which to press.
   * @return
   *   The complete post array containing default values if not overridden, or
   *   FALSE if no form matching the conditions was found.
   */
  protected function processForm($form, $fields, $submit) {
    $page = $this->getPage();

    $post = array();
    $submit_found = FALSE;
    $inputs = $page->getInputs($form);
    foreach ($inputs as $input) {
      $name = (string) $input['name'];
      $html_value = isset($input['value']) ? (string) $input['value'] : '';

      // Get type from input vs textarea and select.
      $type = isset($input['type']) ? (string) $input['type'] : $input->getName();

      if (isset($fields[$name])) {
        if ($type == 'file') {
          // Make sure the file path is the absolute path.
          $file = realpath($fields[$name]);
          if ($file && is_file($file)) {
            // Signify that the post field is a file in case backend needs to
            // perform additional processing.
            $post[$name] = '@' . $file;
          }
          // Known type, field processed.
          unset($fields[$name]);
        }
        elseif (($processed_value = $this->processField($input, $type, $fields[$name], $html_value)) !== NULL) {
          // Value may be ommitted (checkbox).
          if ($processed_value !== FALSE) {
            if (is_array($processed_value)) {
              $post += $processed_value;
            }
            else {
              $post[$name] = $processed_value;
            }
          }
          // Known type, field processed.
          unset($fields[$name]);
        }
      }

      // No post value for the field means that: no post field value specified,
      // the value does not match the field (checkbox, radio, select), or the
      // field is of an unknown type.
      if (!isset($post[$name])) {
        // No value specified so use default value (value in HTML).
        if (($default_value = $this->getDefaultFieldValue($input, $type, $html_value)) !== NULL) {
          $post[$name] = $default_value;
          unset($fields[$name]);
        }
      }

      // Check if the
      if (($type == 'submit' || $type == 'image') && $submit == $html_value) {
        $post[$name] = $html_value;
        $submit_found = TRUE;
      }
    }

    if ($submit_found) {
      return $post;
    }
    return FALSE;
  }

  /**
   * Get the value to be sent for the specified field.
   *
   * @param $input
   *   Input SimpleXMLElement object.
   * @param $type
   *   Input type: text, textarea, password, radio, checkbox, or select.
   * @param $new_value
   *   The new value to be assigned to the input.
   * @param $html_value
   *   The cleaned default value for the input from the HTML value.
   */
  protected function processField($input, $type, $new_value, $html_value) {
    switch ($type) {
      case 'text':
      case 'textarea':
      case 'password':
        return $new_value;
      case 'radio':
        if ($new_value == $html_value) {
          return $new_value;
        }
        return NULL;
      case 'checkbox':
        // If $new_value is set to FALSE then ommit checkbox value, otherwise
        // pass original value.
        if ($new_value === FALSE) {
          return FALSE;
        }
        return $html_value;
      case 'select':
        // Remove the ending [] from multi-select element name.
        $key = preg_replace('/\[\]$/', '', (string) $input['name']);

        $options = $page->getSelectOptions($input);
        $index = 0;
        $out = array();
        foreach ($options as $value => $text) {
          if (is_array($value)) {
            if (in_array($value, $new_value)) {
              $out[$key . '[' . $index++ . ']'] = $value;
            }
          }
          elseif ($new_value == $value) {
            return $new_value;
          }
        }
        return ($out ? $out : NULL);
      default:
        return NULL;
    }
  }

  /**
   * Get the cleaned default value for the input from the HTML value.
   *
   * @param $input
   *   Input SimpleXMLElement object.
   * @param $type
   *   Input type: text, textarea, password, radio, checkbox, or select.
   * @param $html_value
   *   The default value for the input, as specified in the HTML.
   */
  protected function getDefaultFieldValue($input, $type, $html_value) {
    switch ($type) {
      case 'textarea':
        return (string) $input;
      case 'select':
        // Remove the ending [] from multi-select element name.
        $key = preg_replace('/\[\]$/', '', (string) $input['name']);
        $single = empty($input['multiple']);

        $options = $page->getSelectOptionElements($input);
        $first = TRUE;
        $index = 0;
        $out = array();
        foreach ($options as $option) {
          // For single select, we load the first option, if there is a
          // selected option that will overwrite it later.
          if ($option['selected'] || ($first && $single)) {
            $first = FALSE;
            if ($single) {
              $out[$key] = (string) $option['value'];
            }
            else {
              $out[$key . '[' . $index++ . ']'] = (string) $option['value'];
            }
          }
          return ($single ? $out[$key] : $out);
        }
        break;
      case 'file':
        return NULL;
      case 'radio':
      case 'checkbox':
        if (!isset($input['checked'])) {
          return NULL;
        }
        // Deliberately no break.
      default:
        return $html_value;
    }
  }

  /**
   * Perform a request of arbitrary type.
   *
   * Please use get() and post() for GET and POST requests respectively.
   *
   * @param $method
   *   The method string identifier.
   * @param $url
   *   Absolute URL to request.
   * @param $additional
   *   Additional parameters related to the particular request method.
   * @return
   *   Associative array of state information, as returned by getState().
   * @see getState().
   */
  public function request($method, $url, array $additional) {
    if (!$this->isMethodSupported($method)) {
      return FALSE;
    }

    // TODO
  }

  /**
   * Perform the request using the PHP stream wrapper.
   *
   * @param $url
   *   The url to request.
   * @param $options
   *   The HTTP stream context options to be passed to
   *   stream_context_set_params().
   */
  protected function streamExecute($url, array $options) {
    // Global variable provided by PHP stream wapper.
    global $http_response_header;

    if (!isset($options['header'])) {
      $options['header'] = array();
    }

    // Merge default request headers with the passed headers and generate
    // header string to be sent in http request.
    $headers = $this->requestHeaders + $options['header'];
    $options['header'] = $this->headerString($headers);

    // Update the handler options.
    stream_context_set_params($this->handle, array(
      'options' => array(
        'http' => $options,
      )
    ));

    // Make the request.
    $this->content = file_get_contents($url, FALSE, $this->handle);
    $this->url = $url;
    $this->headers = $this->headerParseAll($http_response_header);
    unset($this->page);
  }


  /**
   * Perform curl_exec() with the specified option changes.
   *
   * @param $options
   *   Curl options to set, any options not set will maintain their previous
   *   value.
   */
  function curlExecute(array $options) {
    // Headers need to be reset since callback appends.
    $this->headers = array();

    // Ensure that request headers are up to date.
    if ($this->getHttpAuthentication()) {
      curl_setopt($this->handle, CURLOPT_USERPWD, $this->getHttpAuthentication());
    }
    curl_setopt($this->handle, CURLOPT_USERAGENT, $this->requestHeaders['User-Agent']);
    curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->requestHeaders);

    curl_setopt_array($this->handle, $options);
    $this->content = curl_exec($this->handle);
    $this->url = curl_getinfo($this->handle, CURLINFO_EFFECTIVE_URL);

    // $this->headers should be filled by $this->curlHeaderCallback().
    unset($this->page);
  }

  /**
   * Get the default curl options to be used with each request.
   *
   * @return
   *   Default curl options.
   */
  protected function curlOptions() {
    return array(
      CURLOPT_COOKIEJAR => $this->cookieFile,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HEADERFUNCTION => array($this, 'curlHeaderCallback'),
      CURLOPT_HTTPHEADER => $this->requestHeaders,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_URL => '/',
      CURLOPT_USERAGENT => $this->requestHeaders['User-Agent'],
    );
  }

  /**
   * Reads reponse headers and stores in $headers array.
   *
   * @param $curlHandler
   *   The curl handler.
   * @param $header
   *   An header.
   * @return
   *   The string length of the header. (required by curl)
   */
  protected function curlHeaderCallback($handler, $header) {
    // Ignore blank header lines.
    $clean_header = trim($header);
    if ($clean_header) {
      $this->headers += $this->headerParse($clean_header);
    }

    // Curl requires strlen() to be returned.
    return strlen($header);
  }

  /**
   * Generate a header string given he associative array of headers.
   *
   * @param $headers
   *   Associative array of headers.
   * @return
   *   Header string to be used with stream.
   */
  protected function headerString(array $headers) {
    $string = '';
    foreach ($headers as $key => $header) {
      $string .= "$key: $header\r\n";
    }
    return $string;
  }

  /**
   * Parse the response header array to create an associative array.
   *
   * @param $headers
   *   Array of headers.
   * @return
   *   An associative array of headers.
   */
  protected function headerParseAll(array $headers) {
    $out = array();
    foreach ($headers as $header) {
      $out += $this->headerParse($header);
    }
    return $out;
  }

  /**
   * Parse an individual header into name and value.
   *
   * @param $header
   *   A string header string.
   * @return
   *   Parsed header as array($name => $value), or array() if parse failed.
   */
  protected function headerParse($header) {
    $parts = explode(':', $header, 2);

    // Ensure header line is valid.
    if (count($parts) == 2) {
      $name = $this->headerName(trim($parts[0]));
      return array($name => trim($parts[1]));
    }
    return array();
  }

  /**
   * Ensure that header name is formatted with all lowercase letters.
   *
   * @param $name
   *   Header name to format.
   * @return
   *   Formatted header name.
   */
  protected function headerName($name) {
    return strtolower($name);
  }

  /**
   * Check for a refresh signifier.
   *
   * A refresh signifier can either be the 'Location' HTTP header or the meta
   * tag 'http-equiv="Refresh"'.
   */
  protected function refreshCheck() {
    // If not handled by backend wrapper then go ahead and handle.
    if (isset($this->headers['Location'])) {
      // Expect absolute URL.
      $this->get($this->headers['Location']);
    }

    if (($page = $this->getPage()) !== FALSE && ($tag = $page->getMetaTag('Refresh', 'http-equiv'))) {
      // Parse the content attribute of the meta tag for the format:
      // "[delay]: URL=[path_to_redirect_to]".
      if (preg_match('/\d+;\s*URL=(?P<url>.*)/i', $tag['content'], $match)) {
        $this->get($page->getAbsoluteUrl(decode_entities($match['url'])));
      }
    }
  }

  /**
   * Close the wrapper connection.
   */
  function __destruct() {
    if (isset($this->handle)) {
      if ($this->curl) {
        curl_close($this->handle);
      }
      unset($this->handle);
    }
  }
}


/**
 * Represents a page of content that has been fetched by the Browser. The class
 * provides a number of convenience methods that relate to page content.
 */
class BrowserPage {

  /**
   * The URL of the page.
   *
   * @var string
   */
  protected $url;

  /**
   * The response headers of the  page.
   *
   * @var Array
   */
  protected $headers;

  /**
   * The root element of the page.
   *
   * @var SimpleXMLElement
   */
  protected $root;

  /**
   * Initialize the BrowserPage with the page state information.
   *
   * @param $url
   *   The URL of the page.
   * @param $headers
   *   The response headers of the page.
   * @param $content
   *   The raw content of the page.
   */
  public function BrowserPage($url, $headers, $content) {
    $this->url = $url;
    $this->headers = $headers;
    $this->root = $this->load($content);
  }

  /**
   * Attempt to parse the raw content using DOM and import it into SimpleXML.
   *
   * @param $content
   *   The raw content of the page.
   * @return
   *   The root element of the page, or FALSE.
   */
  protected function load($content) {
    // Use DOM to load HTML soup, and hide warnings.
    $document = @DOMDocument::loadHTML($content);
    if ($document) {
      return simplexml_import_dom($document);
    }
    return FALSE;
  }

  /**
   * Check if the raw content is valid and could be parse.
   *
   * @return
   *   TRUE if content is valid, otherwise FALSE.
   */
  public function isValid() {
    return ($this->root !== FALSE);
  }

  /**
   * Perform an xpath search on the contents of the page.
   *
   * The search is relative to the root element, usually the HTML tag, of the
   * page. To perform a search using a different root element follow the
   * example below.
   * @code
   *   $parent = $page->xpath('.//parent');
   *   $parent[0]->xpath('//children');
   * @endcode
   *
   * @param $xpath
   *   The xpath string.
   * @return
   *   An array of SimpleXMLElement objects or FALSE in case of an error.
   * @link http://us.php.net/manual/function.simplexml-element-xpath.php
   */
  public function xpath($xpath) {
    if ($this->isValid()) {
      return $this->root->xpath($xpath);
    }
    return FALSE;
  }

  /**
   * Get all the meta tags.
   *
   * @return
   *   An array of SimpleXMLElement objects representing meta tags.
   */
  public function getMetaTags() {
    return $this->xpath('//meta');
  }

  /**
   * Get a specific meta tag.
   *
   * @param $key
   *   The meta tag key.
   * @param $type
   *   The type of meta tag, either: 'name' or 'http-equiv'.
   * @return
   *   A SimpleXMLElement object representing the meta tag, or FALSE if not
   *   found.
   */
  public function getMetaTag($key, $type = 'name') {
    if ($tags = $this->getMetaTags()) {
      foreach ($tags as $tag) {
        if ($tag[$type] == $key) {
          return $tag;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get all the form elements.
   *
   * @return
   *   An array of SimpleXMLElement objects representing form elements.
   */
  public function getForms() {
    return $this->xpath('//form');
  }

  /**
   * Get all the input elements, or only those nested within a parent element.
   *
   * @param $parent
   *   SimpleXMLElement representing the parent to search within.
   * @return
   *   An array of SimpleXMLElement objects representing form elements.
   */
  public function getInputs($parent = NULL) {
    if ($parent) {
      return $parent->xpath('.//input|.//textarea|.//select');
    }
    return $this->xpath('.//input|.//textarea|.//select');
  }

  /**
   * Get all the options contained by a select, including nested options.
   *
   * @param $select
   *   SimpleXMLElement representing the select to extract option from.
   * @return
   *   Associative array where the keys represent each option value and the
   *   value is the text contained within the option tag. For example:
   * @code
   *   array(
   *     'option1' => 'Option 1',
   *     'option2' => 'Option 2',
   *   )
   * @endcode
   */
  public function getSelectOptions(SimpleXMLElement $select) {
    $elements = $this->getSelectOptionElements($select);

    $options = array();
    foreach ($elements as $element) {
      $options[(string) $element['value']] = $this->asText($element);
    }
    return $options;
  }

  /**
   * Get all selected options contained by a select, including nested options.
   *
   * @param $select
   *   SimpleXMLElement representing the select to extract option from.
   * @return
   *   Associative array of selected items in the format described by
   *   BrowserPage->getSelectOptions().
   * @see BrowserPage->getSelectOptions()
   */
  public function getSelectedOptions(SimpleXMLElement $select) {
    $elements = getSelectOptionElements($select);

    $options = array();
    foreach ($elements as $element) {
      if (isset($elements['selected'])) {
        $options[(string) $element['value']] = asText($element);
      }
    }
    return $options;
  }

  /**
   * Get all the options contained by a select, including nested options.
   *
   * @param $element
   *   SimpleXMLElement representing the select to extract option from.
   * @return
   *   An array of SimpleXMLElement objects representing option elements.
   */
  public function getSelectOptionElements(SimpleXMLElement $element) {
    $options = array();

    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = $option;
    }

    // Search option group children.
    if (isset($element->optgroup)) {
      foreach ($element->optgroup as $group) {
        $options = array_merge($options, $this->getSelectOptionElements($group));
      }
    }
    return $options;
  }

  /**
   * Get the absolute URL for a given path, relative to the page.
   *
   * @param
   *   A path relative to the page or absolute.
   * @return
   *   An absolute path.
   */
  public function getAbsoluteUrl($path) {
    $parts = @parse_url($path);
    if (isset($parts['scheme'])) {
      return $path;
    }

    $base = $this->getBaseUrl();
    if ($path[0] == '/') {
      // Lead / then use host as base.
      $parts = parse_url($base);
      $base = $parts['scheme'] . '://' . $parts['host'];
    }
    return $base . $path;
  }

  /**
   * Get the base URL of the page.
   *
   * If a 'base' HTML element is defined then the URL it defines is used as the
   * base URL for the page, otherwise the page URL is used to determine the
   * base URL.
   *
   * @return
   *   The base URL of the page.
   */
  public function getBaseUrl() {
    // Check for base element.
    $elements = $this->xpath('.//base');
    if ($elements) {
      // More than one may be specified.
      foreach ($elements as $element) {
        if (isset($element['href'])) {
          $base = (string) $element['href'];
          break;
        }
      }
    }
    else {
      $base = $this->url;
      if ($pos = strpos($base, '?')) {
        // Remove query string.
        $base = substr($base, 0, $pos);
      }

      // Ignore everything after the last forward slash.
      $base = substr($base, 0, strrpos($base, '/'));
    }

    // Ensure that the last character is a forward slash.
    if ($base[strlen($base) - 1] != '/') {
      $base .= '/';
    }
    return $base;
  }

  /**
   * Extract the text contained by the element.
   *
   * Strips all XML/HTML tags, decodes HTML entities, and trims the result.
   *
   * @param $element
   *   SimpleXMLElement to extract text from.
   * @return
   *   Extracted text.
   */
  public function asText(SimpleXMLElement $element) {
    return trim(html_entity_decode(strip_tags($element->asXML())));
  }
}

/**
 * @} End of "defgroup browser".
 */
