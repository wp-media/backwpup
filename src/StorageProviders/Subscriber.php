<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {

    /**
     * @return mixed
     */
    public static function get_subscribed_events() {
        return [
            'backwpup_update_backup_history' => [ 'update_backup_history', 10, 2 ]
        ];
    }

    /**
     * Updates backup history after completing history
     * @param string $key The backup key.
     * @param array $backups The backups data.
     *
     * @return void
    */
    public function update_backup_history( string $key, array $backups ) : void  {
        set_site_transient( $key, $backups, YEAR_IN_SECONDS );
    }
}