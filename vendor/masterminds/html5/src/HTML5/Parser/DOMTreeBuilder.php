<?php
namespace Masterminds\HTML5\Parser;

use Masterminds\HTML5\Elements;

/**
 * Create an HTML5 DOM tree from events.
 *
 * This attempts to create a DOM from events emitted by a parser. This
 * attempts (but does not guarantee) to up-convert older HTML documents
 * to HTML5. It does this by applying HTML5's rules, but it will not
 * change the architecture of the document itself.
 *
 * Many of the error correction and quirks features suggested in the specification
 * are implemented herein; however, not all of them are. Since we do not
 * assume a graphical user agent, no presentation-specific logic is conducted
 * during tree building.
 *
 * FIXME: The present tree builder does not exactly follow the state machine rules
 * for insert modes as outlined in the HTML5 spec. The processor needs to be
 * re-written to accomodate this. See, for example, the Go language HTML5
 * parser.
 */
class DOMTreeBuilder implements EventHandler
{
    /**
     * Defined in http://www.w3.org/TR/html51/infrastructure.html#html-namespace-0
     */
    const NAMESPACE_HTML = 'http://www.w3.org/1999/xhtml';

    const NAMESPACE_MATHML = 'http://www.w3.org/1998/Math/MathML';

    const NAMESPACE_SVG = 'http://www.w3.org/2000/svg';

    const NAMESPACE_XLINK = 'http://www.w3.org/1999/xlink';

    const NAMESPACE_XML = 'http://www.w3.org/XML/1998/namespace';

    const NAMESPACE_XMLNS = 'http://www.w3.org/2000/xmlns/';

    const OPT_DISABLE_HTML_NS = 'disable_html_ns';

    const OPT_TARGET_DOC = 'target_document';

    const OPT_IMPLICIT_NS = 'implicit_namespaces';

    /**
     * Holds the HTML5 element names that causes a namespace switch
     *
     * @var array
     */
    protected $nsRoots = array(
        'html' => self::NAMESPACE_HTML,
        'svg' => self::NAMESPACE_SVG,
        'math' => self::NAMESPACE_MATHML
    );

    /**
     * Holds the always available namespaces (which does not require the XMLNS declaration).
     *
     * @var array
     */
    protected $implicitNamespaces = array(
        'xml' => self::NAMESPACE_XML,
        'xmlns' => self::NAMESPACE_XMLNS,
        'xlink' => self::NAMESPACE_XLINK
    );

    /**
     * Holds a stack of currently active namespaces.
     *
     * @var array
     */
    protected $nsStack = array();

    /**
     * Holds the number of namespaces declared by a node.
     *
     * @var array
     */
    protected $pushes = array();

    /**
     * Defined in 8.2.5.
     */
    const IM_INITIAL = 0;

    const IM_BEFORE_HTML = 1;

    const IM_BEFORE_HEAD = 2;

    const IM_IN_HEAD = 3;

    const IM_IN_HEAD_NOSCRIPT = 4;

    const IM_AFTER_HEAD = 5;

    const IM_IN_BODY = 6;

    const IM_TEXT = 7;

    const IM_IN_TABLE = 8;

    const IM_IN_TABLE_TEXT = 9;

    const IM_IN_CAPTION = 10;

    const IM_IN_COLUMN_GROUP = 11;

    const IM_IN_TABLE_BODY = 12;

    const IM_IN_ROW = 13;

    const IM_IN_CELL = 14;

    const IM_IN_SELECT = 15;

    const IM_IN_SELECT_IN_TABLE = 16;

    const IM_AFTER_BODY = 17;

    const IM_IN_FRAMESET = 18;

    const IM_AFTER_FRAMESET = 19;

    const IM_AFTER_AFTER_BODY = 20;

    const IM_AFTER_AFTER_FRAMESET = 21;

    const IM_IN_SVG = 22;

    const IM_IN_MATHML = 23;

    protected $options = array();

    protected $stack = array();

    protected $current; // Pointer in the tag hierarchy.
    protected $doc;

    protected $frag;

    protected $processor;

    protected $insertMode = 0;

    /**
     * Track if we are in an element that allows only inline child nodes
     * @var string|null
     */
    protected $onlyInline;

    /**
     * Quirks mode is enabled by default.
     * Any document that is missing the
     * DT will be considered to be in quirks mode.
     */
    protected $quirks = true;

    protected $errors = array();

    public function __construct($isFragment = false, array $options = array())
    {
        $this->options = $options;

        if (isset($options[self::OPT_TARGET_DOC])) {
            $this->doc = $options[self::OPT_TARGET_DOC];
        } else {
            $impl = new \DOMImplementation();
            // XXX:
            // Create the doctype. For now, we are always creating HTML5
            // documents, and attempting to up-convert any older DTDs to HTML5.
            $dt = $impl->createDocumentType('html');
            // $this->doc = \DOMImplementation::createDocument(NULL, 'html', $dt);
            $this->doc = $impl->createDocument(null, null, $dt);
        }
        $this->errors = array();

        $this->current = $this->doc; // ->documentElement;

        // Create a rules engine for tags.
        $this->rules = new TreeBuildingRules($this->doc);

        $implicitNS = array();
        if (isset($this->options[self::OPT_IMPLICIT_NS])) {
            $implicitNS = $this->options[self::OPT_IMPLICIT_NS];
        } elseif (isset($this->options["implicitNamespaces"])) {
            $implicitNS = $this->options["implicitNamespaces"];
        }

        // Fill $nsStack with the defalut HTML5 namespaces, plus the "implicitNamespaces" array taken form $options
        array_unshift($this->nsStack, $implicitNS + array(
            '' => self::NAMESPACE_HTML
        ) + $this->implicitNamespaces);

        if ($isFragment) {
            $this->insertMode = static::IM_IN_BODY;
            $this->frag = $this->doc->createDocumentFragment();
            $this->current = $this->frag;
        }
    }

    /**
     * Get the document.
     */
    public function document()
    {
        return $this->doc;
    }

    /**
     * Get the DOM fragment for the body.
     *
     * This returns a DOMNodeList because a fragment may have zero or more
     * DOMNodes at its root.
     *
     * @see http://www.w3.org/TR/2012/CR-html5-20121217/syntax.html#concept-frag-parse-context
     *
     * @return \DOMFragmentDocumentFragment
     */
    public function fragment()
    {
        return $this->frag;
    }

    /**
     * Provide an instruction processor.
     *
     * This is used for handling Processor Instructions as they are
     * inserted. If omitted, PI's are inserted directly into the DOM tree.
     */
    public function setInstructionProcessor(\Masterminds\HTML5\InstructionProcessor $proc)
    {
        $this->processor = $proc;
    }

    public function doctype($name, $idType = 0, $id = null, $quirks = false)
    {
        // This is used solely for setting quirks mode. Currently we don't
        // try to preserve the inbound DT. We convert it to HTML5.
        $this->quirks = $quirks;

        if ($this->insertMode > static::IM_INITIAL) {
            $this->parseError("Illegal placement of DOCTYPE tag. Ignoring: " . $name);

            return;
        }

        $this->insertMode = static::IM_BEFORE_HTML;
    }

    /**
     * Process the start tag.
     *
     * @todo - XMLNS namespace handling (we need to parse, even if it's not valid)
     *       - XLink, MathML and SVG namespace handling
     *       - Omission rules: 8.1.2.4 Optional tags
     */
    public function startTag($name, $attributes = array(), $selfClosing = false)
    {
        // fprintf(STDOUT, $name);
        $lname = $this->normalizeTagName($name);

        // Make sure we have an html element.
        if (! $this->doc->documentElement && $name !== 'html' && ! $this->frag) {
            $this->startTag('html');
        }

        // Set quirks mode if we're at IM_INITIAL with no doctype.
        if ($this->insertMode == static::IM_INITIAL) {
            $this->quirks = true;
            $this->parseError("No DOCTYPE specified.");
        }

        // SPECIAL TAG HANDLING:
        // Spec says do this, and "don't ask."
        // find the spec where this is defined... looks problematic
        if ($name == 'image' && !($this->insertMode === static::IM_IN_SVG || $this->insertMode === static::IM_IN_MATHML)) {
            $name = 'img';
        }

        // Autoclose p tags where appropriate.
        if ($this->insertMode >= static::IM_IN_BODY && Elements::isA($name, Elements::AUTOCLOSE_P)) {
            $this->autoclose('p');
        }

        // Set insert mode:
        switch ($name) {
            case 'html':
                $this->insertMode = static::IM_BEFORE_HEAD;
                break;
            case 'head':
                if ($this->insertMode > static::IM_BEFORE_HEAD) {
                    $this->parseError("Unexpected head tag outside of head context.");
                } else {
                    $this->insertMode = static::IM_IN_HEAD;
                }
                break;
            case 'body':
                $this->insertMode = static::IM_IN_BODY;
                break;
            case 'svg':
                $this->insertMode = static::IM_IN_SVG;
                break;
            case 'math':
                $this->insertMode = static::IM_IN_MATHML;
                break;
            case 'noscript':
                if ($this->insertMode == static::IM_IN_HEAD) {
                    $this->insertMode = static::IM_IN_HEAD_NOSCRIPT;
                }
                break;
        }

        // Special case handling for SVG.
        if ($this->insertMode == static::IM_IN_SVG) {
            $lname = Elements::normalizeSvgElement($lname);
        }

        $pushes = 0;
        // when we found a tag thats appears inside $nsRoots, we have to switch the defalut namespace
        if (isset($this->nsRoots[$lname]) && $this->nsStack[0][''] !== $this->nsRoots[$lname]) {
            array_unshift($this->nsStack, array(
                '' => $this->nsRoots[$lname]
            ) + $this->nsStack[0]);
            $pushes ++;
        }
        $needsWorkaround = false;
        if (isset($this->options["xmlNamespaces"]) && $this->options["xmlNamespaces"]) {
            // when xmlNamespaces is true a and we found a 'xmlns' or 'xmlns:*' attribute, we should add a new item to the $nsStack
            foreach ($attributes as $aName => $aVal) {
                if ($aName === 'xmlns') {
                    $needsWorkaround = $aVal;
                    array_unshift($this->nsStack, array(
                        '' => $aVal
                    ) + $this->nsStack[0]);
                    $pushes ++;
                } elseif ((($pos = strpos($aName, ':')) ? substr($aName, 0, $pos) : '') === 'xmlns') {
                    array_unshift($this->nsStack, array(
                        substr($aName, $pos + 1) => $aVal
                    ) + $this->nsStack[0]);
                    $pushes ++;
                }
            }
        }

        if ($this->onlyInline && Elements::isA($lname, Elements::BLOCK_TAG)) {
        	$this->autoclose($this->onlyInline);
        	$this->onlyInline = null;
        }

        try {
            $prefix = ($pos = strpos($lname, ':')) ? substr($lname, 0, $pos) : '';


            if ($needsWorkaround!==false) {

                $xml = "<$lname xmlns=\"$needsWorkaround\" ".(strlen($prefix) && isset($this->nsStack[0][$prefix])?("xmlns:$prefix=\"".$this->nsStack[0][$prefix]."\""):"")."/>";

                $frag = new \DOMDocument('1.0', 'UTF-8');
                $frag->loadXML($xml);

                $ele = $this->doc->importNode($frag->documentElement, true);

            } else {
                if (!isset($this->nsStack[0][$prefix]) || ($prefix === "" && isset($this->options[self::OPT_DISABLE_HTML_NS]) && $this->options[self::OPT_DISABLE_HTML_NS])) {
                    $ele = $this->doc->createElement($lname);
                } else {
                    $ele = $this->doc->createElementNS($this->nsStack[0][$prefix], $lname);
                }
            }

        } catch (\DOMException $e) {
            $this->parseError("Illegal tag name: <$lname>. Replaced with <invalid>.");
            $ele = $this->doc->createElement('invalid');
        }

        if (Elements::isA($lname, Elements::BLOCK_ONLY_INLINE)) {
        	$this->onlyInline = $lname;
        }

        // When we add some namespacess, we have to track them. Later, when "endElement" is invoked, we have to remove them.
        // When we are on a void tag, we do not need to care about namesapce nesting.
        if ($pushes > 0 && !Elements::isA($name, Elements::VOID_TAG)) {
            // PHP tends to free the memory used by DOM,
            // to avoid spl_object_hash collisions whe have to avoid garbage collection of $ele storing it into $pushes
            // see https://bugs.php.net/bug.php?id=67459
            $this->pushes[spl_object_hash($ele)] = array($pushes, $ele);

            // SEE https://github.com/facebook/hhvm/issues/2962
            if (defined('HHVM_VERSION')) {
                $ele->setAttribute('html5-php-fake-id-attribute', spl_object_hash($ele));
            }
        }

        foreach ($attributes as $aName => $aVal) {
            // xmlns attributes can't be set
            if ($aName === 'xmlns') {
                continue;
            }

            if ($this->insertMode == static::IM_IN_SVG) {
                $aName = Elements::normalizeSvgAttribute($aName);
            } elseif ($this->insertMode == static::IM_IN_MATHML) {
                $aName = Elements::normalizeMathMlAttribute($aName);
            }

            try {
                $prefix = ($pos = strpos($aName, ':')) ? substr($aName, 0, $pos) : false;

                if ($prefix==='xmlns') {
                    $ele->setAttributeNs(self::NAMESPACE_XMLNS, $aName, $aVal);
                } elseif ($prefix!==false && isset($this->nsStack[0][$prefix])) {
                    $ele->setAttributeNs($this->nsStack[0][$prefix], $aName, $aVal);
                } else {
                    $ele->setAttribute($aName, $aVal);
                }
            } catch (\DOMException $e) {
                $this->parseError("Illegal attribute name for tag $name. Ignoring: $aName");
                continue;
            }

            // This is necessary on a non-DTD schema, like HTML5.
            if ($aName == 'id') {
                $ele->setIdAttribute('id', true);
            }
        }

        // Some elements have special processing rules. Handle those separately.
        if ($this->rules->hasRules($name) && $this->frag !== $this->current) {
            $this->current = $this->rules->evaluate($ele, $this->current);
        }         // Otherwise, it's a standard element.
        else {
            $this->current->appendChild($ele);

            // XXX: Need to handle self-closing tags and unary tags.
            if (! Elements::isA($name, Elements::VOID_TAG)) {
                $this->current = $ele;
            }
        }

        // This is sort of a last-ditch attempt to correct for cases where no head/body
        // elements are provided.
        if ($this->insertMode <= static::IM_BEFORE_HEAD && $name != 'head' && $name != 'html') {
            $this->insertMode = static::IM_IN_BODY;
        }

        // When we are on a void tag, we do not need to care about namesapce nesting,
        // but we have to remove the namespaces pushed to $nsStack.
        if ($pushes > 0 && Elements::isA($name, Elements::VOID_TAG)) {
            // remove the namespaced definded by current node
            for ($i = 0; $i < $pushes; $i ++) {
                array_shift($this->nsStack);
            }
        }
        // Return the element mask, which the tokenizer can then use to set
        // various processing rules.
        return Elements::element($name);
    }

    public function endTag($name)
    {
        $lname = $this->normalizeTagName($name);

        // Ignore closing tags for unary elements.
        if (Elements::isA($name, Elements::VOID_TAG)) {
            return;
        }

        if ($this->insertMode <= static::IM_BEFORE_HTML) {
            // 8.2.5.4.2
            if (in_array($name, array(
                'html',
                'br',
                'head',
                'title'
            ))) {
                $this->startTag('html');
                $this->endTag($name);
                $this->insertMode = static::IM_BEFORE_HEAD;

                return;
            }

            // Ignore the tag.
            $this->parseError("Illegal closing tag at global scope.");

            return;
        }

        // Special case handling for SVG.
        if ($this->insertMode == static::IM_IN_SVG) {
            $lname = Elements::normalizeSvgElement($lname);
        }

        // See https://github.com/facebook/hhvm/issues/2962
        if (defined('HHVM_VERSION') && ($cid = $this->current->getAttribute('html5-php-fake-id-attribute'))) {
            $this->current->removeAttribute('html5-php-fake-id-attribute');
        } else {
            $cid = spl_object_hash($this->current);
        }

        // XXX: Not sure whether we need this anymore.
        // if ($name != $lname) {
        // return $this->quirksTreeResolver($lname);
        // }

        // XXX: HTML has no parent. What do we do, though,
        // if this element appears in the wrong place?
        if ($lname == 'html') {
            return;
        }

        // remove the namespaced definded by current node
        if (isset($this->pushes[$cid])) {
            for ($i = 0; $i < $this->pushes[$cid][0]; $i ++) {
                array_shift($this->nsStack);
            }
            unset($this->pushes[$cid]);
        }

        if (! $this->autoclose($lname)) {
            $this->parseError('Could not find closing tag for ' . $lname);
        }

        // switch ($this->insertMode) {
        switch ($lname) {
            case "head":
                $this->insertMode = static::IM_AFTER_HEAD;
                break;
            case "body":
                $this->insertMode = static::IM_AFTER_BODY;
                break;
            case "svg":
            case "mathml":
                $this->insertMode = static::IM_IN_BODY;
                break;
        }
    }

    public function comment($cdata)
    {
        // TODO: Need to handle case where comment appears outside of the HTML tag.
        $node = $this->doc->createComment($cdata);
        $this->current->appendChild($node);
    }

    public function text($data)
    {
        // XXX: Hmmm.... should we really be this strict?
        if ($this->insertMode < static::IM_IN_HEAD) {
            // Per '8.2.5.4.3 The "before head" insertion mode' the characters
            // " \t\n\r\f" should be ignored but no mention of a parse error. This is
            // practical as most documents contain these characters. Other text is not
            // expected here so recording a parse error is necessary.
            $dataTmp = trim($data, " \t\n\r\f");
            if (! empty($dataTmp)) {
                // fprintf(STDOUT, "Unexpected insert mode: %d", $this->insertMode);
                $this->parseError("Unexpected text. Ignoring: " . $dataTmp);
            }

            return;
        }
        // fprintf(STDOUT, "Appending text %s.", $data);
        $node = $this->doc->createTextNode($data);
        $this->current->appendChild($node);
    }

    public function eof()
    {
        // If the $current isn't the $root, do we need to do anything?
    }

    public function parseError($msg, $line = 0, $col = 0)
    {
        $this->errors[] = sprintf("Line %d, Col %d: %s", $line, $col, $msg);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function cdata($data)
    {
        $node = $this->doc->createCDATASection($data);
        $this->current->appendChild($node);
    }

    public function processingInstruction($name, $data = null)
    {
        // XXX: Ignore initial XML declaration, per the spec.
        if ($this->insertMode == static::IM_INITIAL && 'xml' == strtolower($name)) {
            return;
        }

        // Important: The processor may modify the current DOM tree however
        // it sees fit.
        if (isset($this->processor)) {
            $res = $this->processor->process($this->current, $name, $data);
            if (! empty($res)) {
                $this->current = $res;
            }

            return;
        }

        // Otherwise, this is just a dumb PI element.
        $node = $this->doc->createProcessingInstruction($name, $data);

        $this->current->appendChild($node);
    }

    // ==========================================================================
    // UTILITIES
    // ==========================================================================

    /**
     * Apply normalization rules to a tag name.
     *
     * See sections 2.9 and 8.1.2.
     *
     * @param string $name
     *            The tag name.
     * @return string The normalized tag name.
     */
    protected function normalizeTagName($name)
    {
        /*
         * Section 2.9 suggests that we should not do this. if (strpos($name, ':') !== false) { // We know from the grammar that there must be at least one other // char besides :, since : is not a legal tag start. $parts = explode(':', $name); return array_pop($parts); }
         */
        return $name;
    }

    protected function quirksTreeResolver($name)
    {
        throw new \Exception("Not implemented.");
    }

    /**
     * Automatically climb the tree and close the closest node with the matching $tag.
     */
    protected function autoclose($tag)
    {
        $working = $this->current;
        do {
            if ($working->nodeType != XML_ELEMENT_NODE) {
                return false;
            }
            if ($working->tagName == $tag) {
                $this->current = $working->parentNode;

                return true;
            }
        } while ($working = $working->parentNode);
        return false;
    }

    /**
     * Checks if the given tagname is an ancestor of the present candidate.
     *
     * If $this->current or anything above $this->current matches the given tag
     * name, this returns true.
     */
    protected function isAncestor($tagname)
    {
        $candidate = $this->current;
        while ($candidate->nodeType === XML_ELEMENT_NODE) {
            if ($candidate->tagName == $tagname) {
                return true;
            }
            $candidate = $candidate->parentNode;
        }

        return false;
    }

    /**
     * Returns true if the immediate parent element is of the given tagname.
     */
    protected function isParent($tagname)
    {
        return $this->current->tagName == $tagname;
    }
}
