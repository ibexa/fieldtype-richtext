import { Plugin } from 'ckeditor5';

import IbexaMoveUpUI from './move-up-ui';
import IbexaMoveDownUI from './move-down-ui';
import IbexaMoveEditing from './move-editing';

class IbexaMove extends Plugin {
    static get requires() {
        return [IbexaMoveUpUI, IbexaMoveDownUI, IbexaMoveEditing];
    }
}

export default IbexaMove;
