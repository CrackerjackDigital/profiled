<?php

class ProfiledMemberExtension extends CrackerJackDataExtension {
    const VerificationFieldName = 'ProfiledVerificationCode';
    const VerificationDateName = 'ProfiledVerificationDate';

    private static $db = [
        self::VerificationFieldName => 'Text',
        self::VerificationDateName => 'SS_DateTime'
    ];

    private static $enabled = false;

    /**
     * Returns a form with fields from config.profiled_show_fields loaded from member.currentUser(). To work
     * properly expects the current controller to be extended with ProfiledControllerExtension.
     *
     * @return ProfiledMemberForm
     */
    public function ProfiledMemberForm($action) {

        $form = ProfiledMemberForm::create_for_action($action, Controller::curr(), null, null, null);

        $member = Injector::inst()->get('ProfiledMemberClass');

        $form->loadDataFrom($member::currentUser());

        return $form;
    }

    public static function enabled() {
        // dont do this if e.g. we are in the CMS or otherwise not on site tree
        return Controller::curr() instanceof Page_Controller && parent::enabled();
    }

    public function augmentSQL(SQLQuery &$query) {
        if (static::enabled()) {
            $query->addWhere(self::VerificationDateName . ' is not null');
        }
        parent::augmentSQL($query);
    }
}