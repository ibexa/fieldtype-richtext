import { Command } from 'ckeditor5';

class IbexaLinkCommand extends Command {
    execute(linkData) {
        this.editor.model.change((writer) => {
            const modelElement = this.editor.model.document.selection.getSelectedElement();
            const ranges = this.editor.model.schema.getValidRanges(this.editor.model.document.selection.getRanges(), 'ibexaLinkHref');

            if (modelElement) {
                this.setAttributes(writer, linkData, modelElement);
            } else {
                for (const range of ranges) {
                    this.setAttributes(writer, linkData, range);
                }
            }
        });
    }

    refresh() {
        const {
            model: {
                schema,
                document: { selection },
            },
        } = this.editor;
        const modelElement = selection.getSelectedElement();
        const isValidElement = modelElement && schema.checkAttribute(modelElement, 'ibexaLinkHref');
        const validRanges = schema.getValidRanges(selection.getRanges(), 'ibexaLinkHref');
        const isInsideLink = selection.hasAttribute('ibexaLinkHref');

        this.isEnabled = isValidElement || isInsideLink || !validRanges.next().done;
    }

    setAttributes(writer, linkData, element) {
        writer.setAttribute('ibexaLinkHref', linkData.href, element);
        writer.setAttribute('ibexaLinkTitle', linkData.title, element);
        writer.setAttribute('ibexaLinkTarget', linkData.target, element);
        writer.setAttribute('ibexaLinkClasses', linkData.ibexaLinkClasses, element);

        if (linkData.ibexaLinkAttributes) {
            Object.entries(linkData.ibexaLinkAttributes).forEach(([name, value]) => {
                writer.setAttribute(name, value, element);
            });
        }
    }
}

export default IbexaLinkCommand;
