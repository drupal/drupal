<?php
namespace Masterminds\HTML5\Tests;

class Html5Test extends TestCase
{

    public function setUp()
    {
        $this->html5 = $this->getInstance();
    }

    /**
     * Parse and serialize a string.
     */
    protected function cycle($html)
    {
        $dom = $this->html5->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>');
        $out = $this->html5->saveHTML($dom);

        return $out;
    }

    protected function cycleFragment($fragment)
    {
        $dom = $this->html5->loadHTMLFragment($fragment);
        $out = $this->html5->saveHTML($dom);

        return $out;
    }

    public function testLoadOptions()
    {
        // doc
        $dom = $this->html5->loadHTML($this->wrap('<t:tag/>'), array(
                        'implicitNamespaces' => array('t' => 'http://example.com'),
                        "xmlNamespaces" => true
        ));
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());
        $this->assertFalse($this->html5->hasErrors());

        $xpath = new \DOMXPath( $dom );
        $xpath->registerNamespace( "t", "http://example.com" );
        $this->assertEquals(1, $xpath->query( "//t:tag" )->length);

        // doc fragment
        $frag = $this->html5->loadHTMLFragment('<t:tag/>', array(
                        'implicitNamespaces' => array('t' => 'http://example.com'),
                        "xmlNamespaces" => true
        ));
        $this->assertInstanceOf('\DOMDocumentFragment', $frag);
        $this->assertEmpty($this->html5->getErrors());
        $this->assertFalse($this->html5->hasErrors());

        $frag->ownerDocument->appendChild($frag);
        $xpath = new \DOMXPath( $frag->ownerDocument );
        $xpath->registerNamespace( "t", "http://example.com" );
        $this->assertEquals(1, $xpath->query( "//t:tag" , $frag)->length);
    }

    public function testErrors()
    {
        $dom = $this->html5->loadHTML('<xx as>');
        $this->assertInstanceOf('\DOMDocument', $dom);

        $this->assertNotEmpty($this->html5->getErrors());
        $this->assertTrue($this->html5->hasErrors());
    }

    public function testLoad()
    {
        $dom = $this->html5->load(__DIR__ . '/Html5Test.html');
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());
        $this->assertFalse($this->html5->hasErrors());

        $file = fopen(__DIR__ . '/Html5Test.html', 'r');
        $dom = $this->html5->load($file);
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());

        $dom = $this->html5->loadHTMLFile(__DIR__ . '/Html5Test.html');
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());
    }

    public function testLoadHTML()
    {
        $contents = file_get_contents(__DIR__ . '/Html5Test.html');
        $dom = $this->html5->loadHTML($contents);
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());
    }

    public function testLoadHTMLFragment()
    {
        $fragment = '<section id="Foo"><div class="Bar">Baz</div></section>';
        $dom = $this->html5->loadHTMLFragment($fragment);
        $this->assertInstanceOf('\DOMDocumentFragment', $dom);
        $this->assertEmpty($this->html5->getErrors());
    }

    public function testSaveHTML()
    {
        $dom = $this->html5->load(__DIR__ . '/Html5Test.html');
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());

        $saved = $this->html5->saveHTML($dom);
        $this->assertRegExp('|<p>This is a test.</p>|', $saved);
    }

    public function testSaveHTMLFragment()
    {
        $fragment = '<section id="Foo"><div class="Bar">Baz</div></section>';
        $dom = $this->html5->loadHTMLFragment($fragment);

        $string = $this->html5->saveHTML($dom);
        $this->assertEquals($fragment, $string);
    }

    public function testSave()
    {
        $dom = $this->html5->load(__DIR__ . '/Html5Test.html');
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());

        // Test resource
        $file = fopen('php://temp', 'w');
        $this->html5->save($dom, $file);
        $content = stream_get_contents($file, - 1, 0);
        $this->assertRegExp('|<p>This is a test.</p>|', $content);

        // Test file
        $tmpfname = tempnam(sys_get_temp_dir(), "html5-php");
        $this->html5->save($dom, $tmpfname);
        $content = file_get_contents($tmpfname);
        $this->assertRegExp('|<p>This is a test.</p>|', $content);
        unlink($tmpfname);
    }

    // This test reads a document into a dom, turn the dom into a document,
    // then tries to read that document again. This makes sure we are reading,
    // and generating a document that works at a high level.
    public function testItWorks()
    {
        $dom = $this->html5->load(__DIR__ . '/Html5Test.html');
        $this->assertInstanceOf('\DOMDocument', $dom);
        $this->assertEmpty($this->html5->getErrors());

        $saved = $this->html5->saveHTML($dom);

        $dom2 = $this->html5->loadHTML($saved);
        $this->assertInstanceOf('\DOMDocument', $dom2);
        $this->assertEmpty($this->html5->getErrors());
    }

    public function testConfig()
    {
        $html5 = $this->getInstance();
        $options = $html5->getOptions();
        $this->assertEquals(false, $options['encode_entities']);

        $html5 = $this->getInstance(array(
            'foo' => 'bar',
            'encode_entities' => true
        ));
        $options = $html5->getOptions();
        $this->assertEquals('bar', $options['foo']);
        $this->assertEquals(true, $options['encode_entities']);

        // Need to reset to original so future tests pass as expected.
        // $this->getInstance()->setOption('encode_entities', false);
    }

    public function testSvg()
    {
        $dom = $this->html5->loadHTML(
            '<!doctype html>
      <html lang="en">
        <body>
          <div id="foo" class="bar baz">foo bar baz</div>
          <svg width="150" height="100" viewBox="0 0 3 2">
            <rect width="1" height="2" x="0" fill="#008d46" />
            <rect width="1" height="2" x="1" fill="#ffffff" />
            <rect width="1" height="2" x="2" fill="#d2232c" />
            <text font-family="Verdana" font-size="32">
              <textPath xlink:href="#Foo">
                Test Text.
              </textPath>
            </text>
          </svg>
        </body>
      </html>');

        $this->assertEmpty($this->html5->getErrors());

        // Test a mixed case attribute.
        $list = $dom->getElementsByTagName('svg');
        $this->assertNotEmpty($list->length);
        $svg = $list->item(0);
        $this->assertEquals("0 0 3 2", $svg->getAttribute('viewBox'));
        $this->assertFalse($svg->hasAttribute('viewbox'));

        // Test a mixed case tag.
        // Note: getElementsByTagName is not case sensetitive.
        $list = $dom->getElementsByTagName('textPath');
        $this->assertNotEmpty($list->length);
        $textPath = $list->item(0);
        $this->assertEquals('textPath', $textPath->tagName);
        $this->assertNotEquals('textpath', $textPath->tagName);

        $html = $this->html5->saveHTML($dom);
        $this->assertRegExp('|<svg width="150" height="100" viewBox="0 0 3 2">|', $html);
        $this->assertRegExp('|<rect width="1" height="2" x="0" fill="#008d46" />|', $html);
    }

    public function testMathMl()
    {
        $dom = $this->html5->loadHTML(
            '<!doctype html>
      <html lang="en">
        <body>
          <div id="foo" class="bar baz" definitionURL="http://example.com">foo bar baz</div>
          <math>
            <mi>x</mi>
            <csymbol definitionURL="http://www.example.com/mathops/multiops.html#plusminus">
              <mo>&PlusMinus;</mo>
            </csymbol>
            <mi>y</mi>
          </math>
        </body>
      </html>');

        $this->assertEmpty($this->html5->getErrors());
        $list = $dom->getElementsByTagName('math');
        $this->assertNotEmpty($list->length);

        $list = $dom->getElementsByTagName('div');
        $this->assertNotEmpty($list->length);
        $div = $list->item(0);
        $this->assertEquals('http://example.com', $div->getAttribute('definitionurl'));
        $this->assertFalse($div->hasAttribute('definitionURL'));
        $list = $dom->getElementsByTagName('csymbol');
        $csymbol = $list->item(0);
        $this->assertEquals('http://www.example.com/mathops/multiops.html#plusminus', $csymbol->getAttribute('definitionURL'));
        $this->assertFalse($csymbol->hasAttribute('definitionurl'));

        $html = $this->html5->saveHTML($dom);
        $this->assertRegExp('|<csymbol definitionURL="http://www.example.com/mathops/multiops.html#plusminus">|', $html);
        $this->assertRegExp('|<mi>y</mi>|', $html);
    }

    public function testUnknownElements()
    {
        // The : should not have special handling accourding to section 2.9 of the
        // spec. This is differenant than XML. Since we don't know these elements
        // they are handled as normal elements. Note, to do this is really
        // an invalid example and you should not embed prefixed xml in html5.
        $dom = $this->html5->loadHTMLFragment(
            "<f:rug>
      <f:name>Big rectangle thing</f:name>
      <f:width>40</f:width>
      <f:length>80</f:length>
    </f:rug>
    <sarcasm>um, yeah</sarcasm>");

        $this->assertEmpty($this->html5->getErrors());
        $markup = $this->html5->saveHTML($dom);
        $this->assertRegExp('|<f:name>Big rectangle thing</f:name>|', $markup);
        $this->assertRegExp('|<sarcasm>um, yeah</sarcasm>|', $markup);
    }

    public function testElements()
    {
        // Should have content.
        $res = $this->cycle('<div>FOO</div>');
        $this->assertRegExp('|<div>FOO</div>|', $res);

        // Should be empty
        $res = $this->cycle('<span></span>');
        $this->assertRegExp('|<span></span>|', $res);

        // Should have content.
        $res = $this->cycleFragment('<div>FOO</div>');
        $this->assertRegExp('|<div>FOO</div>|', $res);

        // Should be empty
        $res = $this->cycleFragment('<span></span>');
        $this->assertRegExp('|<span></span>|', $res);

        // Elements with dashes and underscores
        $res = $this->cycleFragment('<sp-an></sp-an>');
        $this->assertRegExp('|<sp-an></sp-an>|', $res);
        $res = $this->cycleFragment('<sp_an></sp_an>');
        $this->assertRegExp('|<sp_an></sp_an>|', $res);

        // Should have no closing tag.
        $res = $this->cycle('<hr>');
        $this->assertRegExp('|<hr></body>|', $res);
    }

    public function testAttributes()
    {
        $res = $this->cycle('<div attr="val">FOO</div>');
        $this->assertRegExp('|<div attr="val">FOO</div>|', $res);

        // XXX: Note that spec does NOT require attrs in the same order.
        $res = $this->cycle('<div attr="val" class="even">FOO</div>');
        $this->assertRegExp('|<div attr="val" class="even">FOO</div>|', $res);

        $res = $this->cycle('<div xmlns:foo="http://example.com">FOO</div>');
        $this->assertRegExp('|<div xmlns:foo="http://example.com">FOO</div>|', $res);

        $res = $this->cycleFragment('<div attr="val">FOO</div>');
        $this->assertRegExp('|<div attr="val">FOO</div>|', $res);

        // XXX: Note that spec does NOT require attrs in the same order.
        $res = $this->cycleFragment('<div attr="val" class="even">FOO</div>');
        $this->assertRegExp('|<div attr="val" class="even">FOO</div>|', $res);

        $res = $this->cycleFragment('<div xmlns:foo="http://example.com">FOO</div>');
        $this->assertRegExp('|<div xmlns:foo="http://example.com">FOO</div>|', $res);
    }

    public function testPCData()
    {
        $res = $this->cycle('<a>This is a test.</a>');
        $this->assertRegExp('|This is a test.|', $res);

        $res = $this->cycleFragment('<a>This is a test.</a>');
        $this->assertRegExp('|This is a test.|', $res);

        $res = $this->cycle('This
      is
      a
      test.');

        // Check that newlines are there, but don't count spaces.
        $this->assertRegExp('|This\n\s*is\n\s*a\n\s*test.|', $res);

        $res = $this->cycleFragment('This
      is
      a
      test.');

        // Check that newlines are there, but don't count spaces.
        $this->assertRegExp('|This\n\s*is\n\s*a\n\s*test.|', $res);

        $res = $this->cycle('<a>This <em>is</em> a test.</a>');
        $this->assertRegExp('|This <em>is</em> a test.|', $res);

        $res = $this->cycleFragment('<a>This <em>is</em> a test.</a>');
        $this->assertRegExp('|This <em>is</em> a test.|', $res);
    }

    public function testUnescaped()
    {
        $res = $this->cycle('<script>2 < 1</script>');
        $this->assertRegExp('|2 < 1|', $res);

        $res = $this->cycle('<style>div>div>div</style>');
        $this->assertRegExp('|div>div>div|', $res);

        $res = $this->cycleFragment('<script>2 < 1</script>');
        $this->assertRegExp('|2 < 1|', $res);

        $res = $this->cycleFragment('<style>div>div>div</style>');
        $this->assertRegExp('|div>div>div|', $res);
    }

    public function testEntities()
    {
        $res = $this->cycle('<a>Apples &amp; bananas.</a>');
        $this->assertRegExp('|Apples &amp; bananas.|', $res);

        $res = $this->cycleFragment('<a>Apples &amp; bananas.</a>');
        $this->assertRegExp('|Apples &amp; bananas.|', $res);
    }

    public function testComment()
    {
        $res = $this->cycle('a<!-- This is a test. -->b');
        $this->assertRegExp('|<!-- This is a test. -->|', $res);

        $res = $this->cycleFragment('a<!-- This is a test. -->b');
        $this->assertRegExp('|<!-- This is a test. -->|', $res);
    }

    public function testCDATA()
    {
        $res = $this->cycle('a<![CDATA[ This <is> a test. ]]>b');
        $this->assertRegExp('|<!\[CDATA\[ This <is> a test\. \]\]>|', $res);

        $res = $this->cycleFragment('a<![CDATA[ This <is> a test. ]]>b');
        $this->assertRegExp('|<!\[CDATA\[ This <is> a test\. \]\]>|', $res);
    }
}
