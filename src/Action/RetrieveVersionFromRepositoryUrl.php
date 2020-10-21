<?php

namespace Saparot\ComposerTools\Action;

use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;
use Saparot\ComposerTools\ComposerPackage;
use Saparot\ComposerTools\ComposerToolsException;
use Saparot\ComposerTools\File\Composer;
use Saparot\NetworkRetriever\HttpRetriever;
use Saparot\NetworkRetriever\HttpRetrieveResult;
use Saparot\NetworkRetriever\NetworkRetrieverException;

class RetrieveVersionFromRepositoryUrl {

    /**
     * @var ComposerPackage
     */
    private $composerPackage;

    /**
     * @var string
     */
    private $repoServerUrl;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param ComposerPackage $composerPackage
     * @param string $repoServerUrl
     *
     * @return static
     * @throws ComposerToolsException
     */
    static function create (ComposerPackage $composerPackage, string $repoServerUrl): self {
        if (!filter_var($repoServerUrl, FILTER_VALIDATE_URL)) {
            throw new ComposerToolsException("invalid url for repo server: '{$repoServerUrl}'");
        }

        return new static($composerPackage, $repoServerUrl);
    }

    /**
     * RetrieveVersionFromRepositoryUrl constructor.
     *
     * @param ComposerPackage $composerPackage
     * @param string $repoServerUrl
     */
    private function __construct (ComposerPackage $composerPackage, string $repoServerUrl) {
        $this->composerPackage = $composerPackage;
        $this->composer = $this->composerPackage->getComposer();
        $this->repoServerUrl = $repoServerUrl;
    }

    /**
     * @return Version
     * @throws ComposerToolsException
     * @throws InvalidVersionException
     * @throws NetworkRetrieverException
     */
    function getVersionFromRepoServer (): ?Version {
        $result = (new HttpRetriever())->retrieveByGet($this->repoServerUrl);
        switch ($result->getHttpStatus()) {
            case 200:
                return $this->parseRepositoryResult($result);
            case 401:
                throw new ComposerToolsException("auth failed", ComposerToolsException::ERROR_REPO_VERSION_RETRIEVE_AUTH_FAIL);
            case 404:
                throw new ComposerToolsException("not found", ComposerToolsException::ERROR_REPO_VERSION_RETRIEVE_NOT_FOUND);
            default:
                throw new ComposerToolsException("failed to retrieve", ComposerToolsException::ERROR_REPO_VERSION_RETRIEVE_HTTP_BASE + $result->getHttpStatus());
        }
    }

    /**
     * @param HttpRetrieveResult $result
     *
     * @return Version|null
     * @throws ComposerToolsException
     * @throws InvalidVersionException
     */
    private function parseRepositoryResult (HttpRetrieveResult $result): ?Version {
        $data = json_decode($result->getData(), true);

        if (json_last_error()) {
            throw new ComposerToolsException("notice: failed to parse list from {$this->repoServerUrl}");
        }

        if (!isset($data['packages'][$this->composer->getName()])) {
            return null;
        }
        $versions = array_keys($data['packages'][$this->composer->getName()]);

        asort($versions, SORT_NATURAL);
        $versionUse = array_pop($versions);

        return Version::parse($versionUse);
    }
}
