<?php
namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\UTF8Utils;
use Masterminds\HTML5\Parser\StringInputStream;
use Masterminds\HTML5\Parser\Scanner;
use Masterminds\HTML5\Parser\Tokenizer;

class TokenizerTest extends \Masterminds\HTML5\Tests\TestCase
{
    // ================================================================
    // Additional assertions.
    // ================================================================
    /**
     * Tests that an event matches both the event type and the expected value.
     *
     * @param string $type
     *            Expected event type.
     * @param string $expects
     *            The value expected in $event['data'][0].
     */
    public function assertEventEquals($type, $expects, $event)
    {
        $this->assertEquals($type, $event['name'], "Event $type for " . print_r($event, true));
        if (is_array($expects)) {
            $this->assertEquals($expects, $event['data'], "Event $type should equal " . print_r($expects, true) . ": " . print_r($event, true));
        } else {
            $this->assertEquals($expects, $event['data'][0], "Event $type should equal $expects: " . print_r($event, true));
        }
    }

    /**
     * Assert that a given event is 'error'.
     */
    public function assertEventError($event)
    {
        $this->assertEquals('error', $event['name'], "Expected error for event: " . print_r($event, true));
    }

    /**
     * Asserts that all of the tests are good.
     *
     * This loops through a map of tests/expectations and runs a few assertions on each test.
     *
     * Checks:
     * - depth (if depth is > 0)
     * - event name
     * - matches on event 0.
     */
    protected function isAllGood($name, $depth, $tests, $debug = false)
    {
        foreach ($tests as $try => $expects) {
            if ($debug) {
                fprintf(STDOUT, "%s expects %s\n", $try, print_r($expects, true));
            }
            $e = $this->parse($try);
            if ($depth > 0) {
                $this->assertEquals($depth, $e->depth(), "Expected depth $depth for test $try." . print_r($e, true));
            }
            $this->assertEventEquals($name, $expects, $e->get(0));
        }
    }

    // ================================================================
    // Utility functions.
    // ================================================================
    public function testParse()
    {
        list ($tok, $events) = $this->createTokenizer('');

        $tok->parse();
        $e1 = $events->get(0);

        $this->assertEquals(1, $events->Depth());
        $this->assertEquals('eof', $e1['name']);
    }

    public function testWhitespace()
    {
        $spaces = '    ';
        list ($tok, $events) = $this->createTokenizer($spaces);

        $tok->parse();

        $this->assertEquals(2, $events->depth());

        $e1 = $events->get(0);

        $this->assertEquals('text', $e1['name']);
        $this->assertEquals($spaces, $e1['data'][0]);
    }

    public function testCharacterReference()
    {
        $good = array(
            '&amp;' => '&',
            '&#x0003c;' => '<',
            '&#38;' => '&',
            '&' => '&'
        );
        $this->isAllGood('text', 2, $good);

        // Test with broken charref
        $str = '&foo';
        $events = $this->parse($str);
        $e1 = $events->get(0);
        $this->assertEquals('error', $e1['name']);

        $str = '&#xfoo';
        $events = $this->parse($str);
        $e1 = $events->get(0);
        $this->assertEquals('error', $e1['name']);

        $str = '&#foo';
        $events = $this->parse($str);
        $e1 = $events->get(0);
        $this->assertEquals('error', $e1['name']);

        // FIXME: Once the text processor is done, need to verify that the
        // tokens are transformed correctly into text.
    }

    public function testBogusComment()
    {
        $bogus = array(
            '</+this is a bogus comment. +>',
            '<!+this is a bogus comment. !>',
            '<!D OCTYPE foo bar>',
            '<!DOCTYEP foo bar>',
            '<![CADATA[ TEST ]]>',
            '<![CDATA Hello ]]>',
            '<![CDATA[ Hello [[>',
            '<!CDATA[[ test ]]>',
            '<![CDATA[',
            '<![CDATA[hellooooo hello',
            '<? Hello World ?>',
            '<? Hello World'
        );
        foreach ($bogus as $str) {
            $events = $this->parse($str);
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('comment', $str, $events->get(1));
        }
    }

    public function testEndTag()
    {
        $succeed = array(
            '</a>' => 'a',
            '</test>' => 'test',
            '</test
      >' => 'test',
            '</thisIsTheTagThatDoesntEndItJustGoesOnAndOnMyFriend>' => 'thisisthetagthatdoesntenditjustgoesonandonmyfriend',
            // See 8.2.4.10, which requires this and does not say error.
            '</a<b>' => 'a<b'
        );
        $this->isAllGood('endTag', 2, $succeed);

        // Recoverable failures
        $fail = array(
            '</a class="monkey">' => 'a',
            '</a <b>' => 'a',
            '</a <b <c>' => 'a',
            '</a is the loneliest letter>' => 'a',
            '</a' => 'a'
        );
        foreach ($fail as $test => $result) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth());
            // Should have triggered an error.
            $this->assertEventError($events->get(0));
            // Should have tried to parse anyway.
            $this->assertEventEquals('endTag', $result, $events->get(1));
        }

        // BogoComments
        $comments = array(
            '</>' => '</>',
            '</ >' => '</ >',
            '</ a>' => '</ a>'
        );
        foreach ($comments as $test => $result) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth());

            // Should have triggered an error.
            $this->assertEventError($events->get(0));

            // Should have tried to parse anyway.
            $this->assertEventEquals('comment', $result, $events->get(1));
        }
    }

    public function testComment()
    {
        $good = array(
            '<!--easy-->' => 'easy',
            '<!-- 1 > 0 -->' => ' 1 > 0 ',
            '<!-- --$i -->' => ' --$i ',
            '<!----$i-->' => '--$i',
            '<!-- 1 > 0 -->' => ' 1 > 0 ',
            "<!--\nHello World.\na-->" => "\nHello World.\na",
            '<!-- <!-- -->' => ' <!-- '
        );
        foreach ($good as $test => $expected) {
            $events = $this->parse($test);
            $this->assertEventEquals('comment', $expected, $events->get(0));
        }

        $fail = array(
            '<!-->' => '',
            '<!--Hello' => 'Hello',
            "<!--\0Hello" => UTF8Utils::FFFD . 'Hello',
            '<!--' => ''
        );
        foreach ($fail as $test => $expected) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth());
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('comment', $expected, $events->get(1));
        }
    }

    public function testCDATASection()
    {
        $good = array(
            '<![CDATA[ This is a test. ]]>' => ' This is a test. ',
            '<![CDATA[CDATA]]>' => 'CDATA',
            '<![CDATA[ ]] > ]]>' => ' ]] > ',
            '<![CDATA[ ]]>' => ' '
        );
        $this->isAllGood('cdata', 2, $good);
    }

    public function testDoctype()
    {
        $good = array(
            '<!DOCTYPE html>' => array(
                'html',
                0,
                null,
                false
            ),
            '<!doctype html>' => array(
                'html',
                0,
                null,
                false
            ),
            '<!DocType html>' => array(
                'html',
                0,
                null,
                false
            ),
            "<!DOCTYPE\nhtml>" => array(
                'html',
                0,
                null,
                false
            ),
            "<!DOCTYPE\fhtml>" => array(
                'html',
                0,
                null,
                false
            ),
            '<!DOCTYPE html PUBLIC "foo bar">' => array(
                'html',
                EventStack::DOCTYPE_PUBLIC,
                'foo bar',
                false
            ),
            "<!DOCTYPE html PUBLIC 'foo bar'>" => array(
                'html',
                EventStack::DOCTYPE_PUBLIC,
                'foo bar',
                false
            ),
            '<!DOCTYPE      html      PUBLIC     "foo bar"    >' => array(
                'html',
                EventStack::DOCTYPE_PUBLIC,
                'foo bar',
                false
            ),
            "<!DOCTYPE html \nPUBLIC\n'foo bar'>" => array(
                'html',
                EventStack::DOCTYPE_PUBLIC,
                'foo bar',
                false
            ),
            '<!DOCTYPE html SYSTEM "foo bar">' => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo bar',
                false
            ),
            "<!DOCTYPE html SYSTEM 'foo bar'>" => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo bar',
                false
            ),
            '<!DOCTYPE      html      SYSTEM "foo/bar"    >' => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo/bar',
                false
            ),
            "<!DOCTYPE html \nSYSTEM\n'foo bar'>" => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo bar',
                false
            )
        );
        $this->isAllGood('doctype', 2, $good);

        $bad = array(
            '<!DOCTYPE>' => array(
                null,
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE    >' => array(
                null,
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE  foo' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE foo PUB' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE foo PUB>' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE  foo PUB "Looks good">' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE  foo SYSTME "Looks good"' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),

            // Can't tell whether these are ids or ID types, since the context is chopped.
            '<!DOCTYPE foo PUBLIC' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE  foo PUBLIC>' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE foo SYSTEM' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),
            '<!DOCTYPE  foo SYSTEM>' => array(
                'foo',
                EventStack::DOCTYPE_NONE,
                null,
                true
            ),

            '<!DOCTYPE html SYSTEM "foo bar"' => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo bar',
                true
            ),
            '<!DOCTYPE html SYSTEM "foo bar" more stuff>' => array(
                'html',
                EventStack::DOCTYPE_SYSTEM,
                'foo bar',
                true
            )
        );
        foreach ($bad as $test => $expects) {
            $events = $this->parse($test);
            // fprintf(STDOUT, $test . PHP_EOL);
            $this->assertEquals(3, $events->depth(), "Counting events for '$test': " . print_r($events, true));
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('doctype', $expects, $events->get(1));
        }
    }

    public function testProcessorInstruction()
    {
        $good = array(
            '<?hph ?>' => 'hph',
            '<?hph echo "Hello World"; ?>' => array(
                'hph',
                'echo "Hello World"; '
            ),
            "<?hph \necho 'Hello World';\n?>" => array(
                'hph',
                "echo 'Hello World';\n"
            )
        );
        $this->isAllGood('pi', 2, $good);
    }

    /**
     * This tests just simple tags.
     */
    public function testSimpleTags()
    {
        $open = array(
            '<foo>' => 'foo',
            '<FOO>' => 'foo',
            '<fOO>' => 'foo',
            '<foo >' => 'foo',
            "<foo\n\n\n\n>" => 'foo',
            '<foo:bar>' => 'foo:bar'
        );
        $this->isAllGood('startTag', 2, $open);

        $selfClose = array(
            '<foo/>' => 'foo',
            '<FOO/>' => 'foo',
            '<foo />' => 'foo',
            "<foo\n\n\n\n/>" => 'foo',
            '<foo:bar/>' => 'foo:bar'
        );
        foreach ($selfClose as $test => $expects) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth(), "Counting events for '$test'" . print_r($events, true));
            $this->assertEventEquals('startTag', $expects, $events->get(0));
            $this->assertEventEquals('endTag', $expects, $events->get(1));
        }

        $bad = array(
            '<foo' => 'foo',
            '<foo ' => 'foo',
            '<foo/' => 'foo',
            '<foo /' => 'foo'
        );

        foreach ($bad as $test => $expects) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth(), "Counting events for '$test': " . print_r($events, true));
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('startTag', $expects, $events->get(1));
        }
    }

    public function testTagsWithAttributeAndMissingName()
    {
        $cases = array(
            '<id="top_featured">' => 'id',
            '<color="white">' => 'color',
            "<class='neaktivni_stranka'>" => 'class',
            '<bgcolor="white">' => 'bgcolor',
            '<class="nom">' => 'class'
        );

        foreach ($cases as $html => $expected) {
            $events = $this->parse($html);
            $this->assertEventError($events->get(0));
            $this->assertEventError($events->get(1));
            $this->assertEventError($events->get(2));
            $this->assertEventEquals('startTag', $expected, $events->get(3));
            $this->assertEventEquals('eof', null, $events->get(4));
        }
    }

    public function testTagNotClosedAfterTagName()
    {
        $cases = array(
            "<noscript<img>" => array(
                'noscript',
                'img'
            ),
            '<center<a>' => array(
                'center',
                'a'
            ),
            '<br<br>' => array(
                'br',
                'br'
            )
        );

        foreach ($cases as $html => $expected) {
            $events = $this->parse($html);
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('startTag', $expected[0], $events->get(1));
            $this->assertEventEquals('startTag', $expected[1], $events->get(2));
            $this->assertEventEquals('eof', null, $events->get(3));
        }

        $events = $this->parse('<span<>02</span>');
        $this->assertEventError($events->get(0));
        $this->assertEventEquals('startTag', 'span', $events->get(1));
        $this->assertEventError($events->get(2));
        $this->assertEventEquals('text', '>02', $events->get(3));
        $this->assertEventEquals('endTag', 'span', $events->get(4));
        $this->assertEventEquals('eof', null, $events->get(5));

        $events = $this->parse('<p</p>');
        $this->assertEventError($events->get(0));
        $this->assertEventEquals('startTag', 'p', $events->get(1));
        $this->assertEventEquals('endTag', 'p', $events->get(2));
        $this->assertEventEquals('eof', null, $events->get(3));

        $events = $this->parse('<strong><WordPress</strong>');
        $this->assertEventEquals('startTag', 'strong', $events->get(0));
        $this->assertEventError($events->get(1));
        $this->assertEventEquals('startTag', 'wordpress', $events->get(2));
        $this->assertEventEquals('endTag', 'strong', $events->get(3));
        $this->assertEventEquals('eof', null, $events->get(4));

        $events = $this->parse('<src=<a>');
        $this->assertEventError($events->get(0));
        $this->assertEventError($events->get(1));
        $this->assertEventError($events->get(2));
        $this->assertEventEquals('startTag', 'src', $events->get(3));
        $this->assertEventEquals('startTag', 'a', $events->get(4));
        $this->assertEventEquals('eof', null, $events->get(5));

        $events = $this->parse('<br...<a>');
        $this->assertEventError($events->get(0));
        $this->assertEventEquals('startTag', 'br', $events->get(1));
        $this->assertEventEquals('eof', null, $events->get(2));
    }

    public function testIllegalTagNames()
    {
        $cases = array(
            '<li">' => 'li',
            '<p">' => 'p',
            '<b&nbsp; >' => 'b',
            '<static*all>' => 'static',
            '<h*0720/>' => 'h',
            '<st*ATTRIBUTE />' => 'st',
        );

        foreach ($cases as $html => $expected) {
            $events = $this->parse($html);
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('startTag', $expected, $events->get(1));
        }
    }

    /**
     * @depends testCharacterReference
     */
    public function testTagAttributes()
    {
        // Opening tags.
        $good = array(
            '<foo bar="baz">' => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                false
            ),
            '<foo bar=" baz ">' => array(
                'foo',
                array(
                    'bar' => ' baz '
                ),
                false
            ),
            "<foo bar=\"\nbaz\n\">" => array(
                'foo',
                array(
                    'bar' => "\nbaz\n"
                ),
                false
            ),
            "<foo bar='baz'>" => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                false
            ),
            '<foo bar="A full sentence.">' => array(
                'foo',
                array(
                    'bar' => 'A full sentence.'
                ),
                false
            ),
            "<foo a='1' b=\"2\">" => array(
                'foo',
                array(
                    'a' => '1',
                    'b' => '2'
                ),
                false
            ),
            "<foo ns:bar='baz'>" => array(
                'foo',
                array(
                    'ns:bar' => 'baz'
                ),
                false
            ),
            "<foo a='blue&amp;red'>" => array(
                'foo',
                array(
                    'a' => 'blue&red'
                ),
                false
            ),
            "<foo a='blue&&amp;red'>" => array(
                'foo',
                array(
                    'a' => 'blue&&red'
                ),
                false
            ),
            "<foo\nbar='baz'\n>" => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                false
            ),
            '<doe a deer>' => array(
                'doe',
                array(
                    'a' => null,
                    'deer' => null
                ),
                false
            ),
            '<foo bar=baz>' => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                false
            ),

            // Updated for 8.1.2.3
            '<foo    bar   =   "baz"      >' => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                false
            ),

            // The spec allows an unquoted value '/'. This will not be a closing
            // tag.
            '<foo bar=/>' => array(
                'foo',
                array(
                    'bar' => '/'
                ),
                false
            ),
            '<foo bar=baz/>' => array(
                'foo',
                array(
                    'bar' => 'baz/'
                ),
                false
            )
        );
        $this->isAllGood('startTag', 2, $good);

        // Self-closing tags.
        $withEnd = array(
            '<foo bar="baz"/>' => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                true
            ),
            '<foo BAR="baz"/>' => array(
                'foo',
                array(
                    'bar' => 'baz'
                ),
                true
            ),
            '<foo BAR="BAZ"/>' => array(
                'foo',
                array(
                    'bar' => 'BAZ'
                ),
                true
            ),
            "<foo a='1' b=\"2\" c=3 d/>" => array(
                'foo',
                array(
                    'a' => '1',
                    'b' => '2',
                    'c' => '3',
                    'd' => null
                ),
                true
            )
        );
        $this->isAllGood('startTag', 3, $withEnd);

        // Cause a parse error.
        $bad = array(
            // This will emit an entity lookup failure for &red.
            "<foo a='blue&red'>" => array(
                'foo',
                array(
                    'a' => 'blue&red'
                ),
                false
            ),
            "<foo a='blue&&amp;&red'>" => array(
                'foo',
                array(
                    'a' => 'blue&&&red'
                ),
                false
            ),
            '<foo bar=>' => array(
                'foo',
                array(
                    'bar' => null
                ),
                false
            ),
            '<foo bar="oh' => array(
                'foo',
                array(
                    'bar' => 'oh'
                ),
                false
            ),
            '<foo bar=oh">' => array(
                'foo',
                array(
                    'bar' => 'oh"'
                ),
                false
            ),

            // these attributes are ignored because of current implementation
            // of method "DOMElement::setAttribute"
            // see issue #23: https://github.com/Masterminds/html5-php/issues/23
            '<foo b"="baz">' => array(
                'foo',
                array(),
                false
            ),
            '<foo 2abc="baz">' => array(
                'foo',
                array(),
                false
            ),
            '<foo ?="baz">' => array(
                'foo',
                array(),
                false
            ),
            '<foo foo?bar="baz">' => array(
                'foo',
                array(),
                false
            )
        )
        ;
        foreach ($bad as $test => $expects) {
            $events = $this->parse($test);
            $this->assertEquals(3, $events->depth(), "Counting events for '$test': " . print_r($events, true));
            $this->assertEventError($events->get(0));
            $this->assertEventEquals('startTag', $expects, $events->get(1));
        }

        // Cause multiple parse errors.
        $reallyBad = array(
            '<foo ="bar">' => array(
                'foo',
                array(
                    '=' => null,
                    '"bar"' => null
                ),
                false
            ),
            '<foo////>' => array(
                'foo',
                array(),
                true
            ),
            // character "&" in unquoted attribute shouldn't cause an infinite loop
            '<foo bar=index.php?str=1&amp;id=29>' => array(
                'foo',
                array(
                    'bar' => 'index.php?str=1&id=29'
                ),
                false
            )
        );
        foreach ($reallyBad as $test => $expects) {
            $events = $this->parse($test);
            // fprintf(STDOUT, $test . print_r($events, true));
            $this->assertEventError($events->get(0));
            $this->assertEventError($events->get(1));
            // $this->assertEventEquals('startTag', $expects, $events->get(1));
        }

        // Regression: Malformed elements should be detected.
        // '<foo baz="1" <bar></foo>' => array('foo', array('baz' => '1'), false),
        $events = $this->parse('<foo baz="1" <bar></foo>');
        $this->assertEventError($events->get(0));
        $this->assertEventEquals('startTag', array(
            'foo',
            array(
                'baz' => '1'
            ),
            false
        ), $events->get(1));
        $this->assertEventEquals('startTag', array(
            'bar',
            array(),
            false
        ), $events->get(2));
        $this->assertEventEquals('endTag', array(
            'foo'
        ), $events->get(3));
    }

    public function testRawText()
    {
        $good = array(
            '<script>abcd efg hijk lmnop</script>     ' => 'abcd efg hijk lmnop',
            '<script><not/><the/><tag></script>' => '<not/><the/><tag>',
            '<script><<<<<<<<</script>' => '<<<<<<<<',
            '<script>hello</script</script>' => 'hello</script',
            "<script>\nhello</script\n</script>" => "\nhello</script\n",
            '<script>&amp;</script>' => '&amp;',
            '<script><!--not a comment--></script>' => '<!--not a comment-->',
            '<script><![CDATA[not a comment]]></script>' => '<![CDATA[not a comment]]>'
        );
        foreach ($good as $test => $expects) {
            $events = $this->parse($test);
            $this->assertEventEquals('startTag', 'script', $events->get(0));
            $this->assertEventEquals('text', $expects, $events->get(1));
            $this->assertEventEquals('endTag', 'script', $events->get(2));
        }

        $bad = array(
            '<script>&amp;</script' => '&amp;</script',
            '<script>Hello world' => 'Hello world'
        );
        foreach ($bad as $test => $expects) {
            $events = $this->parse($test);
            $this->assertEquals(4, $events->depth(), "Counting events for '$test': " . print_r($events, true));
            $this->assertEventEquals('startTag', 'script', $events->get(0));
            $this->assertEventError($events->get(1));
            $this->assertEventEquals('text', $expects, $events->get(2));
        }

        // Testing case sensitivity
        $events = $this->parse('<TITLE>a test</TITLE>');
        $this->assertEventEquals('startTag', 'title', $events->get(0));
        $this->assertEventEquals('text', 'a test', $events->get(1));
        $this->assertEventEquals('endTag', 'title', $events->get(2));

        // Testing end tags with whitespaces
        $events = $this->parse('<title>Whitespaces are tasty</title >');
        $this->assertEventEquals('startTag', 'title', $events->get(0));
        $this->assertEventEquals('text', 'Whitespaces are tasty', $events->get(1));
        $this->assertEventEquals('endTag', 'title', $events->get(2));
    }

    public function testRcdata()
    {
        list ($tok, $events) = $this->createTokenizer('<title>&#x27;<!-- not a comment --></TITLE>');
        $tok->setTextMode(\Masterminds\HTML5\Elements::TEXT_RCDATA, 'title');
        $tok->parse();
        $this->assertEventEquals('text', "'<!-- not a comment -->", $events->get(1));
    }

    public function testText()
    {
        $events = $this->parse('a<br>b');
        $this->assertEquals(4, $events->depth(), "Events: " . print_r($events, true));
        $this->assertEventEquals('text', 'a', $events->get(0));
        $this->assertEventEquals('startTag', 'br', $events->get(1));
        $this->assertEventEquals('text', 'b', $events->get(2));

        $events = $this->parse('<a>Test</a>');
        $this->assertEquals(4, $events->depth(), "Events: " . print_r($events, true));
        $this->assertEventEquals('startTag', 'a', $events->get(0));
        $this->assertEventEquals('text', 'Test', $events->get(1));
        $this->assertEventEquals('endTag', 'a', $events->get(2));

        $events = $this->parse('<p>0</p><p>1</p>');
        $this->assertEquals(7, $events->depth(), "Events: " . print_r($events, true));

        $this->assertEventEquals('startTag', 'p', $events->get(0));
        $this->assertEventEquals('text', '0', $events->get(1));
        $this->assertEventEquals('endTag', 'p', $events->get(2));

        $this->assertEventEquals('startTag', 'p', $events->get(3));
        $this->assertEventEquals('text', '1', $events->get(4));
        $this->assertEventEquals('endTag', 'p', $events->get(5));


        $events = $this->parse('a<![CDATA[test]]>b');
        $this->assertEquals(4, $events->depth(), "Events: " . print_r($events, true));
        $this->assertEventEquals('text', 'a', $events->get(0));
        $this->assertEventEquals('cdata', 'test', $events->get(1));
        $this->assertEventEquals('text', 'b', $events->get(2));

        $events = $this->parse('a<!--test-->b');
        $this->assertEquals(4, $events->depth(), "Events: " . print_r($events, true));
        $this->assertEventEquals('text', 'a', $events->get(0));
        $this->assertEventEquals('comment', 'test', $events->get(1));
        $this->assertEventEquals('text', 'b', $events->get(2));

        $events = $this->parse('a&amp;b');
        $this->assertEquals(2, $events->depth(), "Events: " . print_r($events, true));
        $this->assertEventEquals('text', 'a&b', $events->get(0));
    }

    // ================================================================
    // Utility functions.
    // ================================================================
    protected function createTokenizer($string, $debug = false)
    {
        $eventHandler = new EventStack();
        $stream = new StringInputStream($string);
        $scanner = new Scanner($stream);

        $scanner->debug = $debug;

        return array(
            new Tokenizer($scanner, $eventHandler),
            $eventHandler
        );
    }

    public function parse($string, $debug = false)
    {
        list ($tok, $events) = $this->createTokenizer($string, $debug);
        $tok->parse();

        return $events;
    }
}
