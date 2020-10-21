<?php

namespace Saparot\ComposerTools\File;

use Saparot\ComposerTools\ComposerToolsException;

class Composer extends Json {

    const KEY_VERSION = 'version';
    const KEY_REQUIRE = 'require';

    /**
     * @param $package
     *
     * @return string|null
     * @throws ComposerToolsException
     */
    function getRequirePackageVersionString ($package): ?string {
        return $this->hasRequirePackage($package) ? $this->data[self::KEY_REQUIRE][$package] : null;
    }

    /**
     * @param string $package
     *
     * @return bool
     * @throws ComposerToolsException
     */
    function hasRequirePackage (string $package): bool {
        return $this->hasConfigKey(self::KEY_REQUIRE) ? isset($this->getConfigKey(self::KEY_REQUIRE)[$package]) : false;
    }

    /**
     * @param string $package
     * @param string $version
     *
     * @return $this
     * @throws ComposerToolsException
     */
    function setRequirePackageVersion (string $package, string $version): self {
        $this->load(false);
        $this->data[self::KEY_REQUIRE] = $this->data[self::KEY_REQUIRE] ?? [];
        $this->data[self::KEY_REQUIRE][$package] = $version;

        return $this;
    }

    /**
     * @return string|null
     * @throws ComposerToolsException
     */
    function getVersion (): ?string {
        $data = $this->getData();

        return isset($data[self::KEY_VERSION]) ? $data['version'] : null;
    }

    /**
     * @param string $version
     *
     * @return $this
     */
    function setVersion (string $version): self {
        $this->set('version', $version);

        return $this;
    }

    /**
     * @return array
     * @throws ComposerToolsException
     */
    function getRepositories (): array {
        return $this->getConfigKey('repositories', []);
    }

    /**
     * @param array $repos
     *
     * @return $this
     */
    function setRepositories (array $repos): self {
        $this->set('repositories', $repos);

        return $this;
    }
}
