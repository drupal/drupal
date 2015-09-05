<?php
namespace Masterminds\HTML5\Tests\Serializer;

use Masterminds\HTML5\Serializer\OutputRules;
use Masterminds\HTML5\Serializer\Traverser;

class OutputRulesTest extends \Masterminds\HTML5\Tests\TestCase
{

    protected $markup = '<!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8">
        <title>Test</title>
      </head>
      <body>
        <p>This is a test.</p>
      </body>
    </html>';

    public function setUp()
    {
        $this->html5 = $this->getInstance();
    }

    /**
     * Using reflection we make a protected method accessible for testing.
     *
     * @param string $name
     *            The name of the method on the Traverser class to test.
     *
     * @return \ReflectionMethod \ReflectionMethod for the specified method
     */
    public function getProtectedMethod($name)
    {
        $class = new \ReflectionClass('\Masterminds\HTML5\Serializer\OutputRules');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function getTraverserProtectedProperty($name)
    {
        $class = new \ReflectionClass('\Masterminds\HTML5\Serializer\Traverser');
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property;
    }

    public function getOutputRules($options = array())
    {
        $options = $options + $this->html5->getOptions();
        $stream = fopen('php://temp', 'w');
        $dom = $this->html5->loadHTML($this->markup);
        $r = new OutputRules($stream, $options);
        $t = new Traverser($dom, $stream, $r, $options);

        return array(
            $r,
            $stream
        );
    }

    public function testDocument()
    {
        $dom = $this->html5->loadHTML('<!doctype html><html lang="en"><body>foo</body></html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $r->document($dom);
        $expected = '<!DOCTYPE html>' . PHP_EOL . '<html lang="en"><body>foo</body></html>' . PHP_EOL;
        $this->assertEquals($expected, stream_get_contents($stream, - 1, 0));
    }

    public function testEmptyDocument()
    {
    	$dom = $this->html5->loadHTML('');

    	$stream = fopen('php://temp', 'w');
    	$r = new OutputRules($stream, $this->html5->getOptions());
    	$t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

    	$r->document($dom);
    	$expected = '<!DOCTYPE html>' . PHP_EOL;
    	$this->assertEquals($expected, stream_get_contents($stream, - 1, 0));
    }

    public function testDoctype()
    {
        $dom = $this->html5->loadHTML('<!doctype html><html lang="en"><body>foo</body></html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $m = $this->getProtectedMethod('doctype');
        $m->invoke($r, 'foo');
        $this->assertEquals("<!DOCTYPE html>" . PHP_EOL, stream_get_contents($stream, - 1, 0));
    }

    public function testElement()
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
        </svg>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');
        $r->element($list->item(0));
        $this->assertEquals('<div id="foo" class="bar baz">foo bar baz</div>', stream_get_contents($stream, - 1, 0));
    }

    function testSerializeWithNamespaces()
    {
        $this->html5 = $this->getInstance(array(
            'xmlNamespaces' => true
        ));

        $source = '
            <!DOCTYPE html>
            <html><body id="body" xmlns:x="http://www.prefixed.com">
                    <a id="bar1" xmlns="http://www.prefixed.com/bar1">
                        <b id="bar4" xmlns="http://www.prefixed.com/bar4"><x:prefixed id="prefixed">xy</x:prefixed></b>
                    </a>
                    <svg id="svg">svg</svg>
                    <c id="bar2" xmlns="http://www.prefixed.com/bar2"></c>
                    <div id="div"></div>
                    <d id="bar3"></d>
                    <xn:d id="bar5" xmlns:xn="http://www.prefixed.com/xn" xmlns="http://www.prefixed.com/bar5_x"><x id="bar5_x">y</x></xn:d>
                </body>
            </html>';

        $dom = $this->html5->loadHTML($source, array(
            'xmlNamespaces' => true
        ));
        $this->assertFalse($this->html5->hasErrors(), print_r($this->html5->getErrors(), 1));

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $t->walk();
        $rendered = stream_get_contents($stream, - 1, 0);

        $clear = function($s){
            return trim(preg_replace('/[\s]+/', " ", $s));
        };

        $this->assertEquals($clear($source), $clear($rendered));
    }

    public function testElementWithScript()
    {
        $dom = $this->html5->loadHTML(
            '<!doctype html>
    <html lang="en">
      <head>
        <script>
          var $jQ = jQuery.noConflict();
          // Use jQuery via $jQ(...)
          $jQ(document).ready(function () {
            $jQ("#mktFrmSubmit").wrap("<div class=\'buttonSubmit\'></div>");
            $jQ(".buttonSubmit").prepend("<span></span>");
          });
        </script>
      </head>
      <body>
        <div id="foo" class="bar baz">foo bar baz</div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $script = $dom->getElementsByTagName('script');
        $r->element($script->item(0));
        $this->assertEquals(
            '<script>
          var $jQ = jQuery.noConflict();
          // Use jQuery via $jQ(...)
          $jQ(document).ready(function () {
            $jQ("#mktFrmSubmit").wrap("<div class=\'buttonSubmit\'></div>");
            $jQ(".buttonSubmit").prepend("<span></span>");
          });
        </script>', stream_get_contents($stream, - 1, 0));
    }

    public function testElementWithStyle()
    {
        $dom = $this->html5->loadHTML(
            '<!doctype html>
    <html lang="en">
      <head>
        <style>
          body > .bar {
            display: none;
          }
        </style>
      </head>
      <body>
        <div id="foo" class="bar baz">foo bar baz</div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $style = $dom->getElementsByTagName('style');
        $r->element($style->item(0));
        $this->assertEquals('<style>
          body > .bar {
            display: none;
          }
        </style>', stream_get_contents($stream, - 1, 0));
    }

    public function testOpenTag()
    {
        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div id="foo" class="bar baz">foo bar baz</div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');
        $m = $this->getProtectedMethod('openTag');
        $m->invoke($r, $list->item(0));
        $this->assertEquals('<div id="foo" class="bar baz">', stream_get_contents($stream, - 1, 0));
    }

    public function testCData()
    {
        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div><![CDATA[bar]]></div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');
        $r->cdata($list->item(0)->childNodes->item(0));
        $this->assertEquals('<![CDATA[bar]]>', stream_get_contents($stream, - 1, 0));

        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div id="foo"></div>
      </body>
    </html>');

        $dom->getElementById('foo')->appendChild(new \DOMCdataSection("]]>Foo<[![CDATA test ]]>"));

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());
        $list = $dom->getElementsByTagName('div');
        $r->cdata($list->item(0)->childNodes->item(0));

        $this->assertEquals('<![CDATA[]]]]><![CDATA[>Foo<[![CDATA test ]]]]><![CDATA[>]]>', stream_get_contents($stream, - 1, 0));
    }

    public function testComment()
    {
        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div><!-- foo --></div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');
        $r->comment($list->item(0)->childNodes->item(0));
        $this->assertEquals('<!-- foo -->', stream_get_contents($stream, - 1, 0));

        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div id="foo"></div>
      </body>
      </html>');
        $dom->getElementById('foo')->appendChild(new \DOMComment('<!-- --> --> Foo -->'));

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');
        $r->comment($list->item(0)->childNodes->item(0));

        // Could not find more definitive guidelines on what this should be. Went with
        // what the HTML5 spec says and what \DOMDocument::saveXML() produces.
        $this->assertEquals('<!--<!-- --> --> Foo -->-->', stream_get_contents($stream, - 1, 0));
    }

    public function testText()
    {
        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <head>
        <script>baz();</script>
      </head>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('script');
        $r->text($list->item(0)->childNodes->item(0));
        $this->assertEquals('baz();', stream_get_contents($stream, - 1, 0));

        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <head id="foo"></head>
    </html>');
        $foo = $dom->getElementById('foo');
        $foo->appendChild(new \DOMText('<script>alert("hi");</script>'));

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $r->text($foo->firstChild);
        $this->assertEquals('&lt;script&gt;alert("hi");&lt;/script&gt;', stream_get_contents($stream, - 1, 0));
    }

    public function testNl()
    {
        list ($o, $s) = $this->getOutputRules();

        $m = $this->getProtectedMethod('nl');
        $m->invoke($o);
        $this->assertEquals(PHP_EOL, stream_get_contents($s, - 1, 0));
    }

    public function testWr()
    {
        list ($o, $s) = $this->getOutputRules();

        $m = $this->getProtectedMethod('wr');
        $m->invoke($o, 'foo');
        $this->assertEquals('foo', stream_get_contents($s, - 1, 0));
    }

    public function getEncData()
    {
        return array(
            array(
                false,
                '&\'<>"',
                '&amp;\'&lt;&gt;"',
                '&amp;&apos;&lt;&gt;&quot;'
            ),
            array(
                false,
                'This + is. a < test',
                'This + is. a &lt; test',
                'This &plus; is&period; a &lt; test'
            ),
            array(
                false,
                '.+#',
                '.+#',
                '&period;&plus;&num;'
            ),

            array(
                true,
                '.+#\'',
                '.+#\'',
                '&period;&plus;&num;&apos;'
            ),
            array(
                true,
                '&".<',
                '&amp;&quot;.<',
                '&amp;&quot;&period;&lt;'
            ),
            array(
                true,
                '&\'<>"',
                '&amp;\'<>&quot;',
                '&amp;&apos;&lt;&gt;&quot;'
            ),
            array(
                true,
                "\xc2\xa0\"'",
                '&nbsp;&quot;\'',
                '&nbsp;&quot;&apos;'
            )
        );
    }

    /**
     * Test basic encoding of text.
     * @dataProvider getEncData
     */
    public function testEnc($isAttribute, $test, $expected, $expectedEncoded)
    {
        list ($o, $s) = $this->getOutputRules();
        $m = $this->getProtectedMethod('enc');

        $this->assertEquals($expected, $m->invoke($o, $test, $isAttribute));

        list ($o, $s) = $this->getOutputRules(array(
            'encode_entities' => true
        ));
        $m = $this->getProtectedMethod('enc');
        $this->assertEquals($expectedEncoded, $m->invoke($o, $test, $isAttribute));
    }

    /**
     * Test basic encoding of text.
     * @dataProvider getEncData
     */
    public function testEscape($isAttribute, $test, $expected, $expectedEncoded)
    {
        list ($o, $s) = $this->getOutputRules();
        $m = $this->getProtectedMethod('escape');

        $this->assertEquals($expected, $m->invoke($o, $test, $isAttribute));
    }

    public function booleanAttributes()
    {
        return array(
            array('<img alt="" ismap>'),
            array('<img alt="">'),
            array('<input type="radio" readonly>'),
            array('<input type="radio" checked disabled>'),
            array('<input type="checkbox" checked disabled>'),
            array('<input type="radio" value="" checked disabled>'),
            array('<div data-value=""></div>'),
            array('<select disabled></select>'),
            array('<div ng-app></div>'),
            array('<script defer></script>'),
        );
    }
    /**
     * @dataProvider booleanAttributes
     */
    public function testBooleanAttrs($html)
    {
        $dom = $this->html5->loadHTML('<!doctype html><html lang="en"><body>'.$html.'</body></html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $node = $dom->getElementsByTagName('body')->item(0)->firstChild;

        $m = $this->getProtectedMethod('attrs');
        $m->invoke($r, $node);

        $content = stream_get_contents($stream, - 1, 0);

        $html = preg_replace('~<[a-z]+(.*)></[a-z]+>~', '\1', $html);
        $html = preg_replace('~<[a-z]+(.*)/?>~', '\1', $html);

        $this->assertEquals($content, $html);

    }

    public function testAttrs()
    {
        $dom = $this->html5->loadHTML('<!doctype html>
    <html lang="en">
      <body>
        <div id="foo" class="bar baz">foo bar baz</div>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('div');

        $m = $this->getProtectedMethod('attrs');
        $m->invoke($r, $list->item(0));

        $content = stream_get_contents($stream, - 1, 0);
        $this->assertEquals(' id="foo" class="bar baz"', $content);
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
          <rect id="Bar" x="300" y="100" width="300" height="100" fill="rgb(255,255,0)">
            <animate attributeName="x" attributeType="XML" begin="0s" dur="9s" fill="freeze" from="300" to="0" />
          </rect>
        </svg>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('svg');
        $r->element($list->item(0));
        $contents = stream_get_contents($stream, - 1, 0);
        $this->assertRegExp('|<svg width="150" height="100" viewBox="0 0 3 2">|', $contents);
        $this->assertRegExp('|<rect width="1" height="2" x="0" fill="#008d46" />|', $contents);
        $this->assertRegExp('|<rect id="Bar" x="300" y="100" width="300" height="100" fill="rgb\(255,255,0\)">|', $contents);
    }

    public function testMath()
    {
        $dom = $this->html5->loadHTML(
            '<!doctype html>
    <html lang="en">
      <body>
        <div id="foo" class="bar baz">foo bar baz</div>
        <math>
          <mi>x</mi>
          <csymbol definitionURL="http://www.example.com/mathops/multiops.html#plusminus">
            <mo>&PlusMinus;</mo>
          </csymbol>
          <mi>y</mi>
        </math>
      </body>
    </html>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $list = $dom->getElementsByTagName('math');
        $r->element($list->item(0));
        $content = stream_get_contents($stream, - 1, 0);
        $this->assertRegExp('|<math>|', $content);
        $this->assertRegExp('|<csymbol definitionURL="http://www.example.com/mathops/multiops.html#plusminus">|', $content);
    }

    public function testProcessorInstruction()
    {
        $dom = $this->html5->loadHTMLFragment('<?foo bar ?>');

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $r->processorInstruction($dom->firstChild);
        $content = stream_get_contents($stream, - 1, 0);
        $this->assertRegExp('|<\?foo bar \?>|', $content);
    }
}
