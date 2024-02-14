import Command from '@ckeditor/ckeditor5-core/src/command';

import { getCustomAttributesConfig, getCustomClassesConfig } from './helpers/config-helper';

class IbexaCustomAttributesCommand extends Command {
    cleanAttributes(modelElement, attributes) {
        Object.entries(attributes).forEach(([elementName, config]) => {
            if (elementName === modelElement.name) {
                return;
            }

            this.editor.model.change((writer) => {
                Object.keys(config).forEach((name) => {
                    if (attributes[modelElement.name]?.[name]) {
                        return;
                    }

                    writer.removeAttribute(name, modelElement);
                });
            });
        });
    }

    cleanClasses(modelElement, classes) {
        Object.keys(classes).forEach((elementName) => {
            const selectedCustomClasses = modelElement.getAttribute('custom-classes') ?? '';
            const elementCustomClassesConfig = classes[modelElement.name];
            const hasOwnCustomClasses =
                elementCustomClassesConfig &&
                selectedCustomClasses
                    .split(' ')
                    .every((selectedCustomClass) => elementCustomClassesConfig.choices.includes(selectedCustomClass));

            if (elementName === modelElement.name || hasOwnCustomClasses) {
                return;
            }

            this.editor.model.change((writer) => {
                writer.removeAttribute('custom-classes', modelElement);
            });
        });
    }

    refresh() {
        const { selection } = this.editor.model.document;
        const parentElement = selection.getSelectedElement() ?? selection.getFirstPosition().parent;
        let parentElementName = parentElement.name;

        if (this.editor.isListSelected) {
            const mapping = {
                bulleted: 'ul',
                numbered: 'ol',
            };
            const listType = parentElement.getAttribute('listType');

            if (mapping[listType]) {
                parentElementName = mapping[listType];
            }
        }

        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const parentElementAttributesConfig = customAttributesConfig[parentElementName];
        const parentElementClassesConfig = customClassesConfig[parentElementName];
        const isEnabled = parentElementAttributesConfig || parentElementClassesConfig;

        this.isEnabled = !!isEnabled;

        this.cleanAttributes(parentElement, customAttributesConfig);
        this.cleanClasses(parentElement, customClassesConfig);
    }
}

export default IbexaCustomAttributesCommand;
