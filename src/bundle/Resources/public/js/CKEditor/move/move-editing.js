import { Plugin } from 'ckeditor5';

import IbexaMoveCommand from './move-command';

class IbexaMoveEditing extends Plugin {
    static get requires() {
        return [];
    }

    init() {
        this.editor.commands.add('insertIbexaMove', new IbexaMoveCommand(this.editor));
    }
}

export default IbexaMoveEditing;
