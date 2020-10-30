<?php

namespace Saparot\ComposerTools;

use Saparot\ComposerTools\File\Composer;

class ComposerPackage {

    /** @var Composer */
    private $composer;

    /**
     * @param string $folder
     *
     * @return static
     * @throws ComposerToolsException
     */
    static function createByPath (string $folder): self {
        if (!file_exists($folder)) {
            throw new ComposerToolsException("folder '{$folder}' doesnt exists");
        }
        $realPath = realpath($folder);
        if ($realPath === false) {
            throw new ComposerToolsException("failed to get real path for folder '{$folder}'");
        }
        $composerFile = sprintf("%s/composer.json", $realPath);
        if (!file_exists($composerFile)) {
            throw new ComposerToolsException("composer file 'composer.json' not found in '{$folder}'");
        }
        $composer = new Composer($composerFile);

        return new static($composer);
    }

    /**
     * @param Composer $composer
     *
     * @return static
     */
    static function createByComposer (Composer $composer): self {
        return new static($composer);
    }

    /**
     * ComposerPackage constructor.
     *
     * @param Composer $composer
     */
    private function __construct (Composer $composer) {
        $this->composer = $composer;
    }

    /**
     * @return Composer
     */
    function getComposer (): Composer {
        return $this->composer;
    }

    /**
     * @return string
     * @throws ComposerToolsException
     */
    function getPath (): string {
        return $this->composer->getRealPath();
    }

    /**
     * @return string
     * @throws ComposerToolsException
     */
    function getPathVendor (): string {
        return sprintf('%s/vendor', $this->getPath());
    }

    /**
     * @param ComposerPackage $composerPackage
     * @param string $detail
     *
     * @return bool
     * @throws ComposerToolsException
     */
    function isPackageLinked (ComposerPackage $composerPackage, ?string &$detail = ''): bool {
        $pathInstallation = $this->getPathInstallation($composerPackage);
        $realPathInstallation = realpath($pathInstallation);
        if (file_exists($pathInstallation) && $realPathInstallation === false) {
            throw new ComposerToolsException("failed to resolve real path for path {$pathInstallation}");
        }
        if ($this->isSymLinked($composerPackage)) {
            $detail = 'linked';
            if ($realPathInstallation === $composerPackage->getPath()) {
                return true;
            }
            //here its symlink but mismatch
            $detail = 'mismatch';
        }
        $detail = 'not';

        return false;
    }

    /**
     * @param ComposerPackage $composerPackage
     *
     * @return bool
     * @throws ComposerToolsException
     */
    private function isSymLinked (ComposerPackage $composerPackage): bool {
        $pathInstallation = $this->getPathInstallation($composerPackage);

        return is_link($pathInstallation) || readlink($pathInstallation) !== $pathInstallation;
    }

    /**
     * @param ComposerPackage $composerPackage
     *
     * @return string
     * @throws ComposerToolsException
     */
    function getPathInstallation (ComposerPackage $composerPackage) {
        return sprintf('%s/%s', $this->getPathVendor(), $composerPackage->getInstallationSubPath());
    }

    /**
     * @return string
     * @throws ComposerToolsException
     */
    private function getInstallationSubPath (): string {
        return trim(strtolower($this->composer->getName()));
    }
}
