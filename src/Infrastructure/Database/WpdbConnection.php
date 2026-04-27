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

    /**
     * Execute an unbuffered (streaming) query.
     *
     * Unlike get_results(), which loads all matched rows into memory at once,
     * this sends the query and returns a traversable result. Rows are fetched one at
     * a time via fetch_assoc(), so we only need memory proportional to a single row
     * rather than the entire result set.
     *
     * The caller is responsible for freeing the returned result before running any other
     * query on this connection.
     *
     * This method mirrors wpdb::query() by incrementing the query counter, logging the query, and flushing the state
     * after the previous query. Note that this class is a separate wpdb instance, so the counter and query log
     * do not affect the global $wpdb.
     *
     * @param string $sql The SQL query to execute.
     *
     * @return \mysqli_result A streaming result that must be freed by the caller.
     *
     * @throws \RuntimeException If the connection is not mysqli or the query fails.
     */
    public function unbuffered_query(string $sql): \mysqli_result
    {
        if (!$this->ready) {
            throw new \RuntimeException('Database connection is not ready.');
        }

        if (!$this->dbh instanceof \mysqli) {
            throw new \RuntimeException('Streaming queries require a mysqli connection.');
        }

        $this->flush();
        $this->last_query = $sql;
        $this->num_queries++;

        $save_queries = defined('\SAVEQUERIES') && \SAVEQUERIES;

        if ($save_queries) {
            $this->timer_start();
        }

        $this->dbh->real_query($sql);

        if ($save_queries) {
            $this->log_query(
                $sql,
                $this->timer_stop(),
                $this->get_caller(),
                $this->time_start,
                ['backwpup_streaming' => true]
            );
        }

        if ($this->dbh->errno) {
            throw new \RuntimeException(esc_html($this->dbh->error));
        }

        $result = $this->dbh->use_result();

        if (!$result instanceof \mysqli_result) {
            throw new \RuntimeException('Failed to obtain streaming result from database.');
        }

        return $result;
    }
}
