<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Database;

class WpdbConnection extends \wpdb
{
    /**
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbname
     * @param string $dbhost
     */
    public function __construct($dbuser, $dbpassword, $dbname, $dbhost)
    {
        parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);
        $this->suppress_errors(true);
        $this->hide_errors();
    }

    public function select($db, $dbh = null)
    {
        if ($db === null || $db === '') {
            return true;
        }

        return parent::select($db, $dbh);
    }

    /**
     * Avoid wp_die() so callers can handle errors.
     *
     * @param string $message
     * @param string $error_code
     *
     * @return false
     */
    public function bail($message, $error_code = '500')
    {
        $this->last_error = is_string($message) ? $message : '';

        return false;
    }
}
