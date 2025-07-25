import { SwitchButtonView } from 'ckeditor5';

export function createLabeledSwitchButton(labeledFieldView, viewUid, statusUid) {
    const switchButtonView = new SwitchButtonView(labeledFieldView.locale);

    switchButtonView.set({
        id: viewUid,
        ariaDescribedById: statusUid,
    });

    switchButtonView.bind('isReadOnly').to(labeledFieldView, 'isEnabled', (value) => !value);
    switchButtonView.bind('hasError').to(labeledFieldView, 'errorText', (value) => !!value);

    labeledFieldView.bind('isEmpty', 'isFocused', 'placeholder').to(switchButtonView);

    return switchButtonView;
}
