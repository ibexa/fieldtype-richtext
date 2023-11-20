import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaCustomAttributesCommand from './custom-attributes-command';
import { getCustomAttributesConfig, getCustomClassesConfig } from './helpers/config-helper';

class IbexaCustomAttributesEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;
        const customAttributesConfig = getCustomAttributesConfig();

        conversion.attributeToAttribute({
            model: {
                key: 'custom-classes',
            },
            view: {
                key: 'class',
            },
        });

        Object.values(customAttributesConfig).forEach((customAttributes) => {
            Object.keys(customAttributes).forEach((customAttributeName) => {
                conversion.attributeToAttribute({
                    model: {
                        key: customAttributeName,
                    },
                    view: {
                        key: `data-ezattribute-${customAttributeName}`,
                    },
                });
            });
        });
    }

    extendSchema(schema, element, definition) {
        if (schema.getDefinition(element)) {
            schema.extend(element, definition);
        } else {
            console.warn(`Schema does not have '${element}' element`);
        }
    }

    init() {
        const { model } = this.editor;
        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const elementsWithCustomAttributes = Object.keys(customAttributesConfig);
        const elementsWithCustomClasses = Object.keys(customClassesConfig);

        elementsWithCustomAttributes.forEach((element) => {
            if (element === 'link') {
                return;
            }

            const customAttributes = Object.keys(customAttributesConfig[element]);

            this.extendSchema(model.schema, element, { allowAttributes: customAttributes });
        });

        elementsWithCustomClasses.forEach((element) => {
            if (element === 'link') {
                return;
            }

            this.extendSchema(model.schema, element, { allowAttributes: 'custom-classes' });
        });

        this.defineConverters();

        this.editor.commands.add('insertIbexaCustomAttributes', new IbexaCustomAttributesCommand(this.editor));
    }
}

export default IbexaCustomAttributesEditing;
