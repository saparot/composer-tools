<?php

namespace Saparot\ComposerTools\Action;

use Saparot\ComposerTools\ComposerPackage;
use Saparot\ComposerTools\ComposerToolsException;

class VendorUpdate {

    /**
     * @var ComposerPackage
     */
    private $composerPackage;

    /**
     * VendorUpdate constructor.
     *
     * @param ComposerPackage $composerPackage
     */
    public function __construct (ComposerPackage $composerPackage) {
        $this->composerPackage = $composerPackage;
    }

    /**
     * @throws ComposerToolsException
     */
    function updateVendorAll (): void {
         $this->update(null);
    }

    /**
     * @param ComposerPackage $packageComposer
     *
     * @throws ComposerToolsException
     */
    function updateVendorPackage (ComposerPackage $packageComposer): void {
        $this->update($packageComposer->getComposer()->getName());
    }

    function updateVendorPackageByName (string $packageName): void {
        if (empty(trim($packageName))) {
            throw new ComposerToolsException("package name is required");
        }
        $this->update($packageName);
    }

    /**
     * @param string|null $package
     *
     * @throws ComposerToolsException
     */
    private function update (?string $package): void {
        $current = getcwd();
        $path = $this->composerPackage->getPath();
        if (!file_exists($path)) {
            throw new ComposerToolsException("path '{$this->composerPackage->getPath()}' doesnt exists");
        }

        chdir($path);
        system(sprintf('composer update %s', $package ?? ''), $exitStatus);
        chdir($current);
        if ($exitStatus !== 0) {
            throw new ComposerToolsException("failed to update composer, exit status $exitStatus");
        }
    }
}
