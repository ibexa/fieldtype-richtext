import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaLinkCommand from './link-command';
import { getCustomAttributesConfig, getCustomClassesConfig } from '../custom-attributes/helpers/config-helper';

class IbexaCustomTagEditing extends Plugin {
    static get requires() {
        return [];
    }

    defineConverters(customAttributesLinkConfig, customClassesLinkConfig) {
        const { conversion } = this.editor;

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkHref',
            view: (href, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { href }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkHref',
            view: (href, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { href }),
        });

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkTitle',
            view: (title, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { title }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkTitle',
            view: (title, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { title }),
        });

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkTarget',
            view: (target, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { target }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkTarget',
            view: (target, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { target }),
        });

        if (customClassesLinkConfig) {
            conversion.for('editingDowncast').attributeToElement({
                model: 'ibexaLinkClasses',
                view: (classes, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { class: classes }),
            });

            conversion.for('dataDowncast').attributeToElement({
                model: 'ibexaLinkClasses',
                view: (classes, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { class: classes }),
            });
        }

        if (customAttributesLinkConfig) {
            Object.keys(customAttributesLinkConfig).forEach((customAttributeName) => {
                conversion.for('editingDowncast').attributeToElement({
                    model: `ibexaLink${customAttributeName}`,
                    view: (attr, { writer: downcastWriter }) =>
                        downcastWriter.createAttributeElement('a', { [`data-ezattribute-${customAttributeName}`]: attr }),
                });

                conversion.for('dataDowncast').attributeToElement({
                    model: `ibexaLink${customAttributeName}`,
                    view: (attr, { writer: downcastWriter }) =>
                        downcastWriter.createAttributeElement('a', { [`data-ezattribute-${customAttributeName}`]: attr }),
                });
            });
        }

        conversion.for('upcast').add((dispatcher) => {
            dispatcher.on('element:a', (evt, data, conversionApi) => {
                if (conversionApi.consumable.consume(data.viewItem, { attributes: ['href'] })) {
                    Object.assign(data, conversionApi.convertChildren(data.viewItem, data.modelCursor));

                    const ibexaLinkHref = data.viewItem.getAttribute('href');
                    const ibexaLinkTitle = data.viewItem.getAttribute('title');
                    const ibexaLinkTarget = data.viewItem.getAttribute('target');
                    const classes = data.viewItem.getAttribute('class');

                    conversionApi.writer.setAttributes(
                        {
                            ibexaLinkHref,
                            ibexaLinkTitle,
                            ibexaLinkTarget,
                        },
                        data.modelRange,
                    );

                    if (classes && customClassesLinkConfig) {
                        conversionApi.writer.setAttribute('ibexaLinkClasses', classes, data.modelRange);
                    }

                    if (customAttributesLinkConfig) {
                        Object.keys(customAttributesLinkConfig).forEach((customAttributeName) => {
                            const customAttributeValue = data.viewItem.getAttribute(`data-ezattribute-${customAttributeName}`);

                            if (customAttributeValue) {
                                conversionApi.writer.setAttribute(`ibexaLink${customAttributeName}`, customAttributeValue, data.modelRange);
                            }
                        });
                    }
                }
            });
        });
    }

    init() {
        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const customAttributesLinkConfig = customAttributesConfig.link;
        const customClassesLinkConfig = customClassesConfig.link;

        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkHref' });
        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkTitle' });
        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkTarget' });

        if (customAttributesLinkConfig) {
            const attributes = Object.keys(customAttributesLinkConfig);

            attributes.forEach((attribute) => {
                this.editor.model.schema.extend('$text', { allowAttributes: `ibexaLink${attribute}` });
            });
        }

        if (customClassesLinkConfig) {
            this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkClasses' });
        }

        this.defineConverters(customAttributesLinkConfig, customClassesLinkConfig);

        this.editor.commands.add('insertIbexaLink', new IbexaLinkCommand(this.editor));
    }
}

export default IbexaCustomTagEditing;
