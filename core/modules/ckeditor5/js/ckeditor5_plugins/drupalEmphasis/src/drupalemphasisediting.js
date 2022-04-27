/* eslint-disable import/no-extraneous-dependencies */
import { Plugin } from 'ckeditor5/src/core';

/**
 * Alters the italic command to output `<em>` instead of `<i>`.
 *
 * @private
 */
class DrupalEmphasisEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalEmphasisEditing';
  }

  /**
   * @inheritdoc
   */
  init() {
    this.editor.conversion.for('downcast').attributeToElement({
      model: 'italic',
      view: 'em',
      converterPriority: 'high',
    });
  }
}

export default DrupalEmphasisEditing;
