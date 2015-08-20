<?php

class ProfiledMemberForm extends CrackerjackForm {
    const RegisterAction = 'register';
    const UpdateAction = 'update';

    private static $form_fields = [
/*  example resultant arrays, should be set in config.
        self::RegisterAction => [
            'FirstName' => [true, 'TextField'],
            'Surname' => [true, 'TextField'],
            'Email' => [true, 'EmailField'],
        ],
        self::UpdateAction => [
            'FirstName' => [true, 'TextField'],
            'Surname' => [true, 'TextField'],
            'Email' => [true, 'EmailField'],
        ]
*/
    ];
    private static $form_actions = [
        'register' => [
            ['register', 'Register']
        ],
        'update' => [
            ['cancel', 'Cancel'],
            ['update', 'Update']
        ]
    ];

    public function __construct($controller, $name, $fields, $actions, $validator = null) {
        parent::__construct($controller, $name, $fields, $actions, $validator);
        Requirements::css('profiled/css/profiled.css');
    }

    /**
     * Load member and associated address record data into the form fields.
     *
     * @param int  $fromMember
     * @param int  $mergeStrategy
     * @param null $fieldList
     * @return $this|Form
     * @internal param array|DataObject $action
     */
    public function loadDataFrom($fromMember, $mergeStrategy = 0, $fieldList = NULL) {
        if ($fromMember instanceof Member) {
            $shippingAddress = $fromMember->ShippingAddress();
            if (!$shippingAddress) {
                $shippingAddress = new Address_Shipping([
                    'FirstName' => $fromMember->FirstName,
                    'Surname' => $fromMember->Surname
                ]);
                $shippingAddress->Default = true;
                $shippingAddress->write();
                $fromMember->ShippingAddresses()->add($shippingAddress);
            }

            $billingAddress = $fromMember->BillingAddress();
            if (!$billingAddress) {
                $billingAddress = new Address_Billing(array(
                    'FirstName' => $fromMember->FirstName,
                    'Surname' => $fromMember->Surname
                ));
                $billingAddress->Default = true;
                $billingAddress->write();
                $fromMember->BillingAddresses()->add($billingAddress);
            }

            $formData = $fromMember->toMap();

            foreach ($shippingAddress->toMap() as $fieldName => $value) {
                $formData["ShippingAddress.$fieldName"] = $shippingAddress->{$fieldName};
            }
            foreach ($billingAddress->toMap() as $fieldName => $value) {
                $formData["BillingAddress.$fieldName"] = $billingAddress->{$fieldName};
            }
            $formData = self::filter_data('update', $formData);

            parent::loadDataFrom($formData, $mergeStrategy);
        }
        return $this;
    }

    public static function update_models($action, array $fromData, Customer $toMember) {
        $updateData = [];
        foreach ($fromData as $fieldName => $value) {
            // TODO what if we have a field with an underscore?
            $updateData[str_replace('_', '.', $fieldName)] = $value;
        }
        $updateData = self::filter_data($action, $updateData);

        $toMember->update($updateData);
        $toMember->write();
        return $fromData;
    }
}