import { Plugin } from 'ckeditor5';

import IbexaFormattedEditing from './formatted-editing';
import IbexaFormattedUI from './formatted-ui';

class IbexaFormatted extends Plugin {
    static get requires() {
        return [IbexaFormattedEditing, IbexaFormattedUI];
    }
}

export default IbexaFormatted;
