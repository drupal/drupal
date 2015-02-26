<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Field\TextareaFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Client as HttpKernelClient;

/**
 * Symfony2 BrowserKit driver.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BrowserKitDriver extends CoreDriver
{
    private $session;
    private $client;

    /**
     * @var Form[]
     */
    private $forms = array();
    private $serverParameters = array();
    private $started = false;
    private $removeScriptFromUrl = false;
    private $removeHostFromUrl = false;

    /**
     * Initializes BrowserKit driver.
     *
     * @param Client      $client  BrowserKit client instance
     * @param string|null $baseUrl Base URL for HttpKernel clients
     */
    public function __construct(Client $client, $baseUrl = null)
    {
        $this->client = $client;
        $this->client->followRedirects(true);

        if ($baseUrl !== null && $client instanceof HttpKernelClient) {
            $client->setServerParameter('SCRIPT_FILENAME', parse_url($baseUrl, PHP_URL_PATH));
        }
    }

    /**
     * Returns BrowserKit HTTP client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Tells driver to remove hostname from URL.
     *
     * @param Boolean $remove
     *
     * @deprecated Deprecated as of 1.2, to be removed in 2.0. Pass the base url in the constructor instead.
     */
    public function setRemoveHostFromUrl($remove = true)
    {
        trigger_error(
            'setRemoveHostFromUrl() is deprecated as of 1.2 and will be removed in 2.0. Pass the base url in the constructor instead.',
            E_USER_DEPRECATED
        );
        $this->removeHostFromUrl = (bool) $remove;
    }

    /**
     * Tells driver to remove script name from URL.
     *
     * @param Boolean $remove
     *
     * @deprecated Deprecated as of 1.2, to be removed in 2.0. Pass the base url in the constructor instead.
     */
    public function setRemoveScriptFromUrl($remove = true)
    {
        trigger_error(
            'setRemoveScriptFromUrl() is deprecated as of 1.2 and will be removed in 2.0. Pass the base url in the constructor instead.',
            E_USER_DEPRECATED
        );
        $this->removeScriptFromUrl = (bool) $remove;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->reset();
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        // Restarting the client resets the cookies and the history
        $this->client->restart();
        $this->forms = array();
        $this->serverParameters = array();
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        $this->client->request('GET', $this->prepareUrl($url), array(), array(), $this->serverParameters);
        $this->forms = array();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        if (method_exists($this->client, 'getInternalRequest')) {
            $request = $this->client->getInternalRequest();
        } else {
            // BC layer for BrowserKit 2.2.x and older
            $request = $this->client->getRequest();

            if (null !== $request && !$request instanceof Request && !$request instanceof HttpFoundationRequest) {
                throw new DriverException(sprintf(
                    'The BrowserKit client returned an unsupported request implementation: %s. Please upgrade your BrowserKit package to 2.3 or newer.',
                    get_class($request)
                ));
            }
        }

        if ($request === null) {
            throw new DriverException('Unable to access the request before visiting a page');
        }

        return $request->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->client->reload();
        $this->forms = array();
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        $this->client->forward();
        $this->forms = array();
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        $this->client->back();
        $this->forms = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setBasicAuth($user, $password)
    {
        if (false === $user) {
            unset($this->serverParameters['PHP_AUTH_USER'], $this->serverParameters['PHP_AUTH_PW']);

            return;
        }

        $this->serverParameters['PHP_AUTH_USER'] = $user;
        $this->serverParameters['PHP_AUTH_PW'] = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestHeader($name, $value)
    {
        $contentHeaders = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);
        $name = str_replace('-', '_', strtoupper($name));

        // CONTENT_* are not prefixed with HTTP_ in PHP when building $_SERVER
        if (!isset($contentHeaders[$name])) {
            $name = 'HTTP_' . $name;
        }

        $this->serverParameters[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders()
    {
        return $this->getResponse()->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->deleteCookie($name);

            return;
        }

        $jar = $this->client->getCookieJar();
        $jar->set(new Cookie($name, $value));
    }

    /**
     * Deletes a cookie by name.
     *
     * @param string $name Cookie name.
     */
    private function deleteCookie($name)
    {
        $path = $this->getCookiePath();
        $jar = $this->client->getCookieJar();

        do {
            if (null !== $jar->get($name, $path)) {
                $jar->expire($name, $path);
            }

            $path = preg_replace('/.$/', '', $path);
        } while ($path);
    }

    /**
     * Returns current cookie path.
     *
     * @return string
     */
    private function getCookiePath()
    {
        $path = dirname(parse_url($this->getCurrentUrl(), PHP_URL_PATH));

        if ('\\' === DIRECTORY_SEPARATOR) {
            $path = str_replace('\\', '/', $path);
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        // Note that the following doesn't work well because
        // Symfony\Component\BrowserKit\CookieJar stores cookies by name,
        // path, AND domain and if you don't fill them all in correctly then
        // you won't get the value that you're expecting.
        //
        // $jar = $this->client->getCookieJar();
        //
        // if (null !== $cookie = $jar->get($name)) {
        //     return $cookie->getValue();
        // }

        $allValues = $this->client->getCookieJar()->allValues($this->getCurrentUrl());

        if (isset($allValues[$name])) {
            return $allValues[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->getResponse()->getStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->getResponse()->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function find($xpath)
    {
        $nodes = $this->getCrawler()->filterXPath($xpath);

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $i + 1), $this->session);
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        return $this->getCrawlerNode($this->getFilteredCrawler($xpath))->nodeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        $text = $this->getFilteredCrawler($xpath)->text();
        $text = str_replace("\n", ' ', $text);
        $text = preg_replace('/ {2,}/', ' ', $text);

        return trim($text);
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($xpath)
    {
        // cut the tag itself (making innerHTML out of outerHTML)
        return preg_replace('/^\<[^\>]+\>|\<[^\>]+\>$/', '', $this->getOuterHtml($xpath));
    }

    /**
     * {@inheritdoc}
     */
    public function getOuterHtml($xpath)
    {
        $node = $this->getCrawlerNode($this->getFilteredCrawler($xpath));

        return $node->ownerDocument->saveXML($node);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($xpath, $name)
    {
        $node = $this->getFilteredCrawler($xpath);

        if ($this->getCrawlerNode($node)->hasAttribute($name)) {
            return $node->attr($name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        if (in_array($this->getAttribute($xpath, 'type'), array('submit', 'image', 'button'))) {
            return $this->getAttribute($xpath, 'value');
        }

        $node = $this->getCrawlerNode($this->getFilteredCrawler($xpath));

        if ('option' === $node->tagName) {
            return $this->getOptionValue($node);
        }

        try {
            $field = $this->getFormField($xpath);
        } catch (\InvalidArgumentException $e) {
            return $this->getAttribute($xpath, 'value');
        }

        return $field->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $this->getFormField($xpath)->setValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        $this->getCheckboxField($xpath)->tick();
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        $this->getCheckboxField($xpath)->untick();
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField) {
            throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
        }

        if ($multiple) {
            $oldValue   = (array) $field->getValue();
            $oldValue[] = $value;
            $value      = $oldValue;
        }

        $field->select($value);
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        $optionValue = $this->getOptionValue($this->getCrawlerNode($this->getFilteredCrawler($xpath)));
        $selectField = $this->getFormField('(' . $xpath . ')/ancestor-or-self::*[local-name()="select"]');
        $selectValue = $selectField->getValue();

        return is_array($selectValue) ? in_array($optionValue, $selectValue) : $optionValue == $selectValue;
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $node = $this->getFilteredCrawler($xpath);
        $crawlerNode = $this->getCrawlerNode($node);
        $tagName = $crawlerNode->nodeName;

        if ('a' === $tagName) {
            $this->client->click($node->link());
            $this->forms = array();
        } elseif ($this->canSubmitForm($crawlerNode)) {
            $this->submit($node->form());
        } elseif ($this->canResetForm($crawlerNode)) {
            $this->resetForm($crawlerNode);
        } else {
            $message = sprintf('%%s supports clicking on links and buttons only. But "%s" provided', $tagName);

            throw new UnsupportedDriverActionException($message, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField || 'select' === $field->getType()) {
            throw new DriverException(sprintf('Impossible to get the checked state of the element with XPath "%s" as it is not a checkbox or radio input', $xpath));
        }

        if ('checkbox' === $field->getType()) {
            return $field->hasValue();
        }

        $radio = $this->getCrawlerNode($this->getFilteredCrawler($xpath));

        return $radio->getAttribute('value') === $field->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof FileFormField) {
            throw new DriverException(sprintf('Impossible to attach a file on the element with XPath "%s" as it is not a file input', $xpath));
        }

        $field->upload($path);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        $crawler = $this->getFilteredCrawler($xpath);

        $this->submit($crawler->form());
    }

    /**
     * @return Response
     *
     * @throws DriverException If there is not response yet
     */
    protected function getResponse()
    {
        if (!method_exists($this->client, 'getInternalResponse')) {
            $implementationResponse = $this->client->getResponse();

            if (null === $implementationResponse) {
                throw new DriverException('Unable to access the response before visiting a page');
            }

            return $this->convertImplementationResponse($implementationResponse);
        }

        $response = $this->client->getInternalResponse();

        if (null === $response) {
            throw new DriverException('Unable to access the response before visiting a page');
        }

        return $response;
    }

    /**
     * Gets the BrowserKit Response for legacy BrowserKit versions.
     *
     * Before 2.3.0, there was no Client::getInternalResponse method, and the
     * return value of Client::getResponse can be anything when the implementation
     * uses Client::filterResponse because of a bad choice done in BrowserKit and
     * kept for BC reasons (the Client::getInternalResponse method has been added
     * to solve it).
     *
     * This implementation supports client which don't rely Client::filterResponse
     * and clients which use an HttpFoundation Response (like the HttpKernel client).
     *
     * @param object $response the response specific to the BrowserKit implementation
     *
     * @return Response
     *
     * @throws DriverException If the response cannot be converted to a BrowserKit response
     */
    private function convertImplementationResponse($response)
    {
        if ($response instanceof Response) {
            return $response;
        }

        // due to a bug, the HttpKernel client implementation returns the HttpFoundation response
        // The conversion logic is copied from Symfony\Component\HttpKernel\Client::filterResponse
        if ($response instanceof HttpFoundationResponse) {
            $headers = $response->headers->all();
            if ($response->headers->getCookies()) {
                $cookies = array();
                foreach ($response->headers->getCookies() as $cookie) {
                    $cookies[] = new Cookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        $cookie->isSecure(),
                        $cookie->isHttpOnly()
                    );
                }
                $headers['Set-Cookie'] = $cookies;
            }

            // this is needed to support StreamedResponse
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();

            return new Response($content, $response->getStatusCode(), $headers);
        }

        throw new DriverException(sprintf(
            'The BrowserKit client returned an unsupported response implementation: %s. Please upgrade your BrowserKit package to 2.3 or newer.',
            get_class($response)
        ));
    }

    /**
     * Prepares URL for visiting.
     * Removes "*.php/" from urls and then passes it to BrowserKitDriver::visit().
     *
     * @param string $url
     *
     * @return string
     */
    protected function prepareUrl($url)
    {
        $replacement = ($this->removeHostFromUrl ? '' : '$1') . ($this->removeScriptFromUrl ? '' : '$2');

        return preg_replace('#(https?\://[^/]+)(/[^/\.]+\.php)?#', $replacement, $url);
    }

    /**
     * Returns form field from XPath query.
     *
     * @param string $xpath
     *
     * @return FormField
     *
     * @throws DriverException
     */
    protected function getFormField($xpath)
    {
        $fieldNode = $this->getCrawlerNode($this->getFilteredCrawler($xpath));
        $fieldName = str_replace('[]', '', $fieldNode->getAttribute('name'));

        $formNode = $this->getFormNode($fieldNode);
        $formId = $this->getFormNodeId($formNode);

        if (!isset($this->forms[$formId])) {
            $this->forms[$formId] = new Form($formNode, $this->getCurrentUrl());
        }

        if (is_array($this->forms[$formId][$fieldName])) {
            return $this->forms[$formId][$fieldName][$this->getFieldPosition($fieldNode)];
        }

        return $this->forms[$formId][$fieldName];
    }

    /**
     * Returns the checkbox field from xpath query, ensuring it is valid.
     *
     * @param string $xpath
     *
     * @return ChoiceFormField
     *
     * @throws DriverException when the field is not a checkbox
     */
    private function getCheckboxField($xpath)
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField) {
            throw new DriverException(sprintf('Impossible to check the element with XPath "%s" as it is not a checkbox', $xpath));
        }

        return $field;
    }

    /**
     * @param \DOMElement $element
     *
     * @return \DOMElement
     *
     * @throws DriverException if the form node cannot be found
     */
    private function getFormNode(\DOMElement $element)
    {
        if ($element->hasAttribute('form')) {
            $formId = $element->getAttribute('form');
            $formNode = $element->ownerDocument->getElementById($formId);

            if (null === $formNode || 'form' !== $formNode->nodeName) {
                throw new DriverException(sprintf('The selected node has an invalid form attribute (%s).', $formId));
            }

            return $formNode;
        }

        $formNode = $element;

        do {
            // use the ancestor form element
            if (null === $formNode = $formNode->parentNode) {
                throw new DriverException('The selected node does not have a form ancestor.');
            }
        } while ('form' !== $formNode->nodeName);

        return $formNode;
    }

    /**
     * Gets the position of the field node among elements with the same name
     *
     * BrowserKit uses the field name as index to find the field in its Form object.
     * When multiple fields have the same name (checkboxes for instance), it will return
     * an array of elements in the order they appear in the DOM.
     *
     * @param \DOMElement $fieldNode
     *
     * @return integer
     */
    private function getFieldPosition(\DOMElement $fieldNode)
    {
        $elements = $this->getCrawler()->filterXPath('//*[@name=\''.$fieldNode->getAttribute('name').'\']');

        if (count($elements) > 1) {
            // more than one element contains this name !
            // so we need to find the position of $fieldNode
            foreach ($elements as $key => $element) {
                /** @var \DOMElement $element */
                if ($element->getNodePath() === $fieldNode->getNodePath()) {
                    return $key;
                }
            }
        }

        return 0;
    }

    private function submit(Form $form)
    {
        $formId = $this->getFormNodeId($form->getFormNode());

        if (isset($this->forms[$formId])) {
            $this->mergeForms($form, $this->forms[$formId]);
        }

        // remove empty file fields from request
        foreach ($form->getFiles() as $name => $field) {
            if (empty($field['name']) && empty($field['tmp_name'])) {
                $form->remove($name);
            }
        }

        foreach ($form->all() as $field) {
            // Add a fix for https://github.com/symfony/symfony/pull/10733 to support Symfony versions which are not fixed
            if ($field instanceof TextareaFormField && null === $field->getValue()) {
                $field->setValue('');
            }
        }

        $this->client->submit($form);

        $this->forms = array();
    }

    private function resetForm(\DOMElement $fieldNode)
    {
        $formNode = $this->getFormNode($fieldNode);
        $formId = $this->getFormNodeId($formNode);
        unset($this->forms[$formId]);
    }

    /**
     * Determines if a node can submit a form.
     *
     * @param \DOMElement $node Node.
     *
     * @return boolean
     */
    private function canSubmitForm(\DOMElement $node)
    {
        $type = $node->hasAttribute('type') ? $node->getAttribute('type') : null;

        if ('input' == $node->nodeName && in_array($type, array('submit', 'image'))) {
            return true;
        }

        return 'button' == $node->nodeName && (null === $type || 'submit' == $type);
    }

    /**
     * Determines if a node can reset a form.
     *
     * @param \DOMElement $node Node.
     *
     * @return boolean
     */
    private function canResetForm(\DOMElement $node)
    {
        $type = $node->hasAttribute('type') ? $node->getAttribute('type') : null;

        return in_array($node->nodeName, array('input', 'button')) && 'reset' == $type;
    }

    /**
     * Returns form node unique identifier.
     *
     * @param \DOMElement $form
     *
     * @return string
     */
    private function getFormNodeId(\DOMElement $form)
    {
        return md5($form->getLineNo() . $form->getNodePath() . $form->nodeValue);
    }

    /**
     * Gets the value of an option element
     *
     * @param \DOMElement $option
     *
     * @return string
     *
     * @see \Symfony\Component\DomCrawler\Field\ChoiceFormField::buildOptionValue
     */
    private function getOptionValue(\DOMElement $option)
    {
        if ($option->hasAttribute('value')) {
            return $option->getAttribute('value');
        }

        if (!empty($option->nodeValue)) {
            return $option->nodeValue;
        }

        return '1'; // DomCrawler uses 1 by default if there is no text in the option
    }

    /**
     * Merges second form values into first one.
     *
     * @param Form $to   merging target
     * @param Form $from merging source
     */
    private function mergeForms(Form $to, Form $from)
    {
        foreach ($from->all() as $name => $field) {
            $fieldReflection = new \ReflectionObject($field);
            $nodeReflection  = $fieldReflection->getProperty('node');
            $valueReflection = $fieldReflection->getProperty('value');

            $nodeReflection->setAccessible(true);
            $valueReflection->setAccessible(true);

            if (!($field instanceof InputFormField && in_array(
                $nodeReflection->getValue($field)->getAttribute('type'),
                array('submit', 'button', 'image')
            ))) {
                $valueReflection->setValue($to[$name], $valueReflection->getValue($field));
            }
        }
    }

    /**
     * Returns DOMElement from crawler instance.
     *
     * @param Crawler $crawler
     *
     * @return \DOMElement
     *
     * @throws DriverException when the node does not exist
     */
    private function getCrawlerNode(Crawler $crawler)
    {
        $crawler->rewind();
        $node = $crawler->current();

        if (null !== $node) {
            return $node;
        }

        throw new DriverException('The element does not exist');
    }

    /**
     * Returns a crawler filtered for the given XPath, requiring at least 1 result.
     *
     * @param string $xpath
     *
     * @return Crawler
     *
     * @throws DriverException when no matching elements are found
     */
    private function getFilteredCrawler($xpath)
    {
        if (!count($crawler = $this->getCrawler()->filterXPath($xpath))) {
            throw new DriverException(sprintf('There is no element matching XPath "%s"', $xpath));
        }

        return $crawler;
    }

    /**
     * Returns crawler instance (got from client).
     *
     * @return Crawler
     *
     * @throws DriverException
     */
    private function getCrawler()
    {
        $crawler = $this->client->getCrawler();

        if (null === $crawler) {
            throw new DriverException('Unable to access the response content before visiting a page');
        }

        return $crawler;
    }
}
