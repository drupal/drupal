<?php
/**
 * @file
 * Test the Tree Builder.
 */
namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\StringInputStream;
use Masterminds\HTML5\Parser\Scanner;
use Masterminds\HTML5\Parser\Tokenizer;
use Masterminds\HTML5\Parser\DOMTreeBuilder;

/**
 * These tests are functional, not necessarily unit tests.
 */
class DOMTreeBuilderTest extends \Masterminds\HTML5\Tests\TestCase
{
    protected $errors = array();
    /**
     * Convenience function for parsing.
     */
    protected function parse($string, array $options = array())
    {
        $treeBuilder = new DOMTreeBuilder(false, $options);
        $input = new StringInputStream($string);
        $scanner = new Scanner($input);
        $parser = new Tokenizer($scanner, $treeBuilder);

        $parser->parse();
        $this->errors = $treeBuilder->getErrors();

        return $treeBuilder->document();
    }

    /**
     * Utility function for parsing a fragment of HTML5.
     */
    protected function parseFragment($string)
    {
        $treeBuilder = new DOMTreeBuilder(true);
        $input = new StringInputStream($string);
        $scanner = new Scanner($input);
        $parser = new Tokenizer($scanner, $treeBuilder);

        $parser->parse();
        $this->errors = $treeBuilder->getErrors();

        return $treeBuilder->fragment();
    }

    public function testDocument()
    {
        $html = "<!DOCTYPE html><html></html>";
        $doc = $this->parse($html);

        $this->assertInstanceOf('\DOMDocument', $doc);
        $this->assertEquals('html', $doc->documentElement->tagName);
        $this->assertEquals('http://www.w3.org/1999/xhtml', $doc->documentElement->namespaceURI);
    }

    public function testStrangeCapitalization()
    {
        $html = "<!doctype html>
        <html>
            <head>
                <Title>Hello, world!</TitlE>
            </head>
            <body>TheBody<script>foo</script></body>
        </html>";
        $doc = $this->parse($html);

        $this->assertInstanceOf('\DOMDocument', $doc);
        $this->assertEquals('html', $doc->documentElement->tagName);

        $xpath = new \DOMXPath( $doc );
        $xpath->registerNamespace( "x", "http://www.w3.org/1999/xhtml" );

        $this->assertEquals("Hello, world!", $xpath->query( "//x:title" )->item( 0 )->nodeValue);
        $this->assertEquals("foo", $xpath->query( "//x:script" )->item( 0 )->nodeValue);
    }

    public function testDocumentWithDisabledNamespaces()
    {
        $html = "<!DOCTYPE html><html></html>";
        $doc = $this->parse($html, array('disable_html_ns' => true));

        $this->assertInstanceOf('\DOMDocument', $doc);
        $this->assertEquals('html', $doc->documentElement->tagName);
        $this->assertNull($doc->documentElement->namespaceURI);
    }

    public function testDocumentWithATargetDocument()
    {
        $targetDom = new \DOMDocument();

        $html = "<!DOCTYPE html><html></html>";
        $doc = $this->parse($html, array('target_document' => $targetDom));

        $this->assertInstanceOf('\DOMDocument', $doc);
        $this->assertSame($doc, $targetDom);
        $this->assertEquals('html', $doc->documentElement->tagName);
    }

    public function testDocumentFakeAttrAbsence()
    {
        $html = "<!DOCTYPE html><html xmlns=\"http://www.w3.org/1999/xhtml\"><body>foo</body></html>";
        $doc = $this->parse($html, array('xmlNamespaces'=>true));

        $xp = new \DOMXPath($doc);
        $this->assertEquals(0, $xp->query("//@html5-php-fake-id-attribute")->length);
    }

    public function testFragment()
    {
        $html = "<div>test</div><span>test2</span>";
        $doc = $this->parseFragment($html);

        $this->assertInstanceOf('\DOMDocumentFragment', $doc);
        $this->assertTrue($doc->hasChildNodes());
        $this->assertEquals('div', $doc->childNodes->item(0)->tagName);
        $this->assertEquals('test', $doc->childNodes->item(0)->textContent);
        $this->assertEquals('span', $doc->childNodes->item(1)->tagName);
        $this->assertEquals('test2', $doc->childNodes->item(1)->textContent);
    }

    public function testElements()
    {
        $html = "<!DOCTYPE html><html><head><title></title></head><body></body></html>";
        $doc = $this->parse($html);
        $root = $doc->documentElement;

        $this->assertEquals('html', $root->tagName);
        $this->assertEquals('html', $root->localName);
        $this->assertEquals('html', $root->nodeName);

        $this->assertEquals(2, $root->childNodes->length);
        $kids = $root->childNodes;

        $this->assertEquals('head', $kids->item(0)->tagName);
        $this->assertEquals('body', $kids->item(1)->tagName);

        $head = $kids->item(0);
        $this->assertEquals(1, $head->childNodes->length);
        $this->assertEquals('title', $head->childNodes->item(0)->tagName);
    }

    public function testImplicitNamespaces()
    {
        $dom = $this->parse('<!DOCTYPE html><html><body><a xlink:href="bar">foo</a></body></html>');
        $a = $dom->getElementsByTagName('a')->item(0);
        $attr = $a->getAttributeNode('xlink:href');
        $this->assertEquals('http://www.w3.org/1999/xlink', $attr->namespaceURI);

        $dom = $this->parse('<!DOCTYPE html><html><body><a xml:base="bar">foo</a></body></html>');
        $a = $dom->getElementsByTagName('a')->item(0);
        $attr = $a->getAttributeNode('xml:base');
        $this->assertEquals('http://www.w3.org/XML/1998/namespace', $attr->namespaceURI);
    }

    public function testCustomImplicitNamespaces()
    {
        $dom = $this->parse('<!DOCTYPE html><html><body><a t:href="bar">foo</a></body></html>', array(
            'implicitNamespaces' => array(
                't' => 'http://www.example.com'
            )
        ));
        $a = $dom->getElementsByTagName('a')->item(0);
        $attr = $a->getAttributeNode('t:href');
        $this->assertEquals('http://www.example.com', $attr->namespaceURI);

        $dom = $this->parse('<!DOCTYPE html><html><body><t:a>foo</t:a></body></html>', array(
            'implicitNamespaces' => array(
                't' => 'http://www.example.com'
            )
        ));
        $list = $dom->getElementsByTagNameNS('http://www.example.com', 'a');
        $this->assertEquals(1, $list->length);
    }

    public function testXmlNamespaces()
    {
        $dom = $this->parse(
            '<!DOCTYPE html><html>
            <t:body xmlns:t="http://www.example.com">
                <a t:href="bar">foo</a>
            </body>
            <div>foo</div>
          </html>', array(
                'xmlNamespaces' => true
            ));
        $a = $dom->getElementsByTagName('a')->item(0);
        $attr = $a->getAttributeNode('t:href');
        $this->assertEquals('http://www.example.com', $attr->namespaceURI);

        $list = $dom->getElementsByTagNameNS('http://www.example.com', 'body');
        $this->assertEquals(1, $list->length);
    }

    public function testXmlNamespaceNesting()
    {
        $dom = $this->parse(
            '<!DOCTYPE html><html>
            <body xmlns:x="http://www.prefixed.com" id="body">
                <a id="bar1" xmlns="http://www.prefixed.com/bar1">
                    <b id="bar4" xmlns="http://www.prefixed.com/bar4"><x:prefixed id="prefixed"/></b>
                </a>
                <svg id="svg"></svg>
                <c id="bar2" xmlns="http://www.prefixed.com/bar2"></c>
                <div id="div"></div>
                <d id="bar3"></d>
                <xn:d xmlns:xn="http://www.prefixed.com/xn" xmlns="http://www.prefixed.com/bar5_x" id="bar5"><x id="bar5_x"/></xn:d>
            </body>
          </html>', array(
                'xmlNamespaces' => true
            ));


        $this->assertEmpty($this->errors);

        $div = $dom->getElementById('div');
        $this->assertEquals('http://www.w3.org/1999/xhtml', $div->namespaceURI);

        $body = $dom->getElementById('body');
        $this->assertEquals('http://www.w3.org/1999/xhtml', $body->namespaceURI);

        $bar1 = $dom->getElementById('bar1');
        $this->assertEquals('http://www.prefixed.com/bar1', $bar1->namespaceURI);

        $bar2 = $dom->getElementById('bar2');
        $this->assertEquals("http://www.prefixed.com/bar2", $bar2->namespaceURI);

        $bar3 = $dom->getElementById('bar3');
        $this->assertEquals("http://www.w3.org/1999/xhtml", $bar3->namespaceURI);

        $bar4 = $dom->getElementById('bar4');
        $this->assertEquals("http://www.prefixed.com/bar4", $bar4->namespaceURI);

        $svg = $dom->getElementById('svg');
        $this->assertEquals("http://www.w3.org/2000/svg", $svg->namespaceURI);

        $prefixed = $dom->getElementById('prefixed');
        $this->assertEquals("http://www.prefixed.com", $prefixed->namespaceURI);

        $prefixed = $dom->getElementById('bar5');
        $this->assertEquals("http://www.prefixed.com/xn", $prefixed->namespaceURI);

        $prefixed = $dom->getElementById('bar5_x');
        $this->assertEquals("http://www.prefixed.com/bar5_x", $prefixed->namespaceURI);
    }

    public function testMoveNonInlineElements()
    {
    	$doc = $this->parse('<p>line1<br/><hr/>line2</p>');
		$this->assertEquals('<html xmlns="http://www.w3.org/1999/xhtml"><p>line1<br/></p><hr/>line2</html>', $doc->saveXML($doc->documentElement), 'Move non-inline elements outside of inline containers.');

		$doc = $this->parse('<p>line1<div>line2</div></p>');
		$this->assertEquals('<html xmlns="http://www.w3.org/1999/xhtml"><p>line1</p><div>line2</div></html>', $doc->saveXML($doc->documentElement), 'Move non-inline elements outside of inline containers.');
    }

    public function testAttributes()
    {
        $html = "<!DOCTYPE html>
      <html>
      <head><title></title></head>
      <body id='a' class='b c'></body>
      </html>";
        $doc = $this->parse($html);
        $root = $doc->documentElement;

        $body = $root->GetElementsByTagName('body')->item(0);
        $this->assertEquals('body', $body->tagName);
        $this->assertTrue($body->hasAttributes());
        $this->assertEquals('a', $body->getAttribute('id'));
        $this->assertEquals('b c', $body->getAttribute('class'));

        $body2 = $doc->getElementById('a');
        $this->assertEquals('body', $body2->tagName);
        $this->assertEquals('a', $body2->getAttribute('id'));
    }

    public function testSVGAttributes()
    {
        $html = "<!DOCTYPE html>
      <html><body>
      <svg width='150' viewbox='2'>
      <rect textlength='2'/>
      <animatecolor>foo</animatecolor>
      </svg>
      </body></html>";
        $doc = $this->parse($html);
        $root = $doc->documentElement;

        $svg = $root->getElementsByTagName('svg')->item(0);
        $this->assertTrue($svg->hasAttribute('viewBox'));

        $rect = $root->getElementsByTagName('rect')->item(0);
        $this->assertTrue($rect->hasAttribute('textLength'));

        $ac = $root->getElementsByTagName('animateColor');
        $this->assertEquals(1, $ac->length);
    }

    public function testMathMLAttribute()
    {
        $html = '<!doctype html>
      <html lang="en">
        <body>
          <math>
            <mi>x</mi>
            <csymbol definitionurl="http://www.example.com/mathops/multiops.html#plusminus">
              <mo>&PlusMinus;</mo>
            </csymbol>
            <mi>y</mi>
          </math>
        </body>
      </html>';

        $doc = $this->parse($html);
        $root = $doc->documentElement;

        $csymbol = $root->getElementsByTagName('csymbol')->item(0);
        $this->assertTrue($csymbol->hasAttribute('definitionURL'));
    }

    public function testMissingHtmlTag()
    {
        $html = "<!DOCTYPE html><title>test</title>";
        $doc = $this->parse($html);

        $this->assertEquals('html', $doc->documentElement->tagName);
        $this->assertEquals('title', $doc->documentElement->childNodes->item(0)->tagName);
    }

    public function testComment()
    {
        $html = '<html><!--Hello World.--></html>';

        $doc = $this->parse($html);

        $comment = $doc->documentElement->childNodes->item(0);
        $this->assertEquals(XML_COMMENT_NODE, $comment->nodeType);
        $this->assertEquals("Hello World.", $comment->data);

        $html = '<!--Hello World.--><html></html>';
        $doc = $this->parse($html);

        $comment = $doc->childNodes->item(1);
        $this->assertEquals(XML_COMMENT_NODE, $comment->nodeType);
        $this->assertEquals("Hello World.", $comment->data);

        $comment = $doc->childNodes->item(2);
        $this->assertEquals(XML_ELEMENT_NODE, $comment->nodeType);
        $this->assertEquals("html", $comment->tagName);
    }

    public function testCDATA()
    {
        $html = "<!DOCTYPE html><html><math><![CDATA[test]]></math></html>";
        $doc = $this->parse($html);

        $wrapper = $doc->getElementsByTagName('math')->item(0);
        $this->assertEquals(1, $wrapper->childNodes->length);
        $cdata = $wrapper->childNodes->item(0);
        $this->assertEquals(XML_CDATA_SECTION_NODE, $cdata->nodeType);
        $this->assertEquals('test', $cdata->data);
    }

    public function testText()
    {
        $html = "<!DOCTYPE html><html><head></head><body><math>test</math></body></html>";
        $doc = $this->parse($html);

        $wrapper = $doc->getElementsByTagName('math')->item(0);
        $this->assertEquals(1, $wrapper->childNodes->length);
        $data = $wrapper->childNodes->item(0);
        $this->assertEquals(XML_TEXT_NODE, $data->nodeType);
        $this->assertEquals('test', $data->data);

        // The DomTreeBuilder has special handling for text when in before head mode.
        $html = "<!DOCTYPE html><html>
    Foo<head></head><body></body></html>";
        $doc = $this->parse($html);
        $this->assertEquals('Line 0, Col 0: Unexpected text. Ignoring: Foo', $this->errors[0]);
        $headElement = $doc->documentElement->firstChild;
        $this->assertEquals('head', $headElement->tagName);
    }

    public function testParseErrors()
    {
        $html = "<!DOCTYPE html><html><math><![CDATA[test";
        $doc = $this->parse($html);

        // We're JUST testing that we can access errors. Actual testing of
        // error messages happen in the Tokenizer's tests.
        $this->assertGreaterThan(0, count($this->errors));
        $this->assertTrue(is_string($this->errors[0]));
    }

    public function testProcessingInstruction()
    {
        // Test the simple case, which is where PIs are inserted into the DOM.
        $doc = $this->parse('<!DOCTYPE html><html><?foo bar?>');
        $this->assertEquals(1, $doc->documentElement->childNodes->length);
        $pi = $doc->documentElement->firstChild;
        $this->assertInstanceOf('\DOMProcessingInstruction', $pi);
        $this->assertEquals('foo', $pi->nodeName);
        $this->assertEquals('bar', $pi->data);

        // Leading xml PIs should be ignored.
        $doc = $this->parse('<?xml version="1.0"?><!DOCTYPE html><html><head></head></html>');

        $this->assertEquals(2, $doc->childNodes->length);
        $this->assertInstanceOf('\DOMDocumentType', $doc->childNodes->item(0));
        $this->assertInstanceOf('\DOMElement', $doc->childNodes->item(1));
    }

    public function testAutocloseP()
    {
        $html = "<!DOCTYPE html><html><body><p><figure></body></html>";
        $doc = $this->parse($html);

        $p = $doc->getElementsByTagName('p')->item(0);
        $this->assertEquals(0, $p->childNodes->length);
        $this->assertEquals('figure', $p->nextSibling->tagName);
    }

    public function testAutocloseLI()
    {
        $html = '<!doctype html>
      <html lang="en">
        <body>
          <ul><li>Foo<li>Bar<li>Baz</ul>
        </body>
      </html>';

        $doc = $this->parse($html);
        $length = $doc->getElementsByTagName('ul')->item(0)->childNodes->length;
        $this->assertEquals(3, $length);
    }

    public function testMathML()
    {
        $html = '<!doctype html>
      <html lang="en">
        <body>
          <math xmlns="http://www.w3.org/1998/Math/MathML">
            <mi>x</mi>
            <csymbol definitionurl="http://www.example.com/mathops/multiops.html#plusminus">
              <mo>&PlusMinus;</mo>
            </csymbol>
            <mi>y</mi>
          </math>
        </body>
      </html>';

        $doc = $this->parse($html);
        $math = $doc->getElementsByTagName('math')->item(0);
        $this->assertEquals('math', $math->tagName);
        $this->assertEquals('math', $math->nodeName);
        $this->assertEquals('math', $math->localName);
        $this->assertEquals('http://www.w3.org/1998/Math/MathML', $math->namespaceURI);
    }

    public function testSVG()
    {
        $html = '<!doctype html>
      <html lang="en">
        <body>
          <svg width="150" height="100" viewBox="0 0 3 2" xmlns="http://www.w3.org/2000/svg">
            <rect width="1" height="2" x="2" fill="#d2232c" />
            <text font-family="Verdana" font-size="32">
              <textpath xlink:href="#Foo">
                Test Text.
              </textPath>
            </text>
          </svg>
        </body>
      </html>';

        $doc = $this->parse($html);
        $svg = $doc->getElementsByTagName('svg')->item(0);
        $this->assertEquals('svg', $svg->tagName);
        $this->assertEquals('svg', $svg->nodeName);
        $this->assertEquals('svg', $svg->localName);
        $this->assertEquals('http://www.w3.org/2000/svg', $svg->namespaceURI);

        $textPath = $doc->getElementsByTagName('textPath')->item(0);
        $this->assertEquals('textPath', $textPath->tagName);
    }

    public function testNoScript()
    {
        $html = '<!DOCTYPE html><html><head><noscript>No JS</noscript></head></html>';
        $doc = $this->parse($html);
        $this->assertEmpty($this->errors);
        $noscript = $doc->getElementsByTagName('noscript')->item(0);
        $this->assertEquals('noscript', $noscript->tagName);
    }

    /**
     * Regression for issue #13
     */
    public function testRegressionHTMLNoBody()
    {
        $html = '<!DOCTYPE html><html><span id="test">Test</span></html>';
        $doc = $this->parse($html);
        $span = $doc->getElementById('test');

        $this->assertEmpty($this->errors);

        $this->assertEquals('span', $span->tagName);
        $this->assertEquals('Test', $span->textContent);
    }

    public function testInstructionProcessor()
    {
        $string = '<!DOCTYPE html><html><?foo bar ?></html>';

        $treeBuilder = new DOMTreeBuilder();
        $is = new InstructionProcessorMock();
        $treeBuilder->setInstructionProcessor($is);

        $input = new StringInputStream($string);
        $scanner = new Scanner($input);
        $parser = new Tokenizer($scanner, $treeBuilder);

        $parser->parse();
        $dom = $treeBuilder->document();
        $div = $dom->getElementsByTagName('div')->item(0);

        $this->assertEquals(1, $is->count);
        $this->assertEquals('foo', $is->name);
        $this->assertEquals('bar ', $is->data);
        $this->assertEquals('div', $div->tagName);
        $this->assertEquals('foo', $div->textContent);
    }
}
