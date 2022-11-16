<?php

use Inpsyde\BackWPup\Infrastructure\Http\Authentication\BasicAuthCredentials;
use Inpsyde\BackWPup\Infrastructure\Http\Client\WpHttpClient;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\AuthorizationRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\FormRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator\JsonRequest;
use Inpsyde\BackWPup\Infrastructure\Http\Message\RequestFactory;
use Inpsyde\BackWPup\Infrastructure\Http\Message\ResponseFactory;
use Inpsyde\BackWPup\Infrastructure\Http\Message\StreamFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

/**
 * Class for communicating with Dropbox API V2.
 */
class BackWPup_Destination_Dropbox_API
{
    /**
     * URL to Dropbox API endpoint.
     */
    public const API_URL = 'https://api.dropboxapi.com/';

    /**
     * URL to Dropbox content endpoint.
     */
    public const API_CONTENT_URL = 'https://content.dropboxapi.com/';

    /**
     * URL to Dropbox for authentication.
     */
    public const API_WWW_URL = 'https://www.dropbox.com/';

    /**
     * API version.
     */
    public const API_VERSION_URL = '2/';

    /**
     * oAuth vars.
     *
     * @var string
     */
    private $oauthAppKey = '';

    /**
     * @var string
     */
    private $oauthAppSecret = '';

    /**
     * @var string
     */
    private $oauthToken = [];

    /**
     * Job object for logging.
     *
     * @var BackWPup_Job
     */
    private $jobObject;

    /**
     * Callback to call when token is refreshed.
     *
     * @var callable
     */
    private $listener;

    /**
     * The user agent to use in Dropbox requests.
     *
     * @var string
     */
    private $userAgent;

    /**
     * A path to the SSL ca-bundle file to use in Dropbox requests.
     *
     * @var string
     */
    private $caBundle;

    /**
     * @param string $boxtype
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     */
    public function __construct($boxtype = 'dropbox', BackWPup_Job $jobObject = null)
    {
        if ($boxtype === 'dropbox') {
            $this->oauthAppKey = get_site_option(
                'backwpup_cfg_dropboxappkey',
                base64_decode('NXdtdXl0cm5qZzB5aHhw')
            );
            $this->oauthAppSecret = BackWPup_Encryption::decrypt(
                get_site_option('backwpup_cfg_dropboxappsecret', base64_decode('cXYzZmp2N2IxcG1rbWxy'))
            );
        } else {
            $this->oauthAppKey = get_site_option(
                'backwpup_cfg_dropboxsandboxappkey',
                base64_decode('a3RqeTJwdXFwZWVydW92')
            );
            $this->oauthAppSecret = BackWPup_Encryption::decrypt(
                get_site_option('backwpup_cfg_dropboxsandboxappsecret', base64_decode('aXJ1eDF3Ym9mMHM5eGp6'))
            );
        }

        if (empty($this->oauthAppKey) || empty($this->oauthAppSecret)) {
            throw new BackWPup_Destination_Dropbox_API_Exception('No App key or App Secret specified.');
        }

        $this->jobObject = $jobObject;
    }

    /**
     * List a folder.
     *
     * This is a functions method to use filesListFolder and
     * filesListFolderContinue to construct an array of files within a given
     * folder path.
     *
     * @param string $path
     *
     * @return array
     */
    public function listFolder($path)
    {
        $files = [];
        $result = $this->filesListFolder(['path' => $path]);

        if (!$result) {
            return [];
        }

        $files = array_merge($files, $result['entries']);

        $args = ['cursor' => $result['cursor']];

        while ($result['has_more'] === true) {
            $result = $this->filesListFolderContinue($args);
            $files = array_merge($files, $result['entries']);
            $args['cursor'] = $result['cursor'];
        }

        return $files;
    }

    /**
     * Uploads a file to Dropbox.
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     *
     * @return array
     */
    public function upload(string $file, string $path = '', bool $overwrite = true)
    {
        $file = str_replace('\\', '/', $file);

        if (!is_readable($file)) {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                "Error: File \"{$file}\" is not readable or doesn't exist."
            );
        }

        if (filesize($file) < 5242880) { //chunk transfer on bigger uploads
            return $this->filesUpload(
                [
                    'contents' => file_get_contents($file),
                    'path' => $path,
                    'mode' => ($overwrite) ? 'overwrite' : 'add',
                ]
            );
        }

        return $this->multipartUpload($file, $path, $overwrite);
    }

    /**
     * @param $file
     * @param string $path
     * @param bool   $overwrite
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     *
     * @return array|mixed|string
     */
    public function multipartUpload($file, $path = '', $overwrite = true)
    {
        $file = str_replace('\\', '/', $file);

        if (!is_readable($file)) {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                "Error: File \"{$file}\" is not readable or doesn't exist."
            );
        }

        $chunkSize = 4194304; //4194304 = 4MB

        $fileHandle = fopen($file, 'rb');
        if (!$fileHandle) {
            throw new BackWPup_Destination_Dropbox_API_Exception('Can not open source file for transfer.');
        }

        if (!isset($this->jobObject->steps_data[$this->jobObject->step_working]['uploadid'])) {
            $this->jobObject->log(__('Beginning new file upload session', 'backwpup'));
            $session = $this->filesUploadSessionStart(
            );
            $this->jobObject->steps_data[$this->jobObject->step_working]['uploadid'] = $session['session_id'];
        }
        if (!isset($this->jobObject->steps_data[$this->jobObject->step_working]['offset'])) {
            $this->jobObject->steps_data[$this->jobObject->step_working]['offset'] = 0;
        }
        if (!isset($this->jobObject->steps_data[$this->jobObject->step_working]['totalread'])) {
            $this->jobObject->steps_data[$this->jobObject->step_working]['totalread'] = 0;
        }

        //seek to current position
        if ($this->jobObject->steps_data[$this->jobObject->step_working]['offset'] > 0) {
            fseek($fileHandle, $this->jobObject->steps_data[$this->jobObject->step_working]['offset']);
        }

        while ($data = fread($fileHandle, $chunkSize)) {
            $chunkUploadStart = microtime(true);

            if ($this->jobObject->is_debug()) {
                $this->jobObject->log(
                    sprintf(__('Uploading %s of data', 'backwpup'), size_format(strlen($data)))
                );
            }

            $this->filesUploadSessionAppendV2(
                [
                    'contents' => $data,
                    'cursor' => [
                        'session_id' => $this->jobObject->steps_data[$this->jobObject->step_working]['uploadid'],
                        'offset' => $this->jobObject->steps_data[$this->jobObject->step_working]['offset'],
                    ],
                ]
            );
            $chunkUploadTime = microtime(
                true
            ) - $chunkUploadStart;
            $this->jobObject->steps_data[$this->jobObject->step_working]['totalread'] += strlen($data);

            //args for next chunk
            $this->jobObject->steps_data[$this->jobObject->step_working]['offset'] += $chunkSize;
            if ($this->jobObject->job['backuptype'] === 'archive') {
                $this->jobObject->substeps_done = $this->jobObject->steps_data[$this->jobObject->step_working]['offset'];
                if (strlen($data) == $chunkSize) {
                    $timeRemaining = $this->jobObject->do_restart_time();
                    //calc next chunk
                    if ($timeRemaining < $chunkUploadTime) {
                        $chunkSize = floor($chunkSize / $chunkUploadTime * ($timeRemaining - 3));
                        if ($chunkSize < 0) {
                            $chunkSize = 1024;
                        }
                        if ($chunkSize > 4194304) {
                            $chunkSize = 4194304;
                        }
                    }
                }
            }
            $this->jobObject->update_working_data();
            //correct position
            fseek($fileHandle, $this->jobObject->steps_data[$this->jobObject->step_working]['offset']);
        }

        fclose($fileHandle);

        $this->jobObject->log(
            sprintf(
                __('Finishing upload session with a total of %s uploaded', 'backwpup'),
                size_format($this->jobObject->steps_data[$this->jobObject->step_working]['totalread'])
            )
        );

        $response = $this->filesUploadSessionFinish(
            [
                'cursor' => [
                    'session_id' => $this->jobObject->steps_data[$this->jobObject->step_working]['uploadid'],
                    'offset' => $this->jobObject->steps_data[$this->jobObject->step_working]['totalread'],
                ],
                'commit' => [
                    'path' => $path,
                    'mode' => ($overwrite) ? 'overwrite' : 'add',
                ],
            ]
        );

        unset($this->jobObject->steps_data[$this->jobObject->step_working]['uploadid'], $this->jobObject->steps_data[$this->jobObject->step_working]['offset']);

        return $response;
    }

    /**
     * Set the oauth tokens for this request.
     *
     * @param array    $token    The array with access and refresh tokens
     * @param callable $listener The callback to be called when a new token is fetched
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     */
    public function setOAuthTokens(array $token, $listener = null)
    {
        if (empty($token['access_token'])) {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                __('No access token provided', 'backwpup')
            );
        }

        if (empty($token['refresh_token'])) {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                __('No refresh token provided. You may need to reauthenticate with Dropbox', 'backwpup')
            );
        }

        $this->oauthToken = $token;

        if (isset($listener) && is_callable($listener)) {
            $this->listener = $listener;
        }

        if (isset($token['expires']) && time() > $token['expires']) {
            $token = $this->refresh($token['refresh_token']);
            $this->notifyRefresh($token);
        }
    }

    /**
     * Get the current token array.
     *
     * Also modifies expires_in to match how much time is left until the access token expires.
     *
     * @throws BadMethodCallException If tokens have not been set
     *
     * @return array The token array
     */
    public function getTokens()
    {
        $now = time();
        $tokens = $this->oauthToken;
        if (empty($tokens)) {
            throw new \BadMethodCallException(
                __('OAuth tokens have not been set.', 'backwpup')
            );
        }

        if ($tokens['expires'] > $now) {
            $tokens['expires_in'] = $tokens['expires'] - $now;
        } else {
            $tokens = $this->refresh($tokens['refresh_token']);
            $this->notifyRefresh($tokens);
        }

        return $tokens;
    }

    /**
     * Returns the URL to authorize the user.
     *
     * @return string The authorization URL
     */
    public function oAuthAuthorize()
    {
        return self::API_WWW_URL . 'oauth2/authorize?response_type=code&client_id=' . $this->oauthAppKey . '&token_access_type=offline';
    }

    /**
     * Takes the oauth code and returns the access token.
     *
     * @param string $code The oauth code
     *
     * @return array an array including the access token, account ID, expiration, and
     *               other information
     */
    public function oAuthToken($code)
    {
        $token = $this->request(
            'oauth2/token',
            [
                'code' => trim($code),
                'grant_type' => 'authorization_code',
            ],
            'oauth'
        );

        $token['expires'] = time() + $token['expires_in'];

        return $token;
    }

    /**
     * Returns a new access token given the refresh token.
     *
     * @param string $refreshToken The refresh token
     *
     * @return array an array including the access token, account ID, expiration, and
     *               other information
     */
    public function refresh($refreshToken)
    {
        $token = $this->request(
            'oauth2/token',
            [
                'refresh_token' => trim($refreshToken),
                'grant_type' => 'refresh_token',
            ],
            'oauth'
        );

        $token['expires'] = time() + $token['expires_in'];

        $this->oauthToken = array_merge($this->oauthToken, $token);

        return $this->oauthToken;
    }

    /**
     * Notifies the listener that the access token was refreshed.
     *
     * @param array $token The new token
     */
    private function notifyRefresh(array $token)
    {
        if (isset($this->listener)) {
            call_user_func($this->listener, $token);
        }
    }

    /**
     * Revokes the auth token.
     *
     * @return array
     */
    public function authTokenRevoke()
    {
        return $this->request('auth/token/revoke');
    }

    /**
     * Download.
     *
     * @param array $args argument for the api request
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception because of a rate limit
     *
     * @return mixed whatever the api request returns
     */
    public function download($args, $startByte = null, $endByte = null)
    {
        $args['path'] = $this->formatPath($args['path']);

        if ($startByte !== null && $endByte !== null) {
            return $this->request('files/download', $args, 'download', false, "{$startByte}-{$endByte}");
        }

        return $this->request('files/download', $args, 'download');
    }

    /**
     * Deletes a file.
     *
     * @param array $args An array of arguments
     *
     * @return array|null Information on the deleted file
     */
    public function filesDelete($args): ?array
    {
        $args['path'] = $this->formatPath($args['path']);

        try {
            return $this->request('files/delete', $args);
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesDeleteError($e->getError());

            return null;
        }
    }

    /**
     * Gets the metadata of a file.
     *
     * @param array $args An array of arguments
     *
     * @return array|null The file's metadata
     */
    public function filesGetMetadata($args): ?array
    {
        $args['path'] = $this->formatPath($args['path']);

        try {
            return $this->request('files/get_metadata', $args);
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesGetMetadataError($e->getError());

            return null;
        }
    }

    /**
     * Gets a temporary link from Dropbox to access the file.
     *
     * @param array $args An array of arguments
     *
     * @return array|null Information on the file and link
     */
    public function filesGetTemporaryLink($args): ?array
    {
        $args['path'] = $this->formatPath($args['path']);

        try {
            return $this->request('files/get_temporary_link', $args);
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesGetTemporaryLinkError($e->getError());

            return null;
        }
    }

    /**
     * Lists all the files within a folder.
     *
     * @param array $args An array of arguments
     *
     * @return array|null A list of files
     */
    public function filesListFolder($args): ?array
    {
        $args['path'] = $this->formatPath($args['path']);

        try {
            return $this->request('files/list_folder', $args);
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesListFolderError($e->getError());

            return null;
        }
    }

    /**
     * Continue to list more files.
     *
     * When a folder has a lot of files, the API won't return all at once.
     * So this method is to fetch more of them.
     *
     * @param array $args An array of arguments
     *
     * @return array|null An array of files
     */
    public function filesListFolderContinue($args): ?array
    {
        try {
            return $this->request('files/list_folder/continue', $args);
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesListFolderContinueError($e->getError());

            return null;
        }
    }

    /**
     * Uploads a file to Dropbox.
     *
     * The file must be no greater than 150 MB.
     *
     * @param array $args An array of arguments
     *
     * @return array|null the uploaded file's information
     */
    public function filesUpload($args)
    {
        $args['path'] = $this->formatPath($args['path']);

        if (isset($args['client_modified'])
            && $args['client_modified'] instanceof DateTime
        ) {
            $args['client_modified'] = $args['client_modified']->format('Y-m-d\TH:m:s\Z');
        }

        try {
            return $this->request('files/upload', $args, 'upload');
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $this->handleFilesUploadError($e->getError());

            return null;
        }
    }

    /**
     * Append more data to an uploading file.
     *
     * @param array $args An array of arguments
     */
    public function filesUploadSessionAppendV2($args)
    {
        try {
            return $this->request(
                'files/upload_session/append_v2',
                $args,
                'upload'
            );
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $error = $e->getError();

            // See if we can fix the error first
            if ($error['.tag'] === 'incorrect_offset') {
                $args['cursor']['offset'] = $error['correct_offset'];

                return $this->request(
                    'files/upload_session/append_v2',
                    $args,
                    'upload'
                );
            }

            // Otherwise, can't fix
            $this->handleFilesUploadSessionLookupError($error);
        }
    }

    /**
     * Finish an upload session.
     *
     * @param array $args
     *
     * @return array|null Information on the uploaded file
     */
    public function filesUploadSessionFinish($args): ?array
    {
        $args['commit']['path'] = $this->formatPath($args['commit']['path']);

        try {
            return $this->request('files/upload_session/finish', $args, 'upload');
        } catch (BackWPup_Destination_Dropbox_API_Request_Exception $e) {
            $error = $e->getError();
            if ($error['.tag'] === 'lookup_failed') {
                if ($error['lookup_failed']['.tag'] === 'incorrect_offset') {
                    $args['cursor']['offset'] = $error['lookup_failed']['correct_offset'];

                    return $this->request('files/upload_session/finish', $args, 'upload');
                }
            }
            $this->handleFilesUploadSessionFinishError($e->getError());

            return null;
        }
    }

    /**
     * Starts an upload session.
     *
     * When a file larger than 150 MB needs to be uploaded, then this API
     * endpoint is used to start a session to allow the file to be uploaded in
     * chunks.
     *
     * @param array $args
     *
     * @return array an array containing the session's ID
     */
    public function filesUploadSessionStart($args = [])
    {
        return $this->request('files/upload_session/start', $args, 'upload');
    }

    /**
     * Get user's current account info.
     *
     * @return array
     */
    public function usersGetCurrentAccount()
    {
        return $this->request('users/get_current_account');
    }

    /**
     * Get quota info for this user.
     *
     * @return array
     */
    public function usersGetSpaceUsage()
    {
        return $this->request('users/get_space_usage');
    }

    /**
     * Get the user agent.
     *
     * If no user agent has been provided, defaults to `BackWPup::get_plugin_data('User-Agent')`.
     *
     * @return string The user agent
     */
    public function getUserAgent()
    {
        return $this->userAgent ?: \BackWPup::get_plugin_data('User-Agent');
    }

    /**
     * Set the user agent.
     *
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Get the SSL ca-bundle path.
     *
     * If no ca-bundle has been provided, defaults to `BackWPup::get_plugin_data('cacert')`.
     *
     * @return string The SSL ca-bundle
     */
    public function getCaBundle()
    {
        return $this->caBundle ?: \BackWPup::get_plugin_data('cacert');
    }

    /**
     * Set the path to the SSL ca-bundle.
     *
     * @param string $caBundle The path to the ca-bundle file
     */
    public function setCaBundle($caBundle)
    {
        $this->caBundle = $caBundle;
    }

    /**
     * Set the job object.
     *
     * @param BackWPup_Job $jobObject The job object to set
     */
    public function setJobObject(BackWPup_Job $jobObject)
    {
        $this->jobObject = $jobObject;
    }

    /**
     * Logs a message to the current job.
     *
     * @param string $message The message to log
     * @param int    $level   The log level
     *
     * @return bool|null True on success, null if no job object set
     */
    protected function log($message, $level = E_USER_NOTICE)
    {
        if (!isset($this->jobObject)) {
            return null;
        }

        return $this->jobObject->log($message, $level);
    }

    /**
     * Logs debug info about the current request.
     *
     * @param string $endpoint The current request endpoint
     * @param array  $args     The request args
     *
     * @return bool|null True on success, null if no job object set or debug is not enabled
     */
    protected function logRequest($endpoint, array $args)
    {
        if (!isset($this->jobObject) || !$this->jobObject->is_debug()) {
            return null;
        }

        $message = "Call to {$endpoint}";

        if (isset($args['contents'])) {
            $message .= ' with ' . size_format(strlen($args['contents'])) . ' of data,';
            unset($args['contents']);
        }

        if (!empty($args)) {
            $message .= ' with parameters ' . json_encode($args);
        }

        return $this->log($message);
    }

    /**
     * Request.
     *
     * @param string $url
     * @param array  $args
     * @param string $endpointFormat
     * @param bool   $echo
     * @param string $bytes
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     *
     * @return array|mixed|string
     */
    public function request($endpoint, $args = [], $endpointFormat = 'rpc', $echo = false, $bytes = null)
    {
        // Log request
        $this->logRequest($endpoint, $args);

        if ($bytes !== null) {
            $args['bytes'] = $bytes;
        }

        $request = $this->buildRequest($endpoint, $args, $endpointFormat);
        $client = $this->createClient();

        $response = $client->sendRequest($request);

        if ($response->getStatusCode() >= 500) {
            $this->handleServerException($response);
        } elseif ($response->getStatusCode() >= 400) {
            $this->handleRequestException($response);

            // If we're still here, then recurse
            return $this->request($endpoint, $args, $endpointFormat, $echo, $bytes);
        }

        if ($echo === true) {
            echo $response->getBody(); // phpcs:ignore
        }

        if ($response->getHeaderLine('Content-Type') === 'application/json') {
            return json_decode($response->getBody(), true);
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets the full URL to the Dropbox endpoint.
     *
     * @param string $endpoint The API endpoint
     * @param string $format   The endpoint Format
     *
     * @return string The full URL
     */
    private function getUrl($endpoint, $format)
    {
        Assert::oneOf($format, ['oauth', 'rpc', 'upload', 'download']);

        switch ($format) {
            case 'oauth':
                return self::API_URL . $endpoint;

            case 'rpc':
                return self::API_URL . self::API_VERSION_URL . $endpoint;

            default:
                return self::API_CONTENT_URL . self::API_VERSION_URL . $endpoint;
        }
    }

    /**
     * Builds the options for the request.
     *
     * @param string $endpoint The endpoint to call
     * @param array  $args     The arguments for the request
     * @param string $format   The endpoint format
     *
     * @return RequestInterface The HTTP request
     */
    private function buildRequest($endpoint, array &$args, $format)
    {
        $url = $this->getUrl($endpoint, $format);

        $request = $this->createRequestFactory()
            ->createRequest('POST', $url)
        ;

        if ($format !== 'oauth') {
            $request = new AuthorizationRequest($request);
            $request = $request->withOAuthToken($this->getTokens()['access_token']);
        }

        $streamFactory = new StreamFactory();

        switch ($format) {
            case 'oauth':
                $request = new AuthorizationRequest(new FormRequest($request));
                $request = $request
                    ->withBasicAuth(BasicAuthCredentials::fromUsernameAndPassword($this->oauthAppKey, $this->oauthAppSecret))
                    ->withFormParams($args, $streamFactory)
                    ->withHeader('Accept', 'application/json')
                ;
                break;

            case 'rpc':
                $request = new JsonRequest($request);
                $request = $request
                    ->withJsonData($args ?: null, $streamFactory)
                    ->withHeader('Accept', 'application/json')
                ;
                break;

            case 'upload':
                if (isset($args['contents'])) {
                    $stream = $streamFactory->createStream($args['contents']);
                    $request = $request->withBody($stream);
                    unset($args['contents']);
                }

                $request = $request
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Dropbox-API-Arg', json_encode($args, JSON_FORCE_OBJECT))
                ;
                break;

            case 'download':
                if (isset($args['bytes'])) {
                    $request = $request
                        ->withHeader('Range', 'bytes=' . $args['bytes'])
                    ;
                    unset($args['bytes']);
                }

                $request = $request
                    ->withHeader('Content-Type', 'text/plain')
                    ->withHeader('Accept', 'application/octet-stream')
                    ->withHeader('Dropbox-API-Arg', json_encode($args, JSON_FORCE_OBJECT))
                ;
                break;
        }

        return $request;
    }

    /**
     * Handle request exception.
     *
     * Called for 4xx responses.
     *
     * @param ResponseInterface $response The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception         If the error cannot be handled
     * @throws BackWPup_Destination_Dropbox_API_Request_Exception For endpoint-specific errors
     *                                                            to be passed up the chain
     */
    private function handleRequestException(ResponseInterface $response)
    {
        switch ($response->getStatusCode()) {
            case 400:
            case 401:
            case 403:
            case 409:
            case 429:
                $callback = [$this, 'handle' . $response->getStatusCode() . 'Error'];
                call_user_func($callback, $response);
                break;

            default:
                throw new BackWPup_Destination_Dropbox_API_Exception(
                    sprintf(
                        __(
                            '(%1$s) An unknown error has occurred. Response from server: %2$s',
                            'backwpup'
                        ),
                        $response->getStatusCode(),
                        $response->getBody()->getContents()
                    )
                );
        }
    }

    /**
     * Handle server exception.
     *
     * Called for 5xx responses.
     *
     * @param ResponseInterface $response The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     */
    protected function handleServerException(ResponseInterface $response)
    {
        throw new BackWPup_Destination_Dropbox_API_Exception(
            sprintf(
                __(
                    '(%1$d) An unexpected server error was encountered. Response from server: %2$s',
                    'backwpup'
                ),
                $response->getStatusCode(),
                $response->getBody()->getContents()
            )
        );
    }

    /**
     * Handle 400 response error.
     *
     * @param \Psr\Http\Message\ResponseInterface The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     */
    protected function handle400Error(ResponseInterface $response)
    {
        throw new BackWPup_Destination_Dropbox_API_Exception(
            sprintf(
                __(
                    '(400) Bad input parameter. Response from server: %s',
                    'backwpup'
                ),
                $response->getBody()->getContents()
            )
        );
    }

    /**
     * Handle 401 response error.
     *
     * @param \Psr\Http\Message\ResponseInterface The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception If token is invalid
     */
    protected function handle401Error(ResponseInterface $response)
    {
        $error = json_decode($response->getBody()->getContents(), true);
        if ($error['error']['.tag'] === 'expired_access_token') {
            $this->refresh($this->oauthToken['refresh_token']);
        } else {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                sprintf(
                    __(
                        '(401) Bad or expired token. Response from server: %s',
                        'backwpup'
                    ),
                    $error['error']['.tag']
                )
            );
        }
    }

    /**
     * Handle 403 response error.
     *
     * @param \Psr\Http\Message\ResponseInterface The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception
     */
    protected function handle403Error(ResponseInterface $response)
    {
        $error = json_decode($response->getBody(), true);

        if ($error['error']['.tag'] === 'invalid_account_type') {
            // InvalidAccountTypeError
            if ($error['error']['invalid_account_type']['.tag'] === 'endpoint') {
                throw new BackWPup_Destination_Dropbox_API_Exception(
                    __(
                        '(403) You do not have permission to access this endpoint.',
                        'backwpup'
                    )
                );
            }
            if ($error['error']['invalid_account_type']['.tag'] === 'feature') {
                throw new BackWPup_Destination_Dropbox_API_Exception(
                    __(
                        '(403) You do not have permission to access this feature.',
                        'backwpup'
                    )
                );
            }
        }

        // Catch all
        throw new BackWPup_Destination_Dropbox_API_Exception(
            sprintf(
                __(
                    '(403) You do not have permission to access this resource. Response from server: %s',
                    'backwpup'
                ),
                $error['error_summary']
            )
        );
    }

    /**
     * Handle 409 response error.
     *
     * @param \Psr\Http\Message\ResponseInterface The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Request_Exception
     */
    protected function handle409Error(ResponseInterface $response)
    {
        $error = json_decode($response->getBody(), true);

        throw new BackWPup_Destination_Dropbox_API_Request_Exception(
            sprintf(
                __(
                    '(409) Endpoint-specific error. Response from server: %s',
                    'backwpup'
                ),
                $error['error_summary']
            ),
            $response->getStatusCode(),
            null,
            $error['error']
        );
    }

    /**
     * Handle 429 response error.
     *
     * This error is encountered when requests are being rate limited.
     *
     * @param \Psr\Http\Message\ResponseInterface The returned response
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception If unable to detect time to wait
     */
    protected function handle429Error(ResponseInterface $response)
    {
        if (!$response->hasHeader('Retry-After')) {
            throw new BackWPup_Destination_Dropbox_API_Exception(
                __(
                    '(429) Requests are being rate limited. Please try again later.',
                    'backwpup'
                )
            );
        }
        sleep(intval($response->getHeaderLine('Retry-After')));
    }

    /**
     * Creates a new HTTP client.
     *
     * @return ClientInterface
     */
    protected function createClient()
    {
        $options = [
            'timeout' => 60,
        ];

        if (empty($this->getCaBundle())) {
            $options['sslverify'] = false;
        } else {
            $options += [
                'sslverify' => true,
                'sslcertificates' => $this->getCaBundle(),
            ];
        }

        if (!empty($this->getUserAgent())) {
            $options['user-agent'] = $this->getUserAgent();
        }

        return new WpHttpClient(new ResponseFactory(), new StreamFactory(), $options);
    }

    /**
     * Creates a request factory for creating requests.
     *
     * @return RequestFactory
     */
    protected function createRequestFactory()
    {
        return new RequestFactory();
    }

    /**
     * Formats a path to be valid for Dropbox.
     *
     * @param string $path
     *
     * @return string The formatted path
     */
    private function formatPath($path)
    {
        if (!empty($path) && substr($path, 0, 1) !== '/') {
            $path = '/' . rtrim($path, '/');
        } elseif ($path === '/') {
            $path = '';
        }

        return $path;
    }

    // Error Handlers

    private function handleFilesDeleteError($error)
    {
        switch ($error['.tag']) {
            case 'path_lookup':
                $this->handleFilesLookupError($error['path_lookup']);
                break;

            case 'path_write':
                $this->handleFilesWriteError($error['path_write']);
                break;

            case 'other':
                trigger_error('Could not delete file.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesGetMetadataError($error)
    {
        switch ($error['.tag']) {
            case 'path':
                $this->handleFilesLookupError($error['path']);
                break;

            case 'other':
                trigger_error('Cannot look up file metadata.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesGetTemporaryLinkError($error)
    {
        switch ($error['.tag']) {
            case 'path':
                $this->handleFilesLookupError($error['path']);
                break;

            case 'other':
                trigger_error('Cannot get temporary link.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesListFolderError($error)
    {
        switch ($error['.tag']) {
            case 'path':
                $this->handleFilesLookupError($error['path']);
                break;

            case 'other':
                trigger_error('Cannot list files in folder.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesListFolderContinueError($error)
    {
        switch ($error['.tag']) {
            case 'path':
                $this->handleFilesLookupError($error['path']);
                break;

            case 'reset':
                trigger_error('This cursor has been invalidated.', E_USER_WARNING);
                break;

            case 'other':
                trigger_error('Cannot list files in folder.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesLookupError($error)
    {
        switch ($error['.tag']) {
            case 'malformed_path':
                trigger_error('The path was malformed.', E_USER_WARNING);
                break;

            case 'not_found':
                trigger_error('File could not be found.', E_USER_WARNING);
                break;

            case 'not_file':
                trigger_error('That is not a file.', E_USER_WARNING);
                break;

            case 'not_folder':
                trigger_error('That is not a folder.', E_USER_WARNING);
                break;

            case 'restricted_content':
                trigger_error('This content is restricted.', E_USER_WARNING);
                break;

            case 'invalid_path_root':
                trigger_error('Path root is invalid.', E_USER_WARNING);
                break;

            case 'other':
                trigger_error('File could not be found.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesUploadSessionFinishError($error)
    {
        switch ($error['.tag']) {
            case 'lookup_failed':
                $this->handleFilesUploadSessionLookupError(
                    $error['lookup_failed']
                );
                break;

            case 'path':
                $this->handleFilesWriteError($error['path']);
                break;

            case 'too_many_shared_folder_targets':
                trigger_error('Too many shared folder targets.', E_USER_WARNING);
                break;

            case 'other':
                trigger_error('The file could not be uploaded.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesUploadSessionLookupError($error)
    {
        switch ($error['.tag']) {
            case 'not_found':
                trigger_error('Session not found.', E_USER_WARNING);
                break;

            case 'incorrect_offset':
                trigger_error(
                    'Incorrect offset given. Correct offset is ' .
                    intval($error['correct_offset']) . '.',
                    E_USER_WARNING
                );
                break;

            case 'closed':
                trigger_error(
                    'This session has been closed already.',
                    E_USER_WARNING
                );
                break;

            case 'not_closed':
                trigger_error('This session is not closed.', E_USER_WARNING);
                break;

            case 'other':
                trigger_error(
                    'Could not look up the file session.',
                    E_USER_WARNING
                );
                break;
        }
    }

    private function handleFilesUploadError($error)
    {
        switch ($error['.tag']) {
            case 'path':
                $this->handleFilesUploadWriteFailed($error['path']);
                break;

            case 'other':
                trigger_error('There was an unknown error when uploading the file.', E_USER_WARNING);
                break;
        }
    }

    private function handleFilesUploadWriteFailed($error)
    {
        $this->handleFilesWriteError($error['reason']);
    }

    private function handleFilesWriteError($error)
    {
        $message = '';

        // Type of error
        switch ($error['.tag']) {
            case 'malformed_path':
                $message = 'The path was malformed.';
                break;

            case 'conflict':
                $message = 'Cannot write to the target path due to conflict.';
                break;

            case 'no_write_permission':
                $message = 'You do not have permission to save to this location.';
                break;

            case 'insufficient_space':
                $message = 'You do not have enough space in your Dropbox.';
                break;

            case 'disallowed_name':
                $message = 'The given name is disallowed by Dropbox.';
                break;

            case 'team_folder':
                $message = 'Unable to modify team folders.';
                break;

            case 'other':
                $message = 'There was an unknown error when uploading the file.';
                break;
        }

        trigger_error($message, E_USER_WARNING); // phpcs:ignore
    }
}
