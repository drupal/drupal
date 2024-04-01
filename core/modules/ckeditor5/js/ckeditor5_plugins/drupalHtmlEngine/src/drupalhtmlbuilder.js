// cspell:ignore apos

/**
 * HTML builder that converts document fragments into strings.
 *
 * Escapes ampersand characters (`&`) and angle brackets (`<` and `>`) when
 * transforming data to HTML. This is required because
 * \Drupal\Component\Utility\Xss::filter fails to parse element attributes
 * values containing unescaped HTML entities.
 *
 * @see https://www.drupal.org/project/drupal/issues/3227831
 * @see DrupalHtmlBuilder._escapeAttribute
 *
 * @private
 */
export default class DrupalHtmlBuilder {
  /**
   * Constructs a new object.
   */
  constructor() {
    this.chunks = [];
    // @see https://html.spec.whatwg.org/multipage/syntax.html#elements-2
    this.selfClosingTags = [
      'area',
      'base',
      'br',
      'col',
      'embed',
      'hr',
      'img',
      'input',
      'link',
      'meta',
      'param',
      'source',
      'track',
      'wbr',
    ];

    // @see https://html.spec.whatwg.org/multipage/syntax.html#raw-text-elements
    this.rawTags = ['script', 'style'];
  }

  /**
   * Returns the current HTML string built from document fragments.
   *
   * @return {string}
   *   The HTML string built from document fragments.
   */
  build() {
    return this.chunks.join('');
  }

  /**
   * Converts a document fragment into an HTML string appended to the value.
   *
   * @param {DocumentFragment} node
   *   A document fragment to be appended to the value.
   */
  appendNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      this._appendText(node);
    } else if (node.nodeType === Node.ELEMENT_NODE) {
      this._appendElement(node);
    } else if (node.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
      this._appendChildren(node);
    } else if (node.nodeType === Node.COMMENT_NODE) {
      this._appendComment(node);
    }
  }

  /**
   * Appends an element node to the value.
   *
   * @param {DocumentFragment} node
   *   A document fragment to be appended to the value.
   *
   * @private
   */
  _appendElement(node) {
    const nodeName = node.nodeName.toLowerCase();

    this._append('<');
    this._append(nodeName);
    this._appendAttributes(node);
    this._append('>');
    if (!this.selfClosingTags.includes(nodeName)) {
      this._appendChildren(node);
      this._append('</');
      this._append(nodeName);
      this._append('>');
    }
  }

  /**
   * Appends child nodes to the value.
   *
   * @param {DocumentFragment} node
   *  A document fragment to be appended to the value.
   *
   * @private
   */
  _appendChildren(node) {
    Object.keys(node.childNodes).forEach((child) => {
      this.appendNode(node.childNodes[child]);
    });
  }

  /**
   * Appends attributes to the value.
   *
   * @param {DocumentFragment} node
   *  A document fragment to be appended to the value.
   *
   * @private
   */
  _appendAttributes(node) {
    Object.keys(node.attributes).forEach((attr) => {
      this._append(' ');
      this._append(node.attributes[attr].name);
      this._append('="');
      this._append(
        this.constructor._escapeAttribute(node.attributes[attr].value),
      );
      this._append('"');
    });
  }

  /**
   * Appends text to the value.
   *
   * @param {DocumentFragment} node
   *  A document fragment to be appended to the value.
   *
   * @private
   */
  _appendText(node) {
    // Repack the text into another node and extract using innerHTML. This
    // works around text nodes not having an innerHTML property and textContent
    // not encoding entities.
    // entities. That's why the text is repacked into another node and extracted
    // using innerHTML.
    const doc = document.implementation.createHTMLDocument('');
    const container = doc.createElement('p');
    container.textContent = node.textContent;

    if (
      node.parentElement &&
      this.rawTags.includes(node.parentElement.tagName.toLowerCase())
    ) {
      this._append(container.textContent);
    } else {
      this._append(container.innerHTML);
    }
  }

  /**
   * Appends a comment to the value.
   *
   * @param {DocumentFragment} node
   *  A document fragment to be appended to the value.
   *
   * @private
   */
  _appendComment(node) {
    this._append('<!--');
    this._append(node.textContent);
    this._append('-->');
  }

  /**
   * Appends a string to the value.
   *
   * @param {string} str
   *  A string to be appended to the value.
   *
   * @private
   */
  _append(str) {
    this.chunks.push(str);
  }

  /**
   * Escapes attribute value for compatibility with Drupal's XSS filtering.
   *
   * Drupal's XSS filtering cannot handle entities inside element attribute
   * values. The XSS filtering was written based on W3C XML recommendations
   * which constituted that the ampersand character (&) and the angle
   * brackets (< and >) must not appear in their literal form in attribute
   * values. This differs from the HTML living standard which permits angle
   * brackets.
   *
   * @param {string} text
   *  A string to be escaped.
   *
   * @return {string}
   *  Escaped string.
   *
   * @see https://www.w3.org/TR/2008/REC-xml-20081126/#NT-AttValue
   * @see https://html.spec.whatwg.org/multipage/parsing.html#attribute-value-(single-quoted)-state
   * @see https://www.drupal.org/project/drupal/issues/3227831
   *
   * @private
   */
  static _escapeAttribute(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/'/g, '&apos;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\r\n/g, '&#13;')
      .replace(/[\r\n]/g, '&#13;');
  }
}
