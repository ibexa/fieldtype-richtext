declare global {
    interface IbexaRichText {
        CKEditor: IbexaRichTextCKEditor;
        customTags: Record<string, IbexaRichTextCustomTag>;
        customStyles: Record<string, IbexaRichTextCustomStyle>;
    }

    interface IbexaRichTextCKEditor {
        toolbar: string[];
        extraPlugins?: unknown[];
        extraConfig?: Record<string, unknown>;
        customTags?: IbexaRichTextCKEditorCustomTagMethods;
    }

    interface IbexaRichTextCKEditorCustomTagMethods {
        attributeRenderMethods?: Record<string, IbexaRichTextAttributeRenderMethod>;
        setValueMethods?: Record<string, IbexaRichTextSetValueMethod>;
        getValueMethods?: Record<string, IbexaRichTextGetValueMethod>;
        getValueLabelMethods?: Record<string, IbexaRichTextGetValueLabelMethod>;
    }

    type IbexaRichTextAttributeRenderMethod = (config: unknown, locale: string) => unknown;

    type IbexaRichTextSetValueMethod = (attributeView: unknown, value: unknown) => void;

    type IbexaRichTextGetValueMethod = (attributeView: unknown) => unknown;

    type IbexaRichTextGetValueLabelMethod = (value: unknown, config?: unknown) => string;

    interface IbexaRichTextCustomTag {
        isInline: boolean;
        label: string;
        attributes: Record<string, unknown>;
    }

    interface IbexaRichTextCustomStyle {
        inline: boolean;
        label: string;
    }
}

export {};
