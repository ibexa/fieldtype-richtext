import Command from '@ckeditor/ckeditor5-core/src/command';

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
        const modelElement = this.editor.model.document.selection.getSelectedElement();
        const isValidElement = modelElement && this.editor.model.schema.checkAttribute(modelElement, 'ibexaLinkHref');
        const validRanges = this.editor.model.schema.getValidRanges(this.editor.model.document.selection.getRanges(), 'ibexaLinkHref');

        this.isEnabled = isValidElement || !validRanges.next().done;
    }

    setAttributes(writer, linkData, element) {
        writer.setAttribute('ibexaLinkHref', linkData.href, element);
        writer.setAttribute('ibexaLinkTitle', linkData.title, element);
        writer.setAttribute('ibexaLinkTarget', linkData.target, element);

        if (!!linkData.ibexaLinkClasses) {
            writer.setAttribute('ibexaLinkClasses', linkData.ibexaLinkClasses, element);
        }

        if (linkData.ibexaLinkAttributes) {
            Object.entries(linkData.ibexaLinkAttributes).forEach(([name, value]) => {
                writer.setAttribute(name, value, element);
            });
        }
    }
}

export default IbexaLinkCommand;
