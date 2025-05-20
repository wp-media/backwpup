<?php

namespace Liborm85\ComposerVendorCleaner;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;

class Package
{

    /**
     * @var PackageInterface
     */
    private $package;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var bool
     */
    private $isPackageChanged;

    /**
     * @param PackageInterface $package
     * @param InstallationManager $installationManager
     * @param bool $isPackageChanged
     */
    public function __construct($package, $installationManager, $isPackageChanged)
    {
        $this->package = $package;
        $this->installationManager = $installationManager;
        $this->isPackageChanged = $isPackageChanged;
    }

    /**
     * @return string
     */
    public function getPrettyName()
    {
        return $this->package->getPrettyName();
    }

    /**
     * @return ?string
     */
    public function getInstallPath()
    {
        return $this->installationManager->getInstallPath($this->package);
    }

    /**
     * @return bool
     */
    public function isPackageChanged()
    {
        return $this->isPackageChanged;
    }
}
