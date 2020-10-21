<?php

namespace Saparot\ComposerTools;

use Saparot\ComposerTools\File\Composer;

class PackageConfig {

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $composerFile;

    /**
     * @var Composer
     */
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

        return new static($folder);
    }

    /**
     * PackageConfig constructor.
     *
     * @param string $folder
     *
     * @throws ComposerToolsException
     */
    private function __construct (string $folder) {
        $this->folder = $folder;
        $this->composerFile = sprintf("%s/composer.json", $this->getRealPath());
        $this->composer = new Composer($this->composerFile);
    }

    /**
     * get the folder name as assigned
     *
     * @return string
     */
    function getFolder (): string {
        return $this->folder;
    }

    /**
     * get the composer object for this package
     *
     * @return Composer
     */
    function getComposer (): Composer {
        return $this->composer;
    }

    /**
     * get the real path for this package
     *
     * @return string
     * @throws ComposerToolsException
     */
    function getRealPath (): string {
        $realPath = realpath($this->folder);
        if ($realPath === false) {
            throw new ComposerToolsException("failed to retrieve realpath for {$this->folder}");
        }

        return $realPath;
    }
}
