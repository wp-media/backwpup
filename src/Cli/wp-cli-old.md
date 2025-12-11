# WP-CLI commands before version 5.6

## Command: `wp backwpup start`
Starts a backup job with the specific job id

### OPTIONS

\<job_id\>
: Id of job that should be started

### EXAMPLES
 
    wp backwpup start 1

### OUTPUT
_error|success_ Messages line by line from the running job when it runs

## Command: `wp backwpup abort`
Aborts the current running job

### EXAMPLES

 wp backwpup abort

### OUTPUT
_error|success_

## `wp backwpup jobs`
Lists all jobs

### EXAMPLES

    wp backwpup jobs

### OUTPUT
Table of job id and names

| Job ID | Name            |
|:------:|-----------------|
|   1    | File & Database |
|   2    | File            |
|   3    | Database        |
|   7    | Test            |


## Command: `wp backwpup working`
Displays the current working job information and last log line

### EXAMPLES

    wp backwpup working

### OUTPUT
Table of current working job information and last log line

|JobID | Name | Warnings | Errors | On Step | Done | Last message |
|:----:|------|:--------:|:------:|---------|:----:|--------------|
|  1   | Test |    0     |   0    | FILE    |  66  | Message      |


## Command: `wp backwpup decrypt`
Decrypts a backup archive file

### OPTIONS

\<file\>
: file to decrypt. (Will be replaced with the decrypted file.)

[--key=\<key\>]
: Key to decrypt the file with. (String or file. When not provided, it will try to get it from settings)

### EXAMPLES

    wp backwpup decrypt test.tar --key="abcdef1234567890"
    wp backwpup decrypt test.tar --key=./key.private.pem

### OUTPUT
_error|success_

## Command: `wp backwpup encrypt`
Encrypts an backup archive file

### OPTIONS

\<file\>
: file to encrypt. (Will be replaced with the encrypt file.)

[--key=\<key\>]
: Key to encrypt the file with. (String or file. When not provided, it will try to get it from settings)

### EXAMPLES

    wp backwpup encrypt test.tar --key="abcdef1234567890"
    wp backwpup encrypt test.tar --key=./key.public.pem

### OUTPUT
_error|success_

## Command: `wp backwpup activate_legacy_job`
Activate legacy jobs

### OPTIONS

[--type=\<wpcron|link\>]
: type for activation

[--jobIds=\<job_id\>]
: Legacy job ids to activate. (Comma separated)

### EXAMPLES

    wp backwpup activate_legacy_job --type=wpcron --jobIds=2,3

### OUTPUT
_error|success_
