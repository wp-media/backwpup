<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Infrastructure\Restore;

use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Exception\FileSystemException;

/**
 * Protects the restore working directory (the directory holding the registry
 * file, `uploads/`, the log and `extract/`) against unauthenticated disclosure
 * on Apache and IIS, and against directory listing everywhere else.
 *
 * Self-contained and dependency-free on purpose: this library has no dependency
 * on `backwpup-pro`, so it cannot reuse `BackWPup_File::protect_folder()`.
 *
 * Never writes into `extract_folder` itself: the extracted archive can overwrite
 * (or lack) its own `.htaccess`/`Web.config`/`index.php`, and `RestoreFiles`
 * copies everything under `extract_folder` into `project_root` except a fixed
 * ignore list. Writing a deny-all file there would either be clobbered right
 * before it matters, or be restored into the WordPress root and take the site
 * down. Protection therefore always targets the working directory, and is
 * applied by `Registry::init()` before anything else is written into it.
 */
final class WorkingDirectoryProtector
{
    /**
     * Names of the protection files written into the protected directory.
     *
     * @var string[]
     */
    public const FILES = ['.htaccess', 'Web.config', 'index.php'];

    /**
     * Protect the given directory with deny-all files, unless it is the
     * project root or one of its ancestors.
     *
     * @param string $directory   Absolute path to the directory to protect
     *                            (the restore working directory).
     * @param string $projectRoot Absolute path to the WordPress root, used to
     *                            guard against writing deny-all files into the
     *                            live site. Pass an empty string to skip the
     *                            guard.
     *
     * @throws FileSystemException in case the directory does not exist or a
     *                              protection file cannot be written
     *
     * @return bool True if the directory was protected (or was already
     *              protected), false if the write was skipped because
     *              `$directory` is `$projectRoot` or one of its ancestors.
     */
    public function protect(string $directory, string $projectRoot): bool
    {
        if ($directory === '' || !is_dir($directory)) {
            throw new FileSystemException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    sprintf(
                        __('Restore working directory %s does not exist.', 'backwpup'),
                        $directory
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }

        if ($projectRoot !== '' && $this->isSiteRootOrAncestor($directory, $projectRoot)) {
            return false;
        }

        if (!is_writable($directory)) { // phpcs:ignore
            throw new FileSystemException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    sprintf(
                        __(
                            'Restore working directory %s is not writable, protection files cannot be created.',
                            'backwpup'
                        ),
                        $directory
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }

        foreach (self::FILES as $file) {
            $this->writeIfMissing($directory . DIRECTORY_SEPARATOR . $file, $file);
        }

        return true;
    }

    /**
     * Whether the given directory equals `$projectRoot` or is an ancestor of it.
     */
    private function isSiteRootOrAncestor(string $directory, string $projectRoot): bool
    {
        $directoryNorm = $this->normalizePath($directory);
        $projectRootNorm = $this->normalizePath($projectRoot);

        if ($directoryNorm === $projectRootNorm) {
            return true;
        }

        return strpos(
            $projectRootNorm . DIRECTORY_SEPARATOR,
            $directoryNorm . DIRECTORY_SEPARATOR
        ) === 0;
    }

    /**
     * Normalise a path for comparison. Falls back to trimming trailing
     * separators when `realpath()` cannot resolve the path (e.g. vfsStream
     * streams in tests, or a `$projectRoot` that does not exist on disk).
     */
    private function normalizePath(string $path): string
    {
        $real = realpath($path);

        if ($real !== false) {
            return $real;
        }

        return rtrim($path, '/\\');
    }

    /**
     * Write the protection file if it does not already exist.
     *
     * Never overwrites: a pre-existing file (for example written by the
     * consumer's own `protect_folder()`-equivalent) is left untouched.
     *
     * @throws FileSystemException if the file cannot be written
     */
    private function writeIfMissing(string $path, string $file): void
    {
        if (file_exists($path)) {
            return;
        }

        $written = file_put_contents($path, $this->contentFor($file), LOCK_EX); // phpcs:ignore

        if ($written === false) {
            throw new FileSystemException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    sprintf(
                        __('Impossible to create protection file %s.', 'backwpup'),
                        $path
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }
    }

    /**
     * The content to write for a given protection file name.
     */
    private function contentFor(string $file): string
    {
        switch ($file) {
            case '.htaccess':
                return <<<'HTACCESS'
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>

HTACCESS;

            case 'Web.config':
                return '<configuration>' . PHP_EOL
                    . "\t<system.webServer>" . PHP_EOL
                    . "\t\t<authorization>" . PHP_EOL
                    . "\t\t\t<deny users=\"*\" />" . PHP_EOL
                    . "\t\t</authorization>" . PHP_EOL
                    . "\t</system.webServer>" . PHP_EOL
                    . '</configuration>' . PHP_EOL;

            case 'index.php':
            default:
                return '<?php' . PHP_EOL
                    . "header( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );" . PHP_EOL
                    . "header( 'Status: 404 Not Found' );" . PHP_EOL;
        }
    }
}
