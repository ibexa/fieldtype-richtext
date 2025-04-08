import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaBlockAlignmentCommand extends Command {
    refresh() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        this.value = modelElement?.getAttribute('data-ezalign') || '';
        this.isEnabled = true;
    }

    execute({ alignment }) {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        this.editor.model.change((writer) => {
            if (modelElement.getAttribute('data-ezalign') === alignment) {
                writer.removeAttribute('data-ezalign', modelElement);
            } else {
                writer.setAttribute('data-ezalign', alignment, modelElement);
            }
        });
    }
}

export default IbexaBlockAlignmentCommand;
