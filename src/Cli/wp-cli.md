# New WP-CLI commands

## Command: `wp backwpup backup`
Show BackWPup backup archives.

### OPTIONS
 
[--format=\<format\>]
: Render output in a particular format.
---  
default: table  
options:  
 - table  
 - json  
 - csv  
 - yaml  
 - count  
 - ids  
---  

[--\<field\>=\<value\>]
: Filter by one or more fields (see “Available Fields” section).

[--field=\<field\>]
: Prints the value of a single field for each job.

[--fields=\<fields\>]
: Comma-separated list of fields to show.

### AVAILABLE FIELDS
These fields will be displayed by default for each job:
 - name
 - time
 - size
 - storage
 - type

These fields are optionally available:
 - folder
 - file
 - job_id
 - size_bytes
 - time_unix

### EXAMPLES

     # Display a list of backups as a table.
     $ wp backwpup backup
     +------------------------------------------------------------+-----------------------------+-----------+---------+------------------------+
     | name                                                       | time                        | size      | storage | type                   |
     +------------------------------------------------------------+-----------------------------+-----------+---------+------------------------+
     | 2025-11-10_13-24-36_SDIUOYJN09_FILE-DBDUMP-WPPLUGIN.tar    | November 10, 2025 @ 1:24 pm | 278.50 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz | November 10, 2025 @ 1:24 pm | 181.57 MB | hidrive | file, dbdump, wpplugin |
     | 2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz | November 10, 2025 @ 1:23 pm | 181.57 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-07_09-22-51_U7IUOYKR09_FILE-DBDUMP-WPPLUGIN.tar    | November 7, 2025 @ 9:22 am  | 278.48 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-07_09-22-34_DXIUOYMY06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 9:22 am  | 181.56 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-07_08-57-28_YHIUOYNL06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:58 am  | 88.00 MB  | hidrive | file, dbdump, wpplugin |
     | 2025-11-07_08-57-28_YHIUOYNL06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:57 am  | 181.56 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-07_08-57-11_IDIUOYPB09_FILE-DBDUMP-WPPLUGIN.tar    | November 7, 2025 @ 8:57 am  | 278.48 MB | folder  | file, dbdump, wpplugin |
     | 2025-11-07_08-56-03_HLIUOYPE06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:57 am  | 112.00 MB | hidrive | file, dbdump, wpplugin |
     | 2025-11-07_08-56-03_HLIUOYPE06_FILE-DBDUMP-WPPLUGIN.tar.gz | November 7, 2025 @ 8:56 am  | 181.56 MB | folder  | file, dbdump, wpplugin |

     # Display only filename and storage in json format
     $ wp backwpup backup --fields=storage,name --format=json
     [{"storage":"folder","name":"2025-11-10_13-24-36_SDIUOYJN09_FILE-DBDUMP-WPPLUGIN.tar"},{"storage":"hidrive","name":"2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz"},{"storage":"folder","name":"2025-11-10_13-23-08_FHIUOYK706_FILE-DBDUMP-WPPLUGIN.tar.gz"}]

### ALIAS
`wp backwpup backups`

## Command: `wp backwpup backup-download`
Download a BackWPup backup archive from a storage. Shows progress bar.

### OPTIONS

\<file\>
: Backup archive file name that should be downloaded.

[\<to_file\>]
: Folder or filename to download to. Default: current working directory with the same name as the backup file.

[--storage=\<storage\>]
: Storage where the file will be downloaded from. (Default: use first found storage.)

[--yes]
: Overwrite a local existing file. (Default: prompt for overwriting)

### EXAMPLES

     # Download a backup archive from first found storage.
     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar
     Start download from Folder:wp-content/uploads/backwpup/d14761/backups/2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar to ./2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar.
     Download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar:  80% [====================================================--------] 0:01 / 0:01
     Success: Backup file downloaded successfully.

     # Download a backup archive from HiDrive
     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar --storage=hidrive
     Error: Backup file not found in storage hidrive.

     # Download a backup archive from first found storage to file test.tar.gz
     $ wp backwpup backup-download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar test.tar.gz
     Confirm: File test.tar.gz already exists. Overwrite it? [y/n]
     Start download from Folder:wp-content/uploads/backwpup/d14761/backups/2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar to ./test.tar.gz.
     Download 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar:  80% [====================================================--------] 0:01 / 0:01
     Success: Backup file downloaded successfully.

### ALIAS
`wp backwpup backups-download`

## Command: `wp backwpup backup-delete`
Delete BackWPup backup archives on storages.

### OPTIONS

\<file\>
: Backup archive file name that should be deleted.

[--storage=\<storage\>]
: Storage where the file will be deleted from. (Default: all that have this file)

[--yes]
: Don't ask for confirmation.

### EXAMPLES

     # Delete backup archive with the given name from all storages
     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar
     Confirm: Delete Backup file 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar on S3 ?
     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO04_FILE-WPPLUGIN.tar deleted successfully on S3.

     # Delete backup archive with the given name from all storages, without asking for confirmation.
     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO06_FILE-WPPLUGIN.tar --yes
     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO06_FILE-WPPLUGIN.tar deleted successfully on hidrive.

     # Delete backup archive only from HiDrive
     $ wp backwpup backup-delete 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar --storage=hidrive
     Confirm: Delete Backup file 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar on hidrive ?
     Success: Backup file 2025-10-23_05-56-19_VPIUOYIO03_FILE-WPPLUGIN.tar deleted successfully on hidrive.

### ALIAS 
`wp backwpup backups-delete`

## PRO Command: `wp backwpup backup-decrypt`
Decrypts a file with BackWPup decryption.

### OPTIONS

\<file\>
: Path to the file that should be encrypted.

[--key=\<key\>]
: The key to use for encryption. Can also be a file that has the key. Default: use the key from the settings.

### EXAMPLES

    # Use the key from the settings.
    $ wp backwpup backup-decrypt archiv.tar.gz
    Success: Archive has been successfully decrypted.

    # Use a specific key.
    $ wp backwpup backup-decrypt archiv.tar.gz --key="ABCDEFGHIJKLMNOPQRSTUVWXYZ123456"
    Success: Archive has been successfully decrypted.

    # Use a specific key from a file.
    $ wp backwpup backup-decrypt archiv.zip --key="./id_rsa_backwpup.pub"
    Success: Archive has been successfully decrypted.

### ALIAS
`wp backwpup decrypt`

## PRO Command: `wp backwpup backup-encrypt`
Encrypts a file with BackWPup encryption.

### OPTIONS

 \<file\>
 : Path to the file that should be encrypted.

 [--key=\<key\>]
 : The key to use for encryption. Can also be a file that has the key. Default: use the key from the settings.

### EXAMPLES

     # Use the key from the settings.
     $ wp backwpup backup-encrypt archiv.tar.gz
     Success: Archive has been successfully encrypted.

     # Use a specific key.
     $ wp backwpup backup-encrypt archiv.tar.gz --key="ABCDEFGHIJKLMNOPQRSTUVWXYZ123456"
     Success: Archive has been successfully encrypted.

     # Use a specific key from a file.
     $ wp backwpup backup-encrypt archiv.zip --key="./id_rsa_backwpup.pub"
     Success: Archive has been successfully encrypted.

### ALIAS
`wp backwpup encrypt`

## Command: `wp backwpup job`
Show BackWPup jobs.

## OPTIONS

[--format=\<format\>]
: Render output in a particular format.  
---  
default: table  
options:  
 - table  
 - json  
 - csv  
 - yaml  
 - count  
 - ids  
---  

[--\<field\>=\<value\>]
: Filter by one or more fields (see “Available Fields” section).

[--field=\<field\>]
: Prints the value of a single field for each job.

[--fields=\<fields\>]
: Comma-separated list of fields to show.

## AVAILABLE FIELDS
These fields will be displayed by default for each job:
- job_id
- name
- active_type
- type
- storages
- cron
- last_run
- last_runtime

These fields are optionally available:
- backup_type
- archive_format
- legacy
- archive_encryption
- email_address_for_logs
- email_on_errors_only

## EXAMPLES

    # Output all jobs as a table
    $ wp backwpup job --field=job_id
    +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+
    | job_id | name             | type                   | storages        | active_type | cron      | last_run                   | last_runtime |
    +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+
    | 6      | Files & Database | file, dbdump, wpplugin | folder, hidrive | wpcron      | 0 0 1 * * | November 7, 2025 @ 9:22 am | 47 Seconds   |
    | 9      | Files & Database | file, dbdump, wpplugin | folder          | wpcron      | 0 0 1 * * | November 7, 2025 @ 9:22 am | 3 Seconds    |
    | 10     | Database         | dbdump                 | folder          | wpcron      | 0 0 1 * * | November 7, 2025 @ 8:51 am |              |
    +--------+------------------+------------------------+-----------------+-------------+-----------+----------------------------+--------------+

    # Outputs only fields job_id and legacy as json
    $ wp backwpup job --fields=job_id,legacy --format=json
    [{"job_id":6,"legacy":"no"},{"job_id":9,"legacy":"no"},{"job_id":10,"legacy":"no"}]

### ALIAS
`wp backwpup jobs`

## Command: `wp backwpup job-activate`
Activate Job (also legacy); Filter by all or selected job IDs

### OPTIONS

[--type=\<type\>]
: the active type the job should have.  
---  
default: wpcron  
options:  
 - wpcron  
 - link  
 - disable  
---  

[--job_id=\<job_id\>]
: Comma-separated list of job IDs to activate. (default all jobs)

### EXAMPLES

     # Activate jobs with ID 1, 2 and 3
     $ wp backwpup job-activate --job_id=1,2,3
     Successes: Job with ID 1 changed to active type wpcron
     Successes: Job with ID 2 changed to active type wpcron
     Successes: Job with ID 4 changed to active type wpcron

     # Deactivate job with ID 3
     $ wp backwpup job-activate --job_id=3 --type=disable
     Successes: Job with ID 3 deactivated

### ALIAS 
`wp backwpup activate-legacy-job`

## PRO Command: `wp backwpup job-export`
Export BackWPup jobs.

### OPTIONS

[\<job_id\>]
: Exports a specific job by ID. If not provided, all will be exported.

[--job_id=\<job_id\>]
: Exports a specific job by ID. If not provided, all will be exported. (Same as only <job_id>.)

[--file=\<file\>]
: The file to export to. When not specified, the file will be printed to the console.

[--passwords]
: Password should also be exported. Important: Passwords are then in plaintext in the Exported JSON.

### EXAMPLES

     # Export all jobs to ./jobs.json file
     $ wp backwpup job-export --file=./jobs.json
     Success: Job exported successfully.

     # Export job with ID 2 to ./jobs.json file and its passwords (cleartext)
     $ wp backwpup job-export 2 --passwords
     Success: Job exported successfully.

### ALIAS
`wp backwpup jobs-export`

## PRO Command: `wp backwpup job-import`
Import BackWPup jobs from Json file.

### OPTIONS

[\<file\>]
: The file to import from.

[--job_id=\<job_id\>]
: Imports a specific job by ID and overwrites existing. If not provided, all will be imported and appended.

[--file=\<file\>]
: The file to import from. (Same as only <file>.)

### EXAMPLES

     # Import all jobs from ./jobs.json file.
     $ wp backwpup job-import ./jobs.json
     Success: Job "Database" with ID 3 imported successfully.
     Success: Job "File & Database" with ID 4 imported successfully.

     # Import job with ID 2 from ./jobs.json file and overwrite an existing job with ID 2
     $ wp backwpup job-import --job_id=2
     Success: Job "Database" with ID 2 imported successfully.

### ALIAS 
`wp backwpup jobs-import`

## Command: `wp backwpup kill`
Kills a running BackWPup job.

### EXAMPLES

     # Kill a running job.
     $ wp backwpup kill
     Success: Job will be terminated.

### ALIAS
`wp backwpup abort`

## Command: `wp backwpup log`
List BackWPup logs with powerful filters and customizable output formats.

### OPTIONS
 
[--search=\<pattern\>]
: Free-text filter matching job, name, type, or status.

[--job_id=\<number\>]
: Filter logs by job ID.

[--type=\<types\>]
: Filter by log type(s), comma-separated. Example: FILE,DBDUMP

[--after=\<date\>]
: Include logs on or after the given date (YYYY-MM-DD).

[--before=\<date\>]
: Include logs on or before the given date (YYYY-MM-DD).

[--orderby=\<field\>]
: Sort by a field.
---
default: time
options:
 - time
 - job
 - status
 - size
 - runtime
---

[--order=\<asc|desc\>]
: Sort direction.
---
default: desc
---

[--limit=\<number\>]
: Limit number of results returned.

[--fields=\<fields\>]
: Comma-separated list of fields to show.

[--format=\<format\>]
: Output format.
---
default: table
options:
 - table
 - csv
 - json
 - yaml
 - count
---

### AVAILABLE FIELDS
These fields will be displayed by default for each job:
- time
- job
- status
- size
- runtime
- name
- type

These fields are optionally available:
- timestamp
- job_id
- errors
- warnings

### EXAMPLES

    # View logs as a table (default):
    $ wp backwpup log
    +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
    | name                                         | time                        | job                   | type                   | size      | runtime | status   |
    +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
    | backwpup_log_440bd7_2025-11-10_13-24-36.html | November 10, 2025 @ 1:24 pm | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
    | backwpup_log_e5672b_2025-11-10_13-23-08.html | November 10, 2025 @ 1:23 pm | Files & Database      | file, dbdump, wpplugin | 181.57 MB | 74 s    | 2 ERRORS |
    | backwpup_log_e68e31_2025-11-07_09-22-51.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 3 s     | O.K.     |
    | backwpup_log_05a7f7_2025-11-07_09-22-34.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin |           | 0 s     | O.K.     |
    | backwpup_log_459409_2025-11-07_08-57-28.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin |           | 0 s     | O.K.     |
    | backwpup_log_6f3f67_2025-11-07_08-57-11.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 2 s     | O.K.     |
    | backwpup_log_fa5f45_2025-11-07_08-56-03.html | November 7, 2025 @ 8:56 am  | Files & Database      | file, dbdump, wpplugin | 181.56 MB | 47 s    | 2 ERRORS |

    # Filter logs by text:
    $ wp backwpup logs --search=backup
    +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+
    | name                                         | time                        | job        | type                   | size      | runtime | status |
    +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+
    | backwpup_log_ac7889_2025-11-01_10-16-28.html | November 1, 2025 @ 10:16 am | Backup Now | file, dbdump, wpplugin | 282.68 MB | 5 s     | O.K.   |
    +----------------------------------------------+-----------------------------+------------+------------------------+-----------+---------+--------+

    # Show only logs for job ID 3:
    $ wp backwpup logs --job_id=3
    +----------------------------------------------+-----------------------------+------------------+------------------------+-----------+---------+----------+
    | name                                         | time                        | job              | type                   | size      | runtime | status   |
    +----------------------------------------------+-----------------------------+------------------+------------------------+-----------+---------+----------+
    | backwpup_log_e5672b_2025-11-10_13-23-08.html | November 10, 2025 @ 1:23 pm | Files & Database | file, dbdump, wpplugin | 181.57 MB | 74 s    | 2 ERRORS |
    | backwpup_log_05a7f7_2025-11-07_09-22-34.html | November 7, 2025 @ 9:22 am  | Files & Database | file, dbdump, wpplugin |           | 0 s     | O.K.     |
    | backwpup_log_459409_2025-11-07_08-57-28.html | November 7, 2025 @ 8:57 am  | Files & Database | file, dbdump, wpplugin |           | 0 s     | O.K.     |
    | backwpup_log_fa5f45_2025-11-07_08-56-03.html | November 7, 2025 @ 8:56 am  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 47 s    | 2 ERRORS |
    | backwpup_log_a09990_2025-11-07_08-38-26.html | November 7, 2025 @ 8:38 am  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 76 s    | O.K.     |
    | backwpup_log_8f7ac2_2025-11-06_13-50-50.html | November 6, 2025 @ 1:50 pm  | Files & Database | file, dbdump, wpplugin | 181.56 MB | 82 s    | O.K.     |

    # List logs after a date sorted by size:
    $ wp backwpup logs --after=2025-01-01 --orderby=size --order=desc
    +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
    | name                                         | time                        | job                   | type                   | size      | runtime | status   |
    +----------------------------------------------+-----------------------------+-----------------------+------------------------+-----------+---------+----------+
    | backwpup_log_ac7889_2025-11-01_10-16-28.html | November 1, 2025 @ 10:16 am | Backup Now            | file, dbdump, wpplugin | 282.68 MB | 5 s     | O.K.     |
    | backwpup_log_727958_2025-11-05_07-10-18.html | November 5, 2025 @ 7:10 am  | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
    | backwpup_log_440bd7_2025-11-10_13-24-36.html | November 10, 2025 @ 1:24 pm | Files & Database      | file, dbdump, wpplugin | 278.50 MB | 2 s     | O.K.     |
    | backwpup_log_e68e31_2025-11-07_09-22-51.html | November 7, 2025 @ 9:22 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 3 s     | O.K.     |
    | backwpup_log_6f3f67_2025-11-07_08-57-11.html | November 7, 2025 @ 8:57 am  | Files & Database      | file, dbdump, wpplugin | 278.48 MB | 2 s     | O.K.     |
    | backwpup_log_47facf_2025-10-24_14-12-58.html | October 24, 2025 @ 2:12 pm  | Files & Database      | file, dbdump, wpplugin | 182.15 MB | 61 s    | O.K.     |

    # Output as JSON:
    $ wp backwpup logs --format=json
    [{"name":"backwpup_log_440bd7_2025-11-10_13-24-36.html","time":"November 10, 2025 @ 1:24 pm","job":"Files & Database","type":"file, dbdump, wpplugin","size":"292023322","runtime":2,"status":"O.K."},{"name":"backwpup_log_e5672b_2025-11-10_13-23-08.html","time":"November 10, 2025 @ 1:23 pm","job":"Files & Database","type":"file, dbdump, wpplugin","size":"190387274","runtime":74,"status":"2 ERRORS"},{"name":"backwpup_log_e68e31_2025-11-07_09-22-51.html","time":"November 7, 2025 @ 9:22 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"292007450","runtime":3,"status":"O.K."},{"name":"backwpup_log_05a7f7_2025-11-07_09-22-34.html","time":"November 7, 2025 @ 9:22 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"0","runtime":0,"status":"O.K."},{"name":"backwpup_log_459409_2025-11-07_08-57-28.html","time":"November 7, 2025 @ 8:57 am","job":"Files & Database","type":"file, dbdump, wpplugin","size":"0","runtime":0,"status":"O.K."}]

### ALIAS
`wp backwpup logs`

## Command: `wp backwpup log-show`
Show a BackWPup log as plain text (HTML ignored).

### OPTIONS

[\<file\>]
: Basename inside the log folder. Extension optional (e.g., backwpup_log_XXXX_YYYY.html or .html.gz).

[--file=\<file\>]
: Alternative to positional <file>. Extension optional.

[--job_id=\<number\>]
: If no <file>/--file is provided, select the latest log for a given job ID.

[--lines=\<number\>]
: Print only the last N lines (tail-like).

### EXAMPLES

    # Show by positional file:
    $ wp backwpup log-show backwpup_log_8a8bee_2025-11-03_06-30-29.html
    BackWPup log for Files & Database from November 7, 2025 at 8:57 am
    [INFO] BackWPup 5.6.0; A project of WP Media
    [INFO] WordPress 6.8.3 on https://backwpup-pro.ddev.site/
    [INFO] Log Level: Debug
    [INFO] BackWPup job: Files & Database; FILE+DBDUMP+WPPLUGIN
    [INFO] Runs with user:  (0)

    # Show by filename:
    $ wp backwpup log-show --file=backwpup_log_8a8bee_2025-11-03_06-30-29.html
    [INFO] BackWPup 5.6.0; A project of WP Media
    [INFO] WordPress 6.8.3 on https://backwpup-pro.ddev.site/
    [INFO] Log Level: Debug
    [INFO] BackWPup job: Files & Database; FILE+DBDUMP+WPPLUGIN
    [INFO] Runs with user:  (0)

    # Show only the last 200 lines:
    $ wp backwpup log-show --file=backwpup_log_... --lines=14
    [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/sodium_compat/src/Core32/SecretStream/
    [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/sodium_compat/src/PHP52/
    [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/style-engine/
    [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/theme-compat/
    [07-Nov-2025 08:57:36] Archiving Folder: /var/www/html/.ddev/wordpress/wp-includes/widgets/
    [07-Nov-2025 08:57:36] Backup archive created.
    [07-Nov-2025 08:57:36] Archive size is 181.56 MB.
    [07-Nov-2025 08:57:36] 8657 Files with 272.06 MB in Archive.
    [07-Nov-2025 08:57:36] Restart after 8 seconds.
    [07-Nov-2025 08:57:36] 1. Trying to encrypt archive …
    [07-Nov-2025 08:57:37] Encrypted 181.63 MB of data.
    [07-Nov-2025 08:57:37] Archive has been successfully encrypted.
    [07-Nov-2025 08:57:37] 1. Try to send backup file to HiDrive …
    [07-Nov-2025 08:58:04] Restart after 28 seconds.

### ALIAS
`wp backwpup logs-show`

## Command: `wp backwpup log-delete`
Delete BackWPup log files.

### OPTIONS

[\<file\>]
: Delete a single file (basename; extension optional).

[--file=\<file\>]
: Alternative to positional <file>.

[--older-than=<relative-time>]
: Delete logs older than a relative time, e.g., "7 days", "48 hours".

[--all]
: Delete all logs.

[--yes]
: Don't ask for confirmation.

### EXAMPLES

    # Delete a single log file.
    $ wp backwpup log-delete --file=backwpup_log_8a8bee_2025-11-03_06-30-29.html
    Confirm: Delete logfile backwpup_log_8a8bee_2025-11-03_06-30-29.html ?
    1 logfile deleted.

    # Delete logs older than 30 days.
    $ wp backwpup log-delete --older-than="30 days"
    Confirm: Delete 11 logfile(s) ?
    11 logfile(s) deleted.

### ALIAS
`wp backwpup logs-delete`

## Command: `wp backwpup run`
Start a BackWPup job.

### OPTIONS

[\<job_id\>]
: The IDs of the jobs to start as a comma-separated list.

[--job_id=\<job_id\>]
: The IDs of the jobs to start as a comma-separated list. (Same as only <job_id>.)

[--now]
: Starts a backup now. (Not working with <job_id>.)

[--background]
: Starts a backup as a background process. (Works only with one <job_id>)

### EXAMPLES

     # Start a job with ID 1 in background.
     $ wp backwpup run 1 --background
     Success: Job "File" runs now in background.

     # Start jobs with ID 1, 2 and 3. Jobs will run one after the other.
     $ wp backwpup run 1,2,3
     [INFO] BackWPup 5.6.0; A project of WP Media
     ...
     Job done in 2 seconds.
     Successes: Job runs successfully.

     # Start a backup now job.
     $ wp backwpup run --now
     [INFO] BackWPup 5.6.0; A project of WP Media
     ...
     Job done in 10 seconds.
     Successes: Job runs successfully.

### ALIAS
`wp backwpup start`

## PRO Command: `wp backwpup restore`
Restore a BackWPup backup from an archive file (.tar/.tar.gz/.zip).

### OPTIONS

[\<file\>]
: Path to the backup archive (positional). Alternative: --file=<file>.

[--file=\<file\>]
: Path to the backup archive (if positional not provided).

[--pre-backup]
: Create a safety snapshot (DB export + wp-content copy) before restoring.

[--type=\<full|db|file>\]
: what to restore: full (DB + wp-content), db (DB only), file (wp-content only)
---
default: full
---

[--yes]
: Answer yes to the confirmation prompts (destructive actions).

### EXAMPLES

    # Restore backup from a backwpup archive file.
    $ wp backwpup restore ./2025-11-06_05_07_03-20_CXIOYMK_FILE-DBDUMP-WPPLUGIN.tar.gz
    Confirm: This will RESET the database. Continue? [y/n]
    ...
    Confirm: This will REPLACE wp-content. Continue? [y/n]
    ...
    Success: Restore completed successfully.

    # Restore backup from a archive file without prompts.
    $ wp backwpup restore ./2025-11-06_05_07_03-20_CXIOYMK_FILE-DBDUMP-WPPLUGIN.tar.gz --yes
    ...
    Success: Restore completed successfully.
 
    # Backup data before overwriting with Restore from a file without prompts.
    $ wp backwpup restore --file=/path/to/backup.zip --pre-backup --yes
    ...
    Success: Restore completed successfully.

### ALIAS
`wp backwpup backup-restore`

## Command: `wp backwpup status`
Shows the status about a running BackWPup job. With a progress bar.

### EXAMPLES

     # Display the status of a running job.
     $ wp backwpup status
     Success: No job running.

### ALIAS
`wp backwpup working`

## Command: `wp backwpup version`
Show BackWPup Plugin Version.

### OPTIONS

[--debug]
: Show debug information (Global option).

### EXAMPLES

    # Show BackWPup Plugin Version.
	$ wp backwpup version
	BackWPup 5.6.0
  
### OUTPUT
    # Show BackWPup Plugin Version.
    $ wp backwpup version
    BackWPup 5.6.0

    # Show BackWPup Plugin Version and BackWPup Settings.
    $ wp backwpup version --debug
    BackWPup 5.6.0
    Debug: Document root: /var/www/html/(0.487s)
    Debug: Temp folder: /var/www/html/wp-content/uploads/backwpup/d14761/temp/ (0.487s)
    Debug: Log folder: /var/www/html/wp-content/uploads/backwpup/d14761/logs/ (0.487s)
    Debug: Server:  (0.487s)
    Debug: Operating System: Linux (0.487s)
    Debug: PHP SAPI: cli (0.487s)
    Debug: Current PHP user: root (0.487s)
    Debug: Maximum execution time: 0 seconds (0.487s)
    Debug: BackWPup maximum script execution time: 30 seconds (0.487s)
    Debug: Alternative WP Cron: Off (0.487s)
    Debug: Disabled WP Cron: Off (0.487s)
    Debug: WP Cron is working: Yes (0.487s)
    Debug: CHMOD Dir: 0755 (0.487s)
    Debug: Server Time: 10:27 (0.487s)
    Debug: Blog Time: 10:27 (0.487s)
    Debug: Blog Timezone:  (0.487s)
    Debug: Blog Time offset: 0 hours (0.487s)
    Debug: Blog language: en-US (0.487s)
    Debug: MySQL Client encoding: utf8 (0.487s)
    Debug: PHP Memory limit: -1 (0.487s)
    Debug: WP memory limit: 40M (0.487s)
    Debug: WP maximum memory limit: -1 (0.487s)
    Debug: Memory in use: 12.00 MB (0.487s)
    Debug: Loaded PHP Extensions: Core, ... (0.487s)