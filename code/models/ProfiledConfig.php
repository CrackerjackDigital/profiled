<?php

class ProfiledConfig extends DataObject {
    const LabelSuffix = 'Label';
    const EmailPrefix = 'Email';
    const EmailSubjectSuffix = 'Subject';

    private static $tab_name = 'Root.Profiles';

    // send admin emails from the member, may not be allowed by some mail servers so
    // disable to get 'SendEmailFrom' instead
    private static $send_to_admin_from_member = true;

    // fallback setting if this.SendEmailFrom not set
    private static $send_email_from = '';

    // fallback setting if this.SendAdminEmailsTo not set
    private static $send_admin_email_to = '';

    private static $db = array(
        'SendEmailFrom' => 'Varchar(255)',
        'SendAdminEmailTo' => 'Varchar(255)',
        'SendToAdminFromMember' => 'Boolean',
        'EmailMemberRegisterSubject' => 'Varchar(255)',
        'EmailMemberUpdateSubject' => 'Varchar(255)',
        'EmailMemberVerifySubject' => 'Varchar(255)',
        'EmailAdminRegisterSubject' => 'Varchar(255)',
        'EmailAdminUpdateSubject' => 'Varchar(255)',
        'EmailAdminVerifySubject' => 'Varchar(255)'
    );

    /**
     * @return ProfiledConfig
     */
    public static function current_profiled_config(&$created = false) {
        if (!$config = ProfiledConfig::get()->first()) {
            $config = self::create_default_record();
            $created = true;
        }
        return $config;
    }

    private static function create_default_record() {
        $config = ProfiledConfig::create(array(
            'SendEmailFrom' => static::config()->get('send_email_from'),
            'SendAdminEmailTo' => static::config()->get('send_admin_email_to'),
            'SendToAdminFromMember' => static::config()->get('send_to_admin_from_member')
        ));
        $config->write();
        return $config;
    }
    /**
     * @param FieldList $fields
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $dbFields = $this->custom_database_fields(__CLASS__);

        foreach ($dbFields as $fieldName => $fieldSpec) {

            $fields->addFieldToTab(
                CrackerjackModule::get_config_setting(__CLASS__, 'tab_name'),
                ProfiledMemberForm::make_field(
                    $fieldName,
                    [
                        true,
                        'TextField',
                        self::get_field_label($fieldName)
                    ]
                )
            );
        }
        return $fields;
    }

    public function getCMSActions() {
        $actions = parent::getCMSActions();

        $actions->push(
            new FormAction('save', 'Save')
        );

        return $actions;
    }

    public function requireDefaultRecords() {
        $created = false;
        self::current_profiled_config($created);
        if ($created) {
            DB::alteration_message("Created default ProfiledConfig", "changed");
        } else {
            DB::alteration_message("ProfiledConfig record exists", "unchanged");
        }
    }

    /**
     * If any of the extended fields are empty then set them to the label for the field.
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();

        $dbFields = CrackerjackModule::get_config_setting(__CLASS__, 'db', null, Config::UNINHERITED);

        foreach ($dbFields as $fieldName => $fieldSpec) {
            if (!$this->$fieldName) {
                $this->$fieldName = self::get_field_label($fieldName);
            }
        }
    }

    public function getProfiledSender($who = null, $memberEmail = null) {
        $sender = $this->SendEmailFrom;

        if ($who == 'Admin') {
            if (static::config()->get('send_to_admin_from_member')) {
                $sender = $memberEmail;
            }
        }
        // try SendEmailFrom and if not set use config.send_emails_from then any admins email instead
        return $sender
            ?: ($this->SendEmailFrom
                ?: (static::config()->get('send_email_from')
                    ?: Security::findAnAdministrator()->Email));
    }

    public function getProfiledEmailSubject($who, $what) {
        $fieldName = self::EmailPrefix . ucfirst($who) . ucfirst($what) . self::EmailSubjectSuffix;

        return $this->$fieldName ?: "$who $what";
    }

    public function getAdminEmail() {
        // use this.SendAdminEmailsTo or config.send_admin_emails_to or any admins email
        return $this->SendAdminEmailTo
            ?: (static::config()->get('send_admin_email_to')
                ?: Security::findAnAdministrator()->Email);
    }


    protected static function get_field_label($fieldName, $default = null, array $data = []) {
        return _t(get_called_class() . '.' . $fieldName . self::LabelSuffix, $default ?: $fieldName, $data);
    }
}