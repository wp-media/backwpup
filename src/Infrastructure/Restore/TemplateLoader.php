<?php
/**
 * Template.
 *
 * @author    Guido Scialfa <dev@guidoscialfa.com>
 * @copyright Copyright (c) 2017, Guido Scialfa
 * @license   GNU General Public License, version 2
 *
 * Copyright (C) 2017 Guido Scialfa <dev@guidoscialfa.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use function add_action;
use function backwpup_template;
use Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader\DownloaderFactory;
use Inpsyde\Restore\Api\Module\Registry;
use Inpsyde\Restore\Api\Module\Upload\BackupUpload;
use Inpsyde\Restore\Log\LevelExtractorFactory;
use Pimple\Container;
use SplFileObject;
use function untrailingslashit;
use function wp_create_nonce;

/**
 * Class Template.
 *
 * @psalm-type Item=array{bind?: object, view?: string}
 */
final class TemplateLoader
{
    /**
     * Step.
     *
     * @var int The current step to load
     */
    private $step = 1;

    /**
     * Skip.
     *
     * If the current step must be skipped
     *
     * @var bool True to skip, false otherwise
     */
    private $skip = false;

    /**
     * Default step.
     *
     * @var string The default step if none can be set
     */
    private $defaultStepView = 'step1';

    /**
     * Step List.
     *
     * @var array<string, Item> Step's list
     */
    private $list = [];

    /**
     * Container.
     *
     * @var Container The container of the instances
     */
    private $container;

    /**
     * TemplateLoader constructor.
     *
     * @param Container $container the container of the instances
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        // Default to one. The upload step.
        // phpcs:disable
        $this->step = (int) (
            isset($_GET['step'])
            ? filter_var($_GET['step'], FILTER_SANITIZE_NUMBER_INT)
            : $this->step
        );
        // phpcs:enable
    }

    /**
     * Load.
     *
     * @return $this
     */
    public function load(): self
    {
        add_action('backwpup_restore_upload_content', [$this, 'template']); // @phpstan-ignore-line
        add_action(
            'backwpup_restore_before_upload_content',
            function (): void {
                $this->template('dashboard');
            }
        );
        add_action(
            'backwpup_restore_before_main_content',
            function (): void {
                $this->template('top');
            }
        );
        add_action(
            'backwpup_restore_main_content',
            function (): void {
                $this->template('action');
            }
        );

        return $this;
    }

    /**
     * Template.
     *
     * @param string $which which Template to load
     *
     * @return TemplateLoader $this The instance for concatenation
     */
    public function template(string $which = ''): self
    {
        $this->setContext();

        // Retrieve the item for which to load the template.
        $item = $this->item($which);

        // Prevent infinite loop or max call stack.
        if ($which && !$item) {
            return $this;
        }

        // If item found, load it.
        if (!empty($item) && isset($item['bind'], $item['view'])) {
            backwpup_template($item['bind'], $item['view']);

            return $this;
        }

        backwpup_template($this, '/restore/main.php');

        return $this;
    }

    /**
     * Container.
     *
     * @param string|null $what what container to retrieve
     *
     * @return mixed|null The instance request or empty if the instance doesn't exist
     */
    public function container(?string $what = '')
    {
        if (!$what) {
            return $this->container;
        }

        return $this->container[$what] ?? null;
    }

    /**
     * Set Context for step.
     */
    private function setContext(): void
    {
        // Get the base views path.
        $path = untrailingslashit(\BackWPup::get_plugin_data('plugindir')) . '/views/restore';

        // Top template.
        $this->list['top'] = $this->setStep('top', $path);
        // Action Template.
        $this->list['action'] = $this->setStep('action', $path);
        // Dashboard Template.
        $this->list['dashboard'] = $this->setDashboard();
    }

    /**
     * Set Step.
     *
     * @param string $portion the portion of the view for which set the data
     * @param string $path    the base path where looking for the template view
     *
     * @return array{bind: object, view: string} The data needed by the view
     */
    private function setStep(string $portion, string $path): array
    {
        // Action Template
        $item = [
            'bind' => $this,
            'view' => "{$path}/steps/step{$this->defaultStepView}_{$portion}.php",
        ];

        if (!$this->skip && file_exists("{$path}/steps/step{$this->step}_{$portion}.php")) {
            $item['bind'] = $this->createBindFromStep($this->step);
            $item['view'] = "/restore/steps/step{$this->step}_{$portion}.php";
        }

        return $item;
    }

    /**
     * Set Dashboard.
     *
     * @return array The data needed by the view
     * @psalm-return Item
     */
    private function setDashboard(): array
    {
        try {
            // Download Url view.
            $downloaderFactory = new DownloaderFactory();
            $downloader = $downloaderFactory->create();
        } catch (\RuntimeException $exc) {
            return [];
        }

        return [
            'bind' => (object) [
                'downloader' => $downloader,
            ],
            'view' => '/restore/dashboard.php',
        ];
    }

    /**
     * Create bind object for the view.
     *
     * @param int $step the step for which create the bind object
     *
     * @return object The bind object
     */
    private function createBindFromStep(int $step): object
    {
        $bind = [];

        switch ($step) {
            case 2:
                /** @var BackupUpload $backupUpload */
                $backupUpload = $this->container('backup_upload');
                /** @var Registry $registry */
                $registry = $this->container('registry');

                $bind = [
                    'backupUpload' => $backupUpload,
                    'upload_is_archive' => empty($registry->uploaded_file) ? null : $backupUpload::upload_is_archive(
                        $registry->uploaded_file
                    ),
                    'upload_is_sql' => empty($registry->uploaded_file) ? null : $backupUpload::upload_is_sql(
                        $registry->uploaded_file
                    ),
                ];
                break;

            case 3:
                // Only go to migrate step if in pro version
                // Note that this is hard-coded: if new steps are inserted, please modify as necessary
                $bind = [
                    'migrate_allowed' => \BackWPup::is_pro(),
                ];
                break;

            case 6:
                /** @var LevelExtractorFactory $levelExtractorFactory */
                $levelExtractorFactory = $this->container('level_extractor_factory');

                $levelExtractor = $levelExtractorFactory->create();
                $logFile = new SplFileObject((string) $this->container('log_file'));
                $bind['errors'] = $levelExtractor->extractError($logFile);
                break;

            default:
                break;
        }

        // Set nonce to use within the template.
        $bind['nonce'] = wp_create_nonce('backwpup_action_nonce');

        return (object) $bind;
    }

    /**
     * Retrieve the item from the list.
     *
     * @param string $item the item to retrieve from the list
     *
     * @return array The item found or empty array if the requested item doesn't exist
     * @psalm-return Item
     */
    private function item(string $item): array
    {
        return $this->list[$item] ?? [];
    }
}
