import { ButtonView } from 'ckeditor5';

import IbexaIconView from '../icon-view/icon-view';

export default class IbexaButtonView extends ButtonView {
    constructor(locale) {
        super(locale);

        this.iconView = new IbexaIconView();

        this.iconView.bind('content').to(this, 'icon');
    }
}
