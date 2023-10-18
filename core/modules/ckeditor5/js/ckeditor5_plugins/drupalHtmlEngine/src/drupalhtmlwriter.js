// cspell:ignore drupalhtmlbuilder dataprocessor basichtmlwriter htmlwriter
import DrupalHtmlBuilder from './drupalhtmlbuilder';

/**
 * Custom HTML writer. It creates HTML by traversing DOM nodes.
 *
 * It differs to BasicHtmlWriter in the way it encodes entities in element
 * attributes.
 *
 * @see module:engine/dataprocessor/basichtmlwriter~BasicHtmlWriter
 * @implements module:engine/dataprocessor/htmlwriter~HtmlWriter
 *
 * @see https://www.drupal.org/project/drupal/issues/3227831
 *
 * @private
 */
export default class DrupalHtmlWriter {
  /**
   * Returns an HTML string created from the document fragment.
   *
   * @param {DocumentFragment} fragment
   * @return {String}
   */
  // eslint-disable-next-line class-methods-use-this
  getHtml(fragment) {
    const builder = new DrupalHtmlBuilder();
    builder.appendNode(fragment);

    return builder.build();
  }
}
