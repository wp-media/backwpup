<?php

use Inpsyde\BackWPup\Infrastructure\Database\WpdbConnection;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class to create a MYSQL dump with WordPress DB APIs as sql file.
 */
class BackWPup_MySQLDump {

	// Compression flags.
	public const COMPRESS_NONE = '';
    public const COMPRESS_GZ = 'gz';

    /**
     * Table names of Tables in Database.
     */
    public $tables_to_dump = [];

    /**
     * View names of Views in Database.
     */
    public $views_to_dump = [];

	/**
	 * Holder for wpdb connection.
	 *
	 * @var WpdbConnection
	 */
	private $wpdb;

    /**
     * Whether we are connected to the database.
     *
     * @var bool
     */
    private $connected = false;

    /**
     * Holder for dump file handle.
     */
    private $handle;

    /**
     * Table names of Tables in Database.
     */
    private $table_types = [];

    /**
     * Table information of Tables in Database.
     */
    private $table_status = [];

    /**
     * Database name.
     */
    private $dbname = '';

    /**
     * Check params and makes confections
     * gets the table information too.
     *
     * @param  $args array with arguments
     *
     * @throws BackWPup_MySQLDump_Exception
     *
     * @global $wpdb wpdb
	 */
	public function __construct( $args = [] ) {
		// compression will be automatically set by dumpfile.
		unset( $args['compression'] );

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $args = $resolver->resolve($args);

        $this->connect($args);

		// Set charset.
		if ( ! empty( $args['dbcharset'] ) ) {
			$this->setCharset( $args['dbcharset'] );
		}

		// Open file if set.
		if ( $args['dumpfile'] ) {
			if ( self::COMPRESS_GZ === $args['compression'] ) {
				if ( ! function_exists( 'gzencode' ) ) {
					throw new BackWPup_MySQLDump_Exception( esc_html__( 'Functions for gz compression not available', 'backwpup' ) );
				}

				$this->handle = fopen( 'compress.zlib://' . $args['dumpfile'], 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			} else {
				$this->handle = fopen( $args['dumpfile'], 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			}
        } else {
            $this->handle = $args['dumpfilehandle'];
        }

		// Check file handle.
		if ( ! $this->handle ) {
			throw new BackWPup_MySQLDump_Exception( esc_html__( 'Cannot open SQL backup file', 'backwpup' ) );
		}

		// Get table info.
		$table_status_rows = $this->query( 'SHOW TABLE STATUS', ARRAY_A );
		foreach ( $table_status_rows as $tablestatus ) {
			$this->table_status[ $tablestatus['Name'] ] = $tablestatus;
		}

		// Get table names and types from database.
		$tables = $this->query( 'SHOW FULL TABLES', ARRAY_N );
		foreach ( $tables as $table ) {
			$this->table_types[ $table[0] ] = $table[1];
			$this->tables_to_dump[]         = $table[0];
			if ( 'VIEW' === $table[1] ) {
				$this->views_to_dump[]                   = $table[0];
				$this->table_status[ $table[0] ]['Rows'] = 0;
			}
		}
    }

    /**
	 * Configure options.
	 *
     * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	protected function configureOptions( OptionsResolver $resolver ) {
		$resolver->setDefaults(
			[
				'dbhost'         => DB_HOST,
				'dbport'         => null,
				'dbsocket'       => null,
				'dbname'         => DB_NAME,
				'dbuser'         => DB_USER,
				'dbpassword'     => DB_PASSWORD,
				'dbcharset'      => defined( 'DB_CHARSET' ) ? DB_CHARSET : 'utf8mb4',
				'dumpfilehandle' => fopen( 'php://output', 'wb' ),
				'dumpfile'       => null,
				'dbclientflags'  => defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0,
				'compression'    => function ( Options $options ) {
					if ( ! empty( $options['dumpfile'] )
						&& '.gz' === substr( strtolower( (string) $options['dumpfile'] ), -3 ) ) {
						return self::COMPRESS_GZ;
					}

					return self::COMPRESS_NONE;
				},
			]
			);

        $port = $socket = null;

		$resolver->setNormalizer(
			'dbhost',
			function ( Options $options, $value ) use ( &$port, &$socket ) {
				if ( false !== strpos( $value, ':' ) ) {
					[$value, $part] = array_map( 'trim', explode( ':', $value, 2 ) );
					if ( is_numeric( $part ) ) {
						$port = intval( $part );
					} elseif ( ! empty( $part ) ) {
						$socket = $part;
					}
				}

				return $value ?: 'localhost';
			}
			);

        $resolver->setDefault('dbport', function (Options $options) use (&$port) {
            return $port;
        });

        $resolver->setDefault('dbsocket', function (Options $options) use (&$socket) {
            return $socket;
        });

		$resolver->setAllowedValues(
			'dumpfilehandle',
			function ( $value ) {
				// Ensure handle is writable.
				$metadata = stream_get_meta_data( $value );

				return ! ( 'r' === $metadata['mode'][0] && false === strpos( $metadata['mode'], '+' ) );
			}
			);

        $resolver->setAllowedValues('compression', [self::COMPRESS_NONE, self::COMPRESS_GZ]);

		$resolver->setAllowedTypes( 'dbhost', 'string' );
		$resolver->setAllowedTypes( 'dbport', [ 'null', 'int' ] );
		$resolver->setAllowedTypes( 'dbsocket', [ 'null', 'string' ] );
		$resolver->setAllowedTypes( 'dbname', 'string' );
		$resolver->setAllowedTypes( 'dbuser', 'string' );
		$resolver->setAllowedTypes( 'dbpassword', 'string' );
		$resolver->setAllowedTypes( 'dbcharset', [ 'null', 'string' ] );
		$resolver->setAllowedTypes( 'dumpfilehandle', 'resource' );
		$resolver->setAllowedTypes( 'dumpfile', [ 'null', 'string' ] );
		$resolver->setAllowedTypes( 'dbclientflags', 'int' );
		$resolver->setAllowedTypes( 'compression', [ 'string' ] );
	}

    /**
     * Set the best available database charset.
	 *
	 * @param string $charset The charset to try setting.
	 *
     * @return string The set charset
	 */
	public function setCharset( $charset ) {
		if ( 'utf8' === $charset && true === $this->applyCharset( 'utf8mb4' ) ) {
			return 'utf8mb4';
		}
		if ( true === $this->applyCharset( $charset ) ) {
			return $charset;
		}
		if ( 'utf8mb4' === $charset && true === $this->applyCharset( 'utf8' ) ) {
			return 'utf8';
        }

        trigger_error(
			sprintf(
				/* translators: %s: database charset. */
				esc_html__( 'Cannot set DB charset to %s', 'backwpup' ),
				esc_html( $charset )
			),
            E_USER_WARNING
        );

        return false;
    }

    /**
     * Get the database connection.
	 *
	 * @return WpdbConnection
	 */
	protected function getConnection() {
		return $this->wpdb;
	}

	/**
	 * Apply charset on the current connection.
	 *
	 * @param string $charset The charset to set.
	 *
	 * @return bool True on success, false otherwise
	 */
	private function applyCharset( $charset ) {
		$query = $this->wpdb->prepare( 'SET NAMES %s', $charset );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is already built by the caller with validated identifiers.
		$this->wpdb->query( $query );
		++$GLOBALS[ \wpdb::class ]->num_queries;

		if ( '' !== $this->wpdb->last_error ) {
			return false;
		}

		$this->wpdb->charset = $charset;

		return true;
	}

    /**
     * Whether the database is connected.
     *
     * @return bool
	 */
	public function isConnected() {
		return $this->connected;
	}

    /**
     * Connect to the database.
     *
     * @param array $args Connection parameters
     */
    protected function connect(array $args)
    {
        if ($this->isConnected()) {
            return;
        }

		$dbhost = $args['dbhost'];
		if ( ! empty( $args['dbsocket'] ) ) {
			$dbhost .= ':' . $args['dbsocket'];
		} elseif ( ! empty( $args['dbport'] ) ) {
			$dbhost .= ':' . $args['dbport'];
		}

		$this->wpdb = new WpdbConnection(
			$args['dbuser'],
			$args['dbpassword'],
			$args['dbname'],
			$dbhost
		);

		if ( ! $this->wpdb->check_connection( false ) || ! $this->wpdb->ready ) {
			$error_message = $this->wpdb->last_error ?: __( 'Unknown database error', 'backwpup' );
			throw new BackWPup_MySQLDump_Exception(
				sprintf(
					/* translators: 1: error code, 2: error message. */
					esc_html__( 'Cannot connect to MySQL database (%1$d: %2$s)', 'backwpup' ),
					0,
					esc_html( $error_message )
				)
            );
        }

		// Set db name.
		$this->dbname = $args['dbname'];

		// We are now connected.
		$this->connected = true;
    }

    /**
     * Get the name of the database.
     *
     * @return string
     */
    protected function getDbName()
    {
        return $this->dbname;
    }

    /**
     * Report a query error.
	 *
	 * @param string $message The error message.
	 * @param string $query   The query that caused the error.
	 */
	protected function logQueryError( $message, $query ) {
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error, WordPress.XSS.EscapeOutput.OutputNotEscaped
        trigger_error(
			sprintf(
				/* translators: 1: database error message, 2: SQL query. */
				esc_html__( 'Database error: %1$s. Query: %2$s', 'backwpup' ),
				esc_html( $message ),
				esc_html( $query )
			),
            E_USER_WARNING
        );
        // phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error. WordPress.XSS.EscapeOutput.OutputNotEscaped
    }

    /**
     * Send a query to the database.
     *
     * @param string $query The query to execute
     *
	 * @return mixed The result of the query
	 *
	 * @throws BackWPup_MySQLDump_Exception When the query fails.
	 */
	protected function query( $sql, $output = ARRAY_A ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is already built by the caller with validated identifiers.
		$result = $this->wpdb->get_results( $sql, $output );
		++$GLOBALS[ \wpdb::class ]->num_queries;

		if ( '' !== $this->wpdb->last_error ) {
			throw new BackWPup_MySQLDump_Exception( esc_html( $this->wpdb->last_error ) );
		}

        return $result;
    }

    /**
     * Start the dump.
	 */
	public function execute() {
		// Increase time limit.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}
		// Write dump head.
		$this->dump_head();
		// Write tables.
		foreach ( $this->tables_to_dump as $table ) {
			$this->dump_table_head( $table );
			$start  = 0;
			$length = 1000;
			while ( true ) {
				$done_records = $this->dump_table( $table, $start, $length );
				if ( $done_records <= 0 || $done_records < $length ) {
					break;
				}
				$start += $done_records;
			}
			$this->dump_table_footer( $table );
		}
		// Write footer.
		$this->dump_footer();
    }

    /**
     * Write Dump Header.
	 *
	 * @param bool $wp_info Dump WordPress info in dump head.
	 *
	 * @throws BackWPup_MySQLDump_Exception When the header cannot be generated.
	 */
	public function dump_head( $wp_info = false ) {
		// Get sql timezone.
		$mysqltimezone = $this->wpdb->get_var( 'SELECT @@time_zone' );
		++$GLOBALS[ \wpdb::class ]->num_queries;
		if ( '' !== $this->wpdb->last_error ) {
			throw new BackWPup_MySQLDump_Exception( esc_html( $this->wpdb->last_error ) );
		}
		$mysqltimezone = (string) $mysqltimezone;

        //For SQL always use \n as MySQL wants this on all platforms.
        $dbdumpheader = "-- ---------------------------------------------------------\n";
        $dbdumpheader .= '-- Backup with BackWPup ver.: ' . BackWPup::get_plugin_data('Version') . "\n";
        $dbdumpheader .= "-- http://backwpup.com/\n";
        if ($wp_info) {
            $dbdumpheader .= '-- Blog Name: ' . get_bloginfo('name') . "\n";
            $dbdumpheader .= '-- Blog URL: ' . trailingslashit(get_bloginfo('url')) . "\n";
            $dbdumpheader .= '-- Blog ABSPATH: ' . trailingslashit(str_replace('\\', '/', (string) ABSPATH)) . "\n";
            $dbdumpheader .= '-- Blog Charset: ' . get_bloginfo('charset') . "\n";
            $dbdumpheader .= '-- Table Prefix: ' . $GLOBALS[\wpdb::class]->prefix . "\n";
        }
		$dbdumpheader .= '-- Database Name: ' . $this->dbname . "\n";
		$dbdumpheader .= '-- Backup on: ' . wp_date( 'Y-m-d H:i.s', time() ) . "\n";
		$dbdumpheader .= "-- ---------------------------------------------------------\n\n";
		// For better import with mysql client.
		$dbdumpheader .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
        $dbdumpheader .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
		$dbdumpheader .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
		$dbdumpheader .= '/*!40101 SET NAMES ' . ( $this->wpdb->charset ?: 'utf8mb4' ) . " */;\n";
		$dbdumpheader .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
        $dbdumpheader .= "/*!40103 SET TIME_ZONE='" . $mysqltimezone . "' */;\n";
        $dbdumpheader .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
        $dbdumpheader .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
        $dbdumpheader .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
        $dbdumpheader .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
        $this->write($dbdumpheader);
    }

    /**
     * Write Dump Footer with dump of functions and procedures.
	 */
	public function dump_footer() {
		// Dump views.
		foreach ( $this->views_to_dump as $view ) {
			$this->dump_view_table_head( $view );
		}

		// Dump procedures and functions.
		$this->write( "\n--\n-- Backup routines for database '" . $this->dbname . "'\n--\n" );

		// Dump functions.
		$this->dump_functions();

		// Dump procedures.
		$this->dump_procedures();

		// Dump triggers.
		$this->dump_triggers();

		// For better import with mysql client.
		$dbdumpfooter  = "\n/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
		$dbdumpfooter .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
        $dbdumpfooter .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
        $dbdumpfooter .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
        $dbdumpfooter .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
        $dbdumpfooter .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
        $dbdumpfooter .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
		$dbdumpfooter .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
		$dbdumpfooter .= "\n-- Backup completed on " . wp_date( 'Y-m-d H:i:s', time() ) . "\n";
		$this->write( $dbdumpfooter );
	}

    /**
     * Dump table structure.
     *
     * @param string $table name of Table to dump
     *
     * @throws BackWPup_MySQLDump_Exception
     *
     * @return int Size of table
	 */
	public function dump_table_head( $table ) {
		$this->assertTableName( $table );
		// Dump view.
		if ( 'VIEW' === $this->table_types[ $table ] ) {
			// Dump the view table structure.
			$fields = [];
			try {
				$this->query( 'SELECT * FROM `' . $table . '` LIMIT 1', ARRAY_A );
				$fields = $this->getLastQueryFields();
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				trigger_error(
					sprintf(
						/* translators: 1: database error message, 2: SQL query. */
						esc_html__( 'Database error %1$s for query %2$s', 'backwpup' ),
						esc_html( $e->getMessage() ),
						esc_html( 'SELECT * FROM `' . $table . '` LIMIT 1' )
					),
					E_USER_WARNING
				);
			}
			if ( ! empty( $fields ) ) {
				$tablecreate  = "\n--\n-- Temporary table structure for view `" . $table . "`\n--\n\n";
				$tablecreate .= 'DROP TABLE IF EXISTS `' . $table . "`;\n";
                $tablecreate .= '/*!50001 DROP VIEW IF EXISTS `' . $table . "`*/;\n";
				$tablecreate .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
				$tablecreate .= "/*!40101 SET character_set_client = '" . ( $this->wpdb->charset ?: 'utf8mb4' ) . "' */;\n";
				$tablecreate .= 'CREATE TABLE `' . $table . "` (\n";

                foreach ($fields as $field) {
                    $tablecreate .= '  `' . $field->orgname . "` tinyint NOT NULL,\n";
                }
                $tablecreate = substr($tablecreate, 0, -2) . "\n";
                $tablecreate .= ");\n";
                $tablecreate .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
                $this->write($tablecreate);
            }

            return 0;
        }

		// Dump normal table.
		$tablecreate  = "\n--\n-- Table structure for `" . $table . "`\n--\n\n";
		$tablecreate .= 'DROP TABLE IF EXISTS `' . $table . "`;\n";
		$tablecreate .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
		$tablecreate .= "/*!40101 SET character_set_client = '" . ( $this->wpdb->charset ?: 'utf8mb4' ) . "' */;\n";
		// Dump the table structure.
		$identifier = $this->escapeIdentifier( $table );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is already built by the caller with validated identifiers.
		$createtable = $this->wpdb->get_row( 'SHOW CREATE TABLE ' . $identifier, ARRAY_A );
		++$GLOBALS[ \wpdb::class ]->num_queries;
		if ( '' !== $this->wpdb->last_error ) {
			trigger_error(
				sprintf(
					/* translators: 1: database error message, 2: SQL query. */
					esc_html__( 'Database error %1$s for query %2$s', 'backwpup' ),
					esc_html( $this->wpdb->last_error ),
					esc_html( 'SHOW CREATE TABLE ' . $identifier )
				),
				E_USER_WARNING
			);
		} else {
			$createtable  = str_replace( '"', '`', $createtable );
			$tablecreate .= $createtable['Create Table'] . ";\n";
            $tablecreate .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
            $this->write($tablecreate);

			if ( 'MyISAM' !== $this->table_status[ $table ]['Engine'] ) {
				$this->table_status[ $table ]['Rows'] = '~' . $this->table_status[ $table ]['Rows'];
			}

			if ( 0 !== $this->table_status[ $table ]['Rows'] ) {
				// Dump table data.
				$this->write( "\n--\n-- Backup data for table `" . $table . "`\n--\n\nLOCK TABLES `" . $table . "` WRITE;\n/*!40000 ALTER TABLE `" . $table . "` DISABLE KEYS */;\n" );
			}

            return $this->table_status[$table]['Rows'];
        }

        return 0;
    }

    /**
     * Dump view structure.
     *
     * @param string $view name of Table to dump
     *
     * @throws BackWPup_MySQLDump_Exception
	 */
	public function dump_view_table_head( $view ): void {
		$this->assertTableName( $view );
		// Dump the view structure.
		$identifier = $this->escapeIdentifier( $view );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is already built by the caller with validated identifiers.
		$createview = $this->wpdb->get_row( 'SHOW CREATE VIEW ' . $identifier, ARRAY_A );
		++$GLOBALS[ \wpdb::class ]->num_queries;
		if ( '' !== $this->wpdb->last_error ) {
			trigger_error(
				sprintf(
					/* translators: 1: database error message, 2: SQL query. */
					esc_html__( 'Database error %1$s for query %2$s', 'backwpup' ),
					esc_html( $this->wpdb->last_error ),
					esc_html( 'SHOW CREATE VIEW ' . $identifier )
				),
				E_USER_WARNING
			);
		} else {
			$tablecreate  = "\n--\n-- View structure for `" . $view . "`\n--\n\n";
			$tablecreate .= 'DROP TABLE IF EXISTS `' . $view . "`;\n";
            $tablecreate .= 'DROP VIEW IF EXISTS `' . $view . "`;\n";
			$tablecreate .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
			$tablecreate .= "/*!40101 SET character_set_client = '" . ( $this->wpdb->charset ?: 'utf8mb4' ) . "' */;\n";
			$tablecreate .= $createview['Create View'] . ";\n";
            $tablecreate .= "/*!40101 SET character_set_client = @saved_cs_client */;\n";
            $this->write($tablecreate);
        }
    }

    /**
     * Dump table footer.
     *
     * @param string $table name of Table to dump
     *
     * @return int Size of table
	 */
	public function dump_table_footer( $table ): void {
		if ( 0 !== $this->table_status[ $table ]['Rows'] ) {
			$this->write( '/*!40000 ALTER TABLE `' . $table . "` ENABLE KEYS */;\nUNLOCK TABLES;\n" );
		}
    }

    /**
     * Dump table  Data.
     *
     * @param string $table  name of Table to dump
     * @param int    $start  Start of lengh paramter
     * @param int    $length how many
     *
     * @throws BackWPup_MySQLDump_Exception
     *
     * @return int done records in this backup
	 */
	public function dump_table( $table, $start = 0, $length = 0 ) {
		$this->assertTableName( $table );
		if ( ! is_numeric( $start ) || $start < 0 ) {
			throw new BackWPup_MySQLDump_Exception(
				sprintf(
					/* translators: %s: start offset. */
					esc_html__( 'Start for table backup is not correctly set: %1$s', 'backwpup' ),
					esc_html( $start )
				)
			);
		}

		if ( ! is_numeric( $length ) || $length < 0 ) {
			throw new BackWPup_MySQLDump_Exception(
				sprintf(
					/* translators: %s: requested length. */
					esc_html__( 'Length for table backup is not correctly set: %1$s', 'backwpup' ),
					esc_html( $length )
				)
			);
        }

        $done_records = 0;

		if ( 'VIEW' === $this->get_table_type_for( $table ) ) {
			return $done_records;
        }

		// Get data from table.
		try {
			$query_result = $this->do_table_query( $table, $start, $length );
		} catch ( BackWPup_MySQLDump_Exception $e ) {
			trigger_error(
				sprintf(
					/* translators: 1: database error message, 2: SQL query. */
					esc_html__( 'Database error %1$s for query %2$s', 'backwpup' ),
					esc_html( $e->getMessage() ),
					esc_html( 'SELECT * FROM `' . $table . '`' )
				),
				E_USER_WARNING
			);

            return 0;
        }

		$fieldsarray = [];
		$fieldinfo   = [];
		$fields      = $query_result['fields'];
		$i           = 0;

        foreach ($fields as $field) {
            $fieldsarray[$i] = $field->orgname;
            $fieldinfo[$fieldsarray[$i]] = $field;
            ++$i;
        }
        $dump = '';

		foreach ( $query_result['rows'] as $data ) {
			$values = [];

			foreach ( $data as $key => $value ) {
				if ( null === $value ) { // Make value NULL to string NULL.
					$value = 'NULL';
				} elseif ( in_array( (int) $fieldinfo[ $key ]->type, [ MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL, MYSQLI_TYPE_BIT, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24, MYSQLI_TYPE_YEAR ], true ) ) { // Is value numeric, no esc.
					$value = empty( $value ) ? 0 : $value;
				} elseif ( in_array( (int) $fieldinfo[ $key ]->type, [ MYSQLI_TYPE_TIMESTAMP, MYSQLI_TYPE_DATE, MYSQLI_TYPE_TIME, MYSQLI_TYPE_DATETIME, MYSQLI_TYPE_NEWDATE ], true ) ) { // Date/time types.
					$value = "'{$value}'";
				} elseif ( $fieldinfo[ $key ]->flags & MYSQLI_BINARY_FLAG ) { // Is value binary.
					$hex   = unpack( 'H*', $value );
					$value = empty( $value ) ? "''" : "0x{$hex[1]}";
				} else {
                    $value = "'" . $this->escapeString($value) . "'";
                }
                $values[] = $value;
			}
			// New query in dump on more than 50000 chars.
			if ( empty( $dump ) ) {
				$dump = 'INSERT INTO `' . $table . '` (`' . implode( '`, `', $fieldsarray ) . "`) VALUES \n";
			}
            if (strlen($dump) <= 50000) {
                $dump .= '(' . implode(', ', $values) . "),\n";
            } else {
                $dump .= '(' . implode(', ', $values) . ");\n";
                $this->write($dump);
                $dump = '';
            }
            ++$done_records;
        }
        if (!empty($dump)) {
            // Remove trailing , and newline.
            $dump = substr($dump, 0, -2) . ";\n";
            $this->write($dump);
		}
		return $done_records;
    }

    /**
     * Dump functions.
     *
     * Dumps all functions found in the database.
     */
	protected function dump_functions()
    {
		try {
			$status_result = $this->query( 'SHOW FUNCTION STATUS', ARRAY_A );
		} catch ( BackWPup_MySQLDump_Exception $e ) {
			$this->logQueryError( $e->getMessage(), 'SHOW FUNCTION STATUS' );

            return;
        }

		foreach ( $status_result as $function ) {
			if ( $function['Db'] !== $this->getDbName() ) {
				continue;
            }

			try {
				$db_identifier       = $this->escapeIdentifier( $function['Db'] );
				$function_identifier = $this->escapeIdentifier( $function['Name'] );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), 'SHOW CREATE FUNCTION' );
				continue;
            }

			$query = sprintf(
				'SHOW CREATE FUNCTION %1$s.%2$s',
				$db_identifier,
				$function_identifier
			);

			try {
				$create_result   = $this->query( $query, ARRAY_A );
				$create_function = $create_result[0] ?? null;

				if ( null === $create_function ) {
					continue;
                }

                $sql = sprintf(
                    "\n--\n" .
                    "-- Function structure for %1\$s\n" .
                    "--\n\n" .
                    "/*!50003 DROP FUNCTION IF EXISTS `%1\$s` */;\n" .
                    "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;\n" .
                    "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;\n" .
                    "/*!50003 SET @saved_col_connection = @@collation_connection */ ;\n" .
                    "/*!50003 SET character_set_client  = %2\$s */ ;\n" .
                    "/*!50003 SET character_set_results = %2\$s */ ;\n" .
                    "/*!50003 SET collation_connection  = %3\$s */ ;\n" .
                    "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;\n" .
                    "/*!50003 SET sql_mode              = '%4\$s' */ ;\n" .
                    "DELIMITER ;;\n" .
                    "%5\$s ;;\n" .
                    "DELIMITER ;\n" .
                    "/*!50003 SET sql_mode              = @saved_sql_mode */ ;\n" .
                    "/*!50003 SET character_set_client  = @saved_cs_client */ ;\n" .
                    "/*!50003 SET character_set_results = @saved_cs_results */ ;\n" .
					"/*!50003 SET collation_connection  = @saved_col_connection */ ;\n",
					$create_function['Function'],
					$create_function['character_set_client'],
					$create_function['collation_connection'],
					$create_function['sql_mode'],
					$create_function['Create Function']
				);
				$this->write( $sql );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), $query );
			}
		}
    }

    /**
     * Dump procedures.
     *
     * Dumps all stored procedures found in the database.
     */
	protected function dump_procedures()
    {
		try {
			$status_result = $this->query( 'SHOW PROCEDURE STATUS', ARRAY_A );
		} catch ( BackWPup_MySQLDump_Exception $e ) {
			$this->logQueryError( $e->getMessage(), 'SHOW PROCEDURE STATUS' );

            return;
        }

		foreach ( $status_result as $procedure ) {
			if ( $procedure['Db'] !== $this->getDbName() ) {
				continue;
            }

			try {
				$db_identifier        = $this->escapeIdentifier( $procedure['Db'] );
				$procedure_identifier = $this->escapeIdentifier( $procedure['Name'] );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), 'SHOW CREATE PROCEDURE' );
				continue;
            }

			$query = sprintf(
				'SHOW CREATE PROCEDURE %1$s.%2$s',
				$db_identifier,
				$procedure_identifier
			);

			try {
				$create_result    = $this->query( $query, ARRAY_A );
				$create_procedure = $create_result[0] ?? null;

				if ( null === $create_procedure ) {
					continue;
                }

                $sql = sprintf(
                    "\n--\n" .
                    "-- Procedure structure for %1\$s\n" .
                    "--\n\n" .
                    "/*!50003 DROP PROCEDURE IF EXISTS `%1\$s` */;\n" .
                    "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;\n" .
                    "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;\n" .
                    "/*!50003 SET @saved_col_connection = @@collation_connection */ ;\n" .
                    "/*!50003 SET character_set_client  = %2\$s */ ;\n" .
                    "/*!50003 SET character_set_results = %2\$s */ ;\n" .
                    "/*!50003 SET collation_connection  = %3\$s */ ;\n" .
                    "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;\n" .
                    "/*!50003 SET sql_mode              = '%4\$s' */ ;\n" .
                    "DELIMITER ;;\n" .
                    "%5\$s ;;\n" .
                    "DELIMITER ;\n" .
                    "/*!50003 SET sql_mode              = @saved_sql_mode */ ;\n" .
                    "/*!50003 SET character_set_client  = @saved_cs_client */ ;\n" .
                    "/*!50003 SET character_set_results = @saved_cs_results */ ;\n" .
					"/*!50003 SET collation_connection  = @saved_col_connection */ ;\n",
					$create_procedure['Procedure'],
					$create_procedure['character_set_client'],
					$create_procedure['collation_connection'],
					$create_procedure['sql_mode'],
					$create_procedure['Create Procedure']
				);
				$this->write( $sql );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), $query );
			}
		}
    }

    /**
     * Dump triggers.
     *
     * Dumps all triggers found in the database.
	 */
	protected function dump_triggers() {
		try {
			$db_identifier = $this->escapeIdentifier( $this->getDbName() );
		} catch ( BackWPup_MySQLDump_Exception $e ) {
			$this->logQueryError( $e->getMessage(), 'SHOW TRIGGERS' );

            return;
        }

		$query = sprintf( 'SHOW TRIGGERS FROM %1$s', $db_identifier );

		try {
			$status_result = $this->query( $query, ARRAY_A );
		} catch ( BackWPup_MySQLDump_Exception $e ) {
			$this->logQueryError( $e->getMessage(), $query );

            return;
        }

		foreach ( $status_result as $trigger ) {
			try {
				$trigger_identifier = $this->escapeIdentifier( $trigger['Trigger'] );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), 'SHOW CREATE TRIGGER' );
				continue;
            }

			$query = sprintf(
				'SHOW CREATE TRIGGER %1$s.%2$s',
				$db_identifier,
				$trigger_identifier
			);

			try {
				$create_result  = $this->query( $query, ARRAY_A );
				$create_trigger = $create_result[0] ?? null;

				if ( null === $create_trigger ) {
					continue;
                }

                $sql = sprintf(
                    "\n--\n" .
                    "-- Trigger structure for %1\$s\n" .
                    "--\n\n" .
                    "/*!50032 DROP TRIGGER IF EXISTS `%1\$s` */;\n" .
                    "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;\n" .
                    "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;\n" .
                    "/*!50003 SET @saved_col_connection = @@collation_connection */ ;\n" .
                    "/*!50003 SET character_set_client  = %2\$s */ ;\n" .
                    "/*!50003 SET character_set_results = %2\$s */ ;\n" .
                    "/*!50003 SET collation_connection  = %3\$s */ ;\n" .
                    "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;\n" .
                    "/*!50003 SET sql_mode              = '%4\$s' */ ;\n" .
                    "DELIMITER ;;\n" .
                    "/*!50003 %5\$s */;;\n" .
                    "DELIMITER ;\n" .
                    "/*!50003 SET sql_mode              = @saved_sql_mode */ ;\n" .
                    "/*!50003 SET character_set_client  = @saved_cs_client */ ;\n" .
                    "/*!50003 SET character_set_results = @saved_cs_results */ ;\n" .
					"/*!50003 SET collation_connection  = @saved_col_connection */ ;\n",
					$create_trigger['Trigger'],
					$create_trigger['character_set_client'],
					$create_trigger['collation_connection'],
					$create_trigger['sql_mode'],
					$create_trigger['SQL Original Statement']
				);
				$this->write( $sql );
			} catch ( BackWPup_MySQLDump_Exception $e ) {
				$this->logQueryError( $e->getMessage(), $query );
			}
		}
    }

    /**
     * Writes data to handle and compress.
     *
     * @param $data string to write
     *
     * @throws BackWPup_MySQLDump_Exception
	 */
	protected function write( $data ) {
		$written = fwrite( $this->handle, (string) $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		if ( ! $written ) {
			throw new BackWPup_MySQLDump_Exception( esc_html__( 'Error while writing file!', 'backwpup' ) );
		}
    }

    /**
     * Closes all confections on shutdown.
	 */
	public function __destruct() {
		// Close MySQL connection.
		if ( null !== $this->wpdb ) {
			$this->wpdb->close();
		}
		// Close file handle.
		if ( is_resource( $this->handle ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing stream resource.
			fclose( $this->handle );
		}
    }

    /**
     * Get table type for given table.
     *
     * @param string $table the table to look up the type for
     *
     * @return string The table type if found
     */
    protected function get_table_type_for($table)
    {
        if (isset($this->table_types[$table])) {
            return $this->table_types[$table];
        }

        return null;
    }

	/**
	 * Ensure table name exists in the whitelist and is safe to use.
	 *
	 * @param string $table
	 *
     * @throws BackWPup_MySQLDump_Exception
	 */
	protected function assertTableName( $table ): void {
		if ( ! isset( $this->table_types[ $table ] ) || $this->isInvalidIdentifier( $table ) ) {
			throw new BackWPup_MySQLDump_Exception(
				sprintf(
					/* translators: %s: table name. */
					esc_html__( 'Invalid table name "%s".', 'backwpup' ),
					esc_html( (string) $table )
				)
			);
		}
    }

	/**
	 * Escape an identifier for SQL usage.
	 *
	 * @param string $identifier
	 *
	 * @return string
	 *
     * @throws BackWPup_MySQLDump_Exception
	 */
	protected function escapeIdentifier( $identifier ) {
		if ( $this->isInvalidIdentifier( $identifier ) ) {
			throw new BackWPup_MySQLDump_Exception(
				sprintf(
					/* translators: %s: database identifier. */
					esc_html__( 'Invalid database identifier "%s".', 'backwpup' ),
					esc_html( (string) $identifier )
				)
			);
        }

		return '`' . $identifier . '`';
	}

	/**
	 * Check whether an identifier contains unsafe characters.
	 *
	 * @param string $identifier
	 *
     * @return bool
	 */
	private function isInvalidIdentifier( $identifier ): bool {
		return '' === $identifier || 1 === preg_match( '/[`\\x00]/', (string) $identifier );
	}

	/**
	 * Build field metadata from the last query.
	 *
	 * @return array<int, object>
	 */
	private function getLastQueryFields(): array {
		$orgnames = (array) $this->wpdb->get_col_info( 'orgname' );
		$types    = (array) $this->wpdb->get_col_info( 'type' );
		$flags    = (array) $this->wpdb->get_col_info( 'flags' );
		$fields   = [];

		foreach ( $orgnames as $index => $name ) {
			$fields[] = (object) [
				'orgname' => $name,
				'type'    => $types[ $index ] ?? null,
				'flags'   => $flags[ $index ] ?? 0,
			];
		}

		return $fields;
	}

    /**
     * Perform query to fetch table rows.
     *
     * @param string $table  The table on which to perform the query
     * @param int    $start  The record to start at
     * @param int    $length How many records to fetch
     *
     * @throws \BackWPup_MySQLDump_Exception In case of mysql error
	 *
	 * @return array{rows: array<int, array<string, mixed>>, fields: array<int, object>} The resulting data
	 */
	protected function do_table_query( $table, $start, $length ) {
		$this->assertTableName( $table );
		$start  = (int) $start;
		$length = (int) $length;

		$query = 'SELECT * FROM `' . $table . '`';
		if ( 0 !== $length || 0 !== $start ) {
			$query .= $this->wpdb->prepare( ' LIMIT %d, %d', $start, $length );
		}

		$rows = $this->query( $query, ARRAY_A );

		return [
			'rows'   => $rows,
			'fields' => $this->getLastQueryFields(),
		];
	}

    /**
     * Escapes a string for MySQL.
     *
     * @param string $value The value to escape
     *
     * @return string The escaped string
	 */
	protected function escapeString( $value ) {
		return $this->wpdb->remove_placeholder_escape( $this->wpdb->_real_escape( $value ) );
	}
}

/**
 * Exception Handler.
 */
class BackWPup_MySQLDump_Exception extends Exception
{
}
