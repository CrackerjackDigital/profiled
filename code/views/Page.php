<?php

class ProfiledPage extends Page {
    public function ProfiledMemberForm($for) {
        return ProfiledMemberForm::create_for_action($for, Controller::curr(), null, null, null);
    }
}
