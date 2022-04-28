import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaBlockAlignmentUI from './block-alignment-ui';
import IbexaBlockAlignmentEditing from './block-alignment-editing';

class IbexaBlockAlignment extends Plugin {
    static get requires() {
        return [IbexaBlockAlignmentUI, IbexaBlockAlignmentEditing];
    }
}

export default IbexaBlockAlignment;
