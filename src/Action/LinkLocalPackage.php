<?php

namespace Saparot\ComposerTools\Action;

use Exception;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;
use Saparot\ComposerTools\ComposerPackage;
use Saparot\ComposerTools\ComposerToolsException;
use Saparot\NetworkRetriever\NetworkRetrieverException;

class LinkLocalPackage {

    /**
     * @var ComposerPackage
     */
    private $composerPackageWhere;

    /**
     * @var ComposerPackage
     */
    private $composerPackageWhich;

    /**
     * @var string|null
     */
    private $whichRepositoryUrl;

    /**
     * @param ComposerPackage $composerPackageWhere the composer package where you want to install
     * @param ComposerPackage $composerPackageWhich composer package which you want to install
     * @param string|null $whichRepositoryUrl
     *
     * @return static
     * @throws ComposerToolsException
     */
    static function create (ComposerPackage $composerPackageWhere, ComposerPackage $composerPackageWhich, ?string $whichRepositoryUrl): self {
        if (!is_null($whichRepositoryUrl) && !filter_var($whichRepositoryUrl, FILTER_VALIDATE_URL)) {
            throw new ComposerToolsException("invalid url for repo server: '{$whichRepositoryUrl}'");
        }

        return new static($composerPackageWhere, $composerPackageWhich, $whichRepositoryUrl);
    }

    /**
     * LinkLocalPackage constructor.
     *
     * @param ComposerPackage $composerPackageWhere
     * @param ComposerPackage $composerPackageWhich
     * @param string|null $whichRepositoryUrl
     */
    public function __construct (ComposerPackage $composerPackageWhere, ComposerPackage $composerPackageWhich, ?string $whichRepositoryUrl) {
        $this->composerPackageWhere = $composerPackageWhere;
        $this->composerPackageWhich = $composerPackageWhich;
        $this->whichRepositoryUrl = $whichRepositoryUrl;
    }

    /**
     * @return ComposerPackage
     */
    private function where (): ComposerPackage {
        return $this->composerPackageWhere;
    }

    /**
     * @return ComposerPackage
     */
    private function which (): ComposerPackage {
        return $this->composerPackageWhich;
    }

    /**
     * @param bool $force
     *
     * @throws ComposerToolsException
     * @throws NetworkRetrieverException
     */
    function link (bool $force = false): void {
        $isLinked = $this->where()->isPackageLinked($this->which());
        if (!$isLinked || $force) {
            $this->install();
        }
    }

    /**
     * @throws ComposerToolsException
     * @throws NetworkRetrieverException
     */
    private function install (): void {
        $versionWhich = $this->which()->getComposer()->getVersion();
        $versionWhere = $this->where()->getComposer()->getRequirePackageVersionString($this->which()->getComposer()->getName());
        $repositoryVersion = $this->whichRepositoryUrl ? RetrieveVersionFromRepositoryUrl::create($this->which(), $this->whichRepositoryUrl)->getVersionFromRepoServer() : null;
        $versionForInstall = (string) ($repositoryVersion ?? $this->parseVersionString('999.999.998'))->incrementPatch();
        $vendorUpdate = new VendorUpdate($this->where());

        try {
            $this->which()->getComposer()->setVersion($versionForInstall);
            $this->where()->getComposer()->setRequirePackageVersion($this->which()->getComposer()->getName(), sprintf('^%s', $versionForInstall));
            $this->where()->getComposer()->setRepositories($this->addWhichToWhereRepoList());
            //save
            $this->which()->getComposer()->save();
            $this->where()->getComposer()->save();

            $repositoryVersion ? $vendorUpdate->updateVendorPackage($this->which()) : $vendorUpdate->updateVendorAll();
        } catch (Exception $e) {
            $this->restoreVersion($versionWhich, $versionWhere);
            throw $e;
        }
        //restore version original in where
        $this->restoreVersion($versionWhich, $versionWhere);
    }

    /**
     * @param $versionWhich
     * @param $versionWhere
     *
     * @throws ComposerToolsException
     */
    private function restoreVersion ($versionWhich, $versionWhere): void {
        $this->where()->getComposer()->setRequirePackageVersion($this->which()->getComposer()->getName(), $versionWhere);
        $this->where()->getComposer()->setRepositories($this->removeWhichFromWhereRepoList());
        $this->where()->getComposer()->save();

        //restore version original in which
        $this->which()->getComposer()->setVersion($versionWhich);
        $this->which()->getComposer()->save();
    }

    /**
     * @param string $versionString
     *
     * @return Version
     * @throws ComposerToolsException
     */
    private function parseVersionString (string $versionString): Version {
        try {
            return Version::parse(str_replace('^', '', $versionString));
        } catch (InvalidVersionException $e) {
            throw new ComposerToolsException(sprintf("failed to parse version string: %s", $e->getMessage()));
        }
    }

    /**
     * @return array
     * @throws ComposerToolsException
     */
    private function addWhichToWhereRepoList (): array {
        $repoList = $this->where()->getComposer()->getRepositories();
        foreach ($repoList as $idx => $repo) {
            if ($repo['type'] !== 'path') {
                continue;
            } elseif ($repo['url'] === $this->which()->getPath()) {
                return $repoList;
            }
        }
        $repoList[] = [
            'type' => 'path',
            'url' => $this->which()->getPath(),
        ];

        return $repoList;
    }

    /**
     * @return array
     * @throws ComposerToolsException
     */
    private function removeWhichFromWhereRepoList (): array {
        $repoList = $this->where()->getComposer()->getRepositories();
        foreach ($repoList as $idx => $repo) {
            if ($repo['type'] !== 'path') {
                continue;
            } elseif ($repo['url'] === $this->which()->getPath()) {
                unset($repoList[$idx]);
            }
        }

        return $repoList;
    }
}
