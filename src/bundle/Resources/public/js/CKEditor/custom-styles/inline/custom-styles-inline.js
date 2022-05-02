import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaCustomStylesInlineUI from './custom-style-inline-ui';
import IbexaCustomStylesInlineEditing from './custom-style-inline-editing';

class IbexaCustomStylesInline extends Plugin {
    static get requires() {
        const customStylesInline = Object.entries(window.ibexa.richText.customStyles).filter(([, config]) => config.inline);
        const customStylesInlineEditing = customStylesInline.map(([name]) => {
            return class CustomStyleEditing extends IbexaCustomStylesInlineEditing {
                constructor(props) {
                    super(props);

                    this.customStyleName = name;
                }
            };
        });

        return [IbexaCustomStylesInlineUI, ...customStylesInlineEditing];
    }
}

export default IbexaCustomStylesInline;
