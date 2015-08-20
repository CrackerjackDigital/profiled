<?php

/**
 * Class to build required models etc on /dev/build.
 */
class ProfiledBuild extends DataObject {

    private static $auto_build_models = true;

    /**
     * If no ProfiledPage exists and no Page exists called 'Profile' then creates one
     * titled 'Profile' and writes it to stage.
     *
     */
    public function requireDefaultRecords() {
        if ($this->config()->get('auto_build_models')) {
            if (!ProfiledPage::get()->count()) {
                if (!SiteTree::get_by_link('/profile')) {
                    /** @var ProfiledPage $page */
                    $page = ProfiledPage::create(array(
                        'Title' => CrackerjackModule::get_localised_config_string('ProfiledBuild', 'ProfiledPageTitle', 'Title')
                    ));
                    $page->writeToStage('Stage');
                    DB::alteration_message("Added ProfiledPage 'Page' at '/profile'", 'changed');
                }
            }
        }
    }
}