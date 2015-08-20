<?php

class ProfiledEmail extends Email {
    // set to who will receive registration, verification etc emails
    private static $admin_email_address = '';

    // set to who will send emails to admin if can't use the member's email address
    private static $admin_sender_email_address = '';

    // set to who will send emails to members
    private static $member_sender_email_address = '';

    public function configureForAction($action, $who, Member $member, array $templateData = []) {
        $action = ucfirst($action);
        $who = ucfirst($who);

        $this->setSubject(CrackerjackModule::get_site_localised_config_setting(
            __CLASS__,
            $who . $action . 'Subject',
            $who . $action,
            $member->toMap()
        ));
        if ($who == 'Admin') {
            $to = $this->config()->get('admin_email_address') ?: Security::findAnAdministrator()->Email;
            $from = $this->config()->get('admin_sender_email_address') ?: $member->Email;
        } else {
            $to = $member->Email;
            $from = $this->config()->get('member_sender_email_address') ?: Security::findAnAdministrator()->Email;
        }
        $this->setTo($to);
        $this->setFrom($from);

        $this->setTemplate("Profiled{$who}_$action");
        $this->populateTemplate($templateData);
    }
}