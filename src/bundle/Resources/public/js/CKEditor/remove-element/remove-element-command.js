import { Command } from 'ckeditor5';

class IbexaRemoveElementCommand extends Command {
    execute() {
        this.editor.model.change((writer) => {
            const selectedElement = this.editor.model.document.selection.getSelectedElement();

            writer.remove(selectedElement);
        });
    }
}

export default IbexaRemoveElementCommand;
