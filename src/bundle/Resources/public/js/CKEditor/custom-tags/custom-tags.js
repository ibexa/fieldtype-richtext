import { Plugin } from 'ckeditor5';

import IbexaCustomTagsUI from './block-custom-tag/custom-tag-ui';
import IbexaInlineCustomTagsUI from './inline-custom-tag/inline-custom-tag-ui';
import IbexaCustomTagsEditing from './block-custom-tag/custom-tag-editing';
import IbexaInlineCustomTagsEditing from './inline-custom-tag/inline-custom-tag-editing';
import IbexaCustomTagsToolbar from './block-custom-tag/custom-tag-toolbar';
import IbexaCustomTagSettingsUI from './block-custom-tag/custom-tag-settings-ui';

class IbexaCustomTags extends Plugin {
    static get requires() {
        const blockCustomTags = Object.entries(window.ibexa.richText.customTags).filter(([, config]) => !config.isInline);
        const inlineCustomTags = Object.entries(window.ibexa.richText.customTags).filter(([, config]) => config.isInline);
        const inlineCustomTagsUI = inlineCustomTags.map(([name, config]) => {
            return class InlineCustomTagUI extends IbexaInlineCustomTagsUI {
                constructor(props) {
                    super(props);

                    this.componentName = name;
                    this.config = config;

                    if (!this.config.attributes) {
                        this.config.attributes = {};
                    }

                    this.formView.setChildren(
                        {
                            attributes: this.config.attributes,
                        },
                        window.ibexa.richText.customTags[name].label,
                    );
                }
            };
        });
        const blockCustomTagsUI = blockCustomTags.map(([name, config]) => {
            return class CustomTagUI extends IbexaCustomTagsUI {
                constructor(props) {
                    super(props);

                    this.componentName = name;
                    this.config = config;

                    if (!this.config.attributes) {
                        this.config.attributes = {};
                    }

                    this.formView.setChildren(
                        {
                            attributes: this.config.attributes,
                        },
                        window.ibexa.richText.customTags[name].label,
                    );

                    this.attributesView.setChildren({
                        attributes: this.config.attributes,
                    });
                }
            };
        });

        return [
            ...blockCustomTagsUI,
            ...inlineCustomTagsUI,
            IbexaCustomTagsEditing,
            IbexaInlineCustomTagsEditing,
            IbexaCustomTagsToolbar,
            IbexaCustomTagSettingsUI,
        ];
    }
}

export default IbexaCustomTags;
