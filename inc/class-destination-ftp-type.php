<?php
interface BackWPup_Destination_Ftp_Type {

	/**
	 * Connect to FTP server
	 *
	 * @param string $user Username.
	 * @param string $password Password.
	 * @param string $host Hostname.
	 * @param array  $args [
	 *   'port' => 21,    //port
	 *   'timeout' => 90, //timeout
	 *   'version' => 3,  //ssh version
	 *   'ssl' => false,  //use ftps
	 *   'pasv' => false, //use passive mode
	 *   'privkey' => ''  //private key for ssh
	 *  ].
	 *
	 * @return bool connected and logged in
	 */
	public function connect( string $user, string $password, string $host, array $args ): bool;

	/**
	 * Disconnect from Server.
	 *
	 * @return void
	 */
	public function disconnect();

	/**
	 * Upload file chunk to server.
	 *
	 * @param string   $remote_filename Remote filename.
	 * @param resource $local_file Local file resource pointer.
	 *
	 * @return bool more to upload.
	 */
	public function upload( string $remote_filename, $local_file ): bool;

	/**
	 * Get file size.
	 *
	 * @param string $remote_filename Remote filename.
	 *
	 * @return int file size in bytes.
	 */
	public function size( string $remote_filename ): int;

	/**
	 * Change and create a directory on the Server.
	 *
	 * @param string $path directory path.
	 *
	 * @return string current working directory.
	 */
	public function chdir( string $path ): string;

	/**
	 * Delete file on the Server.
	 *
	 * @param string $path file path.
	 *
	 * @return bool success
	 */
	public function delete( string $path ): bool;

	/**
	 * Download chunk from server.
	 *
	 * @param string   $remote_filename Remote filename.
	 * @param resource $local_file Local file resource pointer.
	 * @param int      $offset Offset.
	 * @param int      $length Length.
	 */
	public function download( string $remote_filename, $local_file, int $offset = 0, int $length = 2097152 ): void;

	/**
	 * List files on the Server in a given directory.
	 *
	 * @param string $path directory path.
	 *
	 * @return array list of files.
	 */
	public function list_files( string $path ): array;

	/**
	 * Check if the server supports appending to files.
	 *
	 * @return bool
	 */
	public function supports_appending(): bool;
}
