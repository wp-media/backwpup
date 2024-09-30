<?php

declare(strict_types=1);

/*
 * This file is part of the Inpsyde BackWpUp package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore;

use Inpsyde\Restore\Api\Controller\DecryptController;
use Inpsyde\Restore\Api\Controller\JobController;
use Inpsyde\Restore\Api\Controller\LanguageController;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseConnectionException;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;
use Inpsyde\Restore\Api\Module\Registry;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Class Ajax.
 */
class AjaxHandler
{
    /**
     * @var string
     */
    public const EVENT_SOURCE_CONTEXT = 'event_source';

    /**
     * Ajax Hooks.
     *
     * @var string[] list of the ajax hooks for the actions to perform
     */
    private static $hooks = [
        'download',
        'decompress_upload',
        'decrypt',
        'get_strategy',
        'switch_language',
        'save_strategy',
        'db_test',
        'restore_db',
        'restore_dir',
        'upload',
        'fetch_url',
        'save_migration',
    ];

    /**
     * @var JobController
     */
    private $jobController;

    /**
     * @var LanguageController
     */
    private $languageController;

    /**
     * @var DecryptController
     */
    private $decryptController;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventSource
     */
    private $eventSource;

    /**
     * @var string
     */
    private $logFilePath;

    /**
     * AjaxHandler constructor.
     */
    public function __construct(
        JobController $jobController,
        LanguageController $languageController,
        DecryptController $decryptController,
        Registry $registry,
        LoggerInterface $logger,
        EventSource $eventSource,
        string $logFilePath
    ) {
        $this->jobController = $jobController;
        $this->languageController = $languageController;
        $this->decryptController = $decryptController;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->eventSource = $eventSource;
        $this->logFilePath = $logFilePath;
    }

    /**
     * Load Hooks.
     *
     * @return $this For concatenation
     */
    public function load(): self
    {
        // Not in standalone.
        if (!\function_exists('add_action')) {
            return $this;
        }

        foreach (self::$hooks as $hook) {
            add_action(sprintf('wp_ajax_%s', $hook), function (): void {
                $this->dispatch();
            });
        }

        return $this;
    }

    /**
     * Dispatch Request.
     */
    public function dispatch(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        try {
            $response = '';
            [$controller, $action] = $this->prepare();

            switch (strtolower($controller)) {
                case 'job':
                    $response = $this->handle_job($action);
                    break;

                case 'language':
                    $response = $this->handle_language($action);
                    break;

                case 'decrypt':
                    $response = $this->handle_decrypt();
                    break;

                default:
                    break;
            }

            // Clean the registry if we have done.
            if ($this->registry->is_restore_finished()) {
                $this->registry->reset_registry();
            }

            // Sent the response to the client.
            $this->handle_json_response($response);
        } catch (\Throwable $e) {
            $this->handle_catch_exception($e);
        }
    }

    /**
     * Handle job action.
     *
     * @param string $action The action to handle
     *
     * @return string|bool|string[] The output to send back to the client
     */
    public function handle_job(string $action)
    {
        $response = '';

        if (method_exists($this, $action)) {
            $response = $this->{$action}();
        } elseif (method_exists($this->jobController, $action)) {
            $response = $this->jobController->{$action}();
        }

        return $response;
    }

    /**
     * Handle Language Request.
     *
     * @param string $action The action to perform
     *
     * @return bool $output the response of the controller
     */
    public function handle_language(string $action): bool
    {
        /** @var callable(string): bool $callback */
        $callback = [$this->languageController, $action];
        Assert::isCallable($callback);

        $locale = filter_input(INPUT_POST, 'locale',FILTER_SANITIZE_ADD_SLASHES);

        if (is_string($locale)) {
            return $callback($locale);
        }

        return false;
    }

    /**
     * Handle the decryption.
     *
     * @throws DecryptException
     */
    public function handle_decrypt(): string
    {
        $encrypted_file = '';

        if (!isset($_REQUEST['decryption_key'])) {
            throw new DecryptException(
                __(
                    "You tried to decrypt a backup but you didn't sent any decryption key",
                    'backwpup'
                )
            );
        }

        $key = $_REQUEST['decryption_key'];

        if (isset($_REQUEST['encrypted_file_path'])) {
            $encrypted_file = $_REQUEST['encrypted_file_path'];
        }

        if (!$encrypted_file && $this->registry->uploaded_file) {
            $encrypted_file = $this->registry->uploaded_file;
        }

        if (!$encrypted_file) {
            throw new DecryptException(
                __('Backup cannot be decrypted, file has not been found.', 'backwpup')
            );
        }

        $this->decryptController->decrypt($key, $encrypted_file);

        return __('Backup decrypted successfully.', 'backwpup');
    }

    /**
     * Save strategy.
     *
     * @throws InvalidArgumentException
     */
    public function save_strategy_action(): bool
    {
        $strategy = filter_input(INPUT_POST, 'strategy', FILTER_SANITIZE_ADD_SLASHES);

        if (!is_string($strategy) || $strategy === '') {
            throw new InvalidArgumentException(
                __('You have to select one strategy.', 'backwpup')
            );
        }

        $this->jobController->save_strategy_action($strategy);

        return true;
    }

    /**
     * Restore Database.
     */
    public function restore_db_action(): string
    {
        $this->eventSource
            ->increaseResources()
            ->setHeaders()
        ;

        return $this->jobController->restore_db_action();
    }

    /**
     * Restore Directory.
     */
    public function restore_dir_action(): string
    {
        $this->eventSource
            ->increaseResources()
            ->setHeaders()
        ;

        return $this
            ->jobController
            ->restore_dir_action()
        ;
    }

    /**
     * Db Test.
     *
     * Test the connection to the db
     *
     * @throws DatabaseConnectionException
     *
     * @return string[] The response text of the test
     */
    public function db_test_action(): array
    {
        $response = [];

        // Sanitize to remove slashes added by WordPress
        $db_settings = filter_input(INPUT_POST, 'db_settings', FILTER_CALLBACK, [
            'options' => 'backwpup_clean_json_from_request',
        ]);

        if (!is_string($db_settings)) {
            return $response;
        }

        if ($db_settings !== '') {
            $this->jobController->db_test_action($db_settings);
            $response['message'] = __('Connection to Database Successful.', 'backwpup');
            $response['charset'] = $this->registry->dbcharset ?: '';
        }

        return $response;
    }

    /**
     * Download Action.
     *
     * AJAX method to trigger the download.
     *
     * @throws \Exception if something goes wrong with the download
     */
    public function download_action(): bool
    {
        // Clean the restore.log.
        file_put_contents($this->logFilePath, '', LOCK_EX); // phpcs:ignore

        $this->eventSource->setHeaders();

        // phpcs:disable
        $source_file_path = $_GET['source_file_path'];
        $service = $_GET['service'];
        $job_id = filter_var($_GET['jobid'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $local_file_path = $_GET['local_file_path'];
        // phpcs:enable

        $this->jobController->download_action(
            $job_id,
            $service,
            $source_file_path,
            $local_file_path
        );

        return true;
    }

    /**
     * Decompress Upload Action.
     *
     * AJAX method to trigger the decompression and save path to registry.
     *
     * @throws \Exception if something goes wrong with the decompression
     */
    public function decompress_upload_action(): bool
    {
        $this->eventSource
            ->increaseResources()
            ->setHeaders()
        ;

        // We may pass or not a file path, depends from where the decompression action is started.
        // From the backups page we have a file path but when we upload it we don't.
        // phpcs:disable
        $file_path = isset($_GET['file_path'])
            ? $_GET['file_path'] ?? ''
            : '';
        // phpcs:enable

        $this->jobController->decompress_upload_action($file_path);

        return true;
    }

    /**
     * Save migration settings.
     *
     * @throws InvalidArgumentException
     */
    public function save_migration_action(): bool
    {
        $old_url = filter_input(INPUT_POST, 'old_url');
        if (!is_string($old_url) || $old_url === '') {
            throw new InvalidArgumentException(
                __('Please specify the old URL.', 'backwpup')
            );
        }

        $old_url = filter_var($old_url, FILTER_VALIDATE_URL);
        if ($old_url === false || $old_url === '') {
            throw new \InvalidArgumentException(
                __('Old URL is not a valid URL.', 'backwpup')
            );
        }

        $new_url = filter_input(INPUT_POST, 'new_url');
        if (!is_string($new_url) || $new_url === '') {
            throw new InvalidArgumentException(
                __('Please specify the new URL.', 'backwpup')
            );
        }

        $new_url = filter_var($new_url, FILTER_VALIDATE_URL);
        if ($new_url === false || $new_url === '') {
            throw new \InvalidArgumentException(
                __('New URL is not a valid URL.', 'backwpup')
            );
        }

        if ($old_url === $new_url) {
            throw new InvalidArgumentException(
                __('The old and new URLs cannot match.', 'backwpup')
            );
        }

        $this->jobController->save_migration_action($old_url, $new_url);

        return true;
    }

    /**
     * Verify Request.
     *
     * @return bool true if nonce is verified, false if not set.
     *              Will die if nonce isn't a valid one.
     */
    protected function verify_request(): bool
    {
        // Retrieve nonce value and return silently if not set, but if it's set, die if not a valid one.
        // phpcs:ignore
        if (!isset($_REQUEST['backwpup_action_nonce'])) {
            return false;
        }

        return check_ajax_referer('backwpup_action_nonce', 'backwpup_action_nonce')
            && current_user_can('backwpup_restore');
    }

    /**
     * Handle Response.
     *
     * @param string|bool|string[] $response The response data to send back to the client
     */
    protected function handle_json_response($response): void
    {
        $context = $_REQUEST['context']
            ?? '';

        $data = [];

        $data['message'] = \is_array($response) ? $response['message'] ?? '' : (string) $response;

        // EventSource Request.
        if ($context === self::EVENT_SOURCE_CONTEXT) {
            // Set the state to done.
            $data['state'] = 'done';

            $this->eventSource->response('message', $data);

            return;
        }

        wp_send_json_success($data, 200);
    }

    /**
     * Prepare request.
     *
     * @return string[] $array An array containing the controller and the action requested
     */
    private function prepare(): array
    {
        $output = [
            'controller' => 'Job',
            'action' => 'index',
        ];

        foreach (array_keys($output) as $key) {
            $param = filter_input(INPUT_POST, $key) ?: filter_input(INPUT_GET, $key);

            if (is_string($param) && $param !== '') {
                $output[$key] = $param;
            }
        }

        return [
            ucfirst($output['controller']),
            $output['action'] . '_action',
        ];
    }

    /**
     * Handle Catch Exception.
     *
     * @param \Throwable $e The exception to handle
     */
    private function handle_catch_exception(\Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString(),
            'GET' => $_GET,
            'POST' => $_POST,
        ];
        $this->logger->alert($e->getMessage(), $context);

        if (
            !$e instanceof DecryptException
            && !$e instanceof InvalidArgumentException
            && !$e instanceof DatabaseConnectionException
        ) {
            $this->registry->reset_registry();
        }

        $context = filter_input(INPUT_POST, 'context')
            ?: filter_input(INPUT_GET, 'context');
        if ($context === self::EVENT_SOURCE_CONTEXT) {
            $jsonData = wp_json_encode([
                'state' => 'error',
                'message' => $e->getMessage(),
            ]);
            echo "event: log\n";
            echo "data: {$jsonData}\n\n";
            flush();

            return;
        }

        // Set a feedback to the user.
        wp_send_json_error(
            [
                'state' => 'error',
                'message' => $e->getMessage(),
            ]
        );
    }
}
