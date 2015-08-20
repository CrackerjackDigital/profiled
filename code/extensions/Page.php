<?php

class ProfiledPageExtension extends SiteTreeExtension {
    const UpdateContentFieldName = 'ProfiledUpdateContent';
    const RegistrationContentFieldName = 'ProfiledRegisterContent';
    const ThanksContentFieldName = 'ProfiledThanksContent';

    private static $db = array(
        self::UpdateContentFieldName => 'HTMLText',
        self::RegistrationContentFieldName => 'HTMLText',
        self::ThanksContentFieldName => 'HTMLText'
    );

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldsToTab(
            'Root.Main',
            [
                new HtmlEditorField(self::UpdateContentFieldName, _t(__CLASS__ . '.' . self::UpdateContentFieldName, 'Content to show on profile update page')),
                new HtmlEditorField(self::RegistrationContentFieldName, _t(__CLASS__ . '.' . self::RegistrationContentFieldName, 'Content to show on registration page')),
                new HtmlEditorField(self::ThanksContentFieldName, _t(__CLASS__ . '.' . self::ThanksContentFieldName, 'Content to show on thank you page'))
            ]
        );
    }

}