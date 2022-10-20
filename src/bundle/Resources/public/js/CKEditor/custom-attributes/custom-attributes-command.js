import Command from '@ckeditor/ckeditor5-core/src/command';

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
            if (elementName === modelElement.name || classes[modelElement.name]) {
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
        const { attributes, classes } = window.ibexa.richText.alloyEditor;
        const parentElementAttributesConfig = attributes[parentElement.name];
        const parentElementClassesConfig = classes[parentElement.name];
        const isEnabled = parentElementAttributesConfig || parentElementClassesConfig;

        this.isEnabled = !!isEnabled;

        this.cleanAttributes(parentElement, attributes);
        this.cleanClasses(parentElement, classes);
    }
}

export default IbexaCustomAttributesCommand;
