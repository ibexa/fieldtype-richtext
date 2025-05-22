import { Plugin } from 'ckeditor5';

import IbexaRemoveElementUI from './remove-element-ui';
import IbexaRemoveElementEditing from './remove-element-editing';

class IbexaRemoveElement extends Plugin {
    static get requires() {
        return [IbexaRemoveElementUI, IbexaRemoveElementEditing];
    }
}

export default IbexaRemoveElement;
