<?php

class ProfiledControllerExtension extends CrackerjackControllerExtension {
    const AdminEmailPrefix = "ProfiledAdmin_";
    const MemberEmailPrefix = "ProfiledMember_";
    const CustomerGroupCode = 'customers';

    const SessionEmailKey = 'Profiled.RegistrationEmail';

    // TODO integrate this into url_handlers maye via get_extra_config or some such
    const ActionPrefix = 'profile';

    private static $url_handlers = [
        'register' => 'register',
        'GET verify/$Token!' => 'verify',
        'POST update' => 'update',
        'GET thanks' => 'thanks'
    ];
    private static $allowed_actions = [
        'register' => true,
        'verify' => true,
        'update' => '->canUpdate',
        'thanks' => true
    ];

    // TODO IMPORTANT! Is this enough, does the form always only show current member?
    public function canUpdate() {
        return true;
    }

    public function TabStrip($fullLinks = false) {
        return ProfiledMemberForm::tab_strip('update', $fullLinks);
    }

    public function register(SS_HTTPRequest $request) {
        if ($request->isPOST()) {
            try {
                if (Customer::get()->filter('Email', $request->postVar('Email'))->count()) {
                    throw new ValidationException("Sorry a member with that email address already exists");
                }
                $password = Customer::create_new_password();


                /** @var Customer $member */
                $member = Injector::inst()->create('ProfiledMemberClass');
                $member->changePassword($password);

                // update member with cleansed posted variables
                $updateData = array_merge(
                    ProfiledMemberForm::update_models(
                        'register',
                        array_merge(
                            $request->postVars(),
                            [
                                'Password' => $password
                            ]
                        ),
                        $member
                    )
                );

                /** @var CryptofierImplementation $crypto */
                $crypto = Injector::inst()->get('CryptofierService');

                $token = $crypto->friendly($crypto->encrypt($member->Email));

                $member->{ProfiledMemberExtension::VerificationFieldName} = $token;

                $member->write();

                $member->addToGroupByCode(self::CustomerGroupCode);


                // add verification link and HasRegisteredFlag
                $updateData = array_merge(
                    [
                        'Password' => $password,
                        'VerificationLink' => Controller::join_links(Director::absoluteBaseURL(), $this()->ActionLink("verify/$token"))
                    ],
                    $updateData
                );

                $this->sendEmail('Register', $member, $updateData);

                Session::set(self::SessionEmailKey, $member->Email);

                $url = CrackerjackModule::get_config_setting(__CLASS__, 'post_register_url')
                    ?: $this()->ActionLink('thanks');

                return $this()->redirect($url);

            } catch (ValidationException $e) {

                ProfiledMemberForm::set_form_message($e->getMessage(), CrackerjackForm::Bad);
                return $this()->redirectBack();

            }
        } else {
            return array();
        }
    }

    public function verify(SS_HTTPRequest $request) {
        $token = $request->param('Token');

        /** @var CryptofierImplementation $crypto */
        $crypto = Injector::inst()->get('CryptofierService');

        if ($email = $crypto->decrypt_friendly($token)) {

            /** @var Customer $member */
            if ($member = Customer::get()->filter('Email', $email)->first()) {

                $member->{ProfiledMemberExtension::VerificationDateName} = date('Y-m-d H:i:s');

                $member->write();

                ProfiledMemberForm::set_form_message(ProfiledMemberForm::get_form_message('VerificationOK'), 'Your account has been activated', CrackerjackForm::Good);

                return $this()->redirect(CrackerjackModule::get_config_setting(null, 'post_verify_url'));
            }
        }
        ProfiledMemberForm::set_form_message('VerificationFail', 'bad');
        return $this()->redirectBack();
    }

    public function update(SS_HTTPRequest $request) {
        $member = Customer::currentUser();
        if (!$member->canEdit()) {
            return $this()->httpError(401);
        }

        $updateData = $request->postVars();

        $updateData = ProfiledMemberForm::update_models('update', $updateData, $member);

        $member->write();

        /** @noinspection PhpParamsInspection */
        ProfiledMemberForm::set_form_message("ProfileUpdated", CrackerjackForm::Good);

        $this->sendEmail('Update', $member, $updateData);

        return $this()->redirectBack();
    }

    public function thanks(SS_HTTPRequest $request) {
        $data = array(
            'Email' => Session::get(self::SessionEmailKey),
            'ShowThanks' => true
        );

        Session::clear(self::SessionEmailKey);
        ProfiledMemberForm::clear_form_message();

        return $data;
    }

    protected function sendEmail($what, Member $member, array $data = []) {
        $profiledConfig = ProfiledConfig::current_profiled_config();

        $templateData = array_merge(
            [
                'Member' => $member
            ],
            $data
        );

        $memberEmailTemplate = self::MemberEmailPrefix . strtolower($what);

        if (SSViewer::hasTemplate($memberEmailTemplate)) {
            // send email to member from ProfiledConfig.SendEmailFrom to Member.Email
            $emailMember = new Email(
                $profiledConfig->getProfiledSender('Member'),
                $member->Email,
                $profiledConfig->getProfiledEmailSubject('Member', $what)
            );
            $emailMember->setTemplate($memberEmailTemplate);
            $emailMember->populateTemplate($templateData);
            $emailMember->send();
        }

        $adminEmailTemplate = self::AdminEmailPrefix . strtolower($what);

        if (SSViewer::hasTemplate([$adminEmailTemplate])) {
            // send admin email from either Member.Email or ProfiledConfig.SendEmailFrom to Profiled.AdminEmail
            $emailAdmin = new Email(
                $profiledConfig->getProfiledSender('Admin', $member->Email),
                $profiledConfig->getAdminEmail(),
                $profiledConfig->getProfiledEmailSubject('Admin', $what)
            );
            $emailAdmin->setTemplate($adminEmailTemplate);

            $emailAdmin->populateTemplate($templateData);

            // save body to attach to admin email
            if (isset($emailMember)) {
                $emailAdmin->attachFileFromString($emailMember->Body(), $member->Email . "_registration.txt");
            }
            $emailAdmin->send();
        }
    }

}