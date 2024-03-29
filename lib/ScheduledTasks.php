<?php

namespace SwIgPlugin;

class ScheduledTasks {

    public function __construct() {
        add_action( 'ig_import_newest_posts_job', \Closure::fromCallable( [ $this, 'ig_import_newest_posts' ] ) );
    }

    public function init_tasks() {
        $helpers  = new SWIGHelpers();
        $settings = $helpers->getPluginSettings();

        if ( $settings['autosync_enabled'] && ! wp_next_scheduled( 'ig_import_newest_posts_job' ) ) {
            return wp_schedule_event( strtotime( '09:00:00' ), 'daily', 'ig_import_newest_posts_job' );
        }

        return true;
    }

    protected function ig_import_newest_posts() {
        $importer = new PostsImporter();
        $importer->fetch_from_all_accounts(true);
    }

}