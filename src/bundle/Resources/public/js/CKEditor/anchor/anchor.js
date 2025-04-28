import { Plugin } from 'ckeditor5';

import IbexaAnchorUI from './anchor-ui';
import IbexaAnchorEditing from './anchor-editing';

class IbexaAnchor extends Plugin {
    static get requires() {
        return [IbexaAnchorUI, IbexaAnchorEditing];
    }
}

export default IbexaAnchor;
