<?php

namespace Saparot\ComposerTools\File;

use Exception;
use Saparot\ComposerTools\ComposerToolsException;

class Json {

    /**
     * @var string
     */
    protected $composerFile;

    /**
     * @var int
     */
    private $saveSpaceReducer;

    /**
     * @var array
     */
    protected $data;

    /**
     * ComposerFile constructor.
     *
     * @param string $composerFile
     * @param int $saveSpaceReducer
     */
    function __construct (string $composerFile, $saveSpaceReducer = 2) {
        $this->composerFile = $composerFile;
        $this->saveSpaceReducer = $saveSpaceReducer;
    }

    function getFileName (): string {
        return $this->composerFile;
    }

    /**
     * @return string
     * @throws ComposerToolsException
     */
    function getRealPath (): string {
        $path = realpath(dirname($this->composerFile));
        if ($path === false) {
            throw new ComposerToolsException(sprintf('failed to retrieve path for: %s', $this->composerFile));
        }

        return $path;
    }

    /**
     * @return array
     * @throws ComposerToolsException
     */
    function getData (): array {
        $this->load(false);

        return $this->data;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws ComposerToolsException
     */
    function hasConfigKey (string $key): bool {
        $this->load(false);

        return isset($this->data[$key]);
    }

    /**
     * @param bool $force
     *
     * @throws ComposerToolsException
     */
    function load (bool $force): void {
        if (!$this->data || $force) {
            $this->read();
        }
    }

    /**
     * @param $key
     * @param bool $throw
     * @param null $default
     *
     * @return mixed|null
     * @throws ComposerToolsException
     */
    protected function getConfigKey ($key, bool $throw, $default = null) {
        if ($throw && !$this->hasConfigKey($key)) {
            throw new ComposerToolsException("key '{$key}' not found in file '{$this->getFileName()}'");
        }

        return $this->hasConfigKey($key) ? $this->data[$key] : $default;
    }

    /**
     * @return array
     * @throws ComposerToolsException
     */
    private function read (): array {
        if (!file_exists($this->composerFile)) {
            throw new ComposerToolsException(sprintf("file %s not found!", $this->getFileName()));
        }
        $content = json_decode(file_get_contents($this->getFileName()), true);
        if (json_last_error()) {
            throw new ComposerToolsException(sprintf("syntax errors in %s, update failed", $this->getFileName()));
        }

        $this->data = $content;

        return $this->data;
    }

    /**
     * @throws ComposerToolsException
     */
    function save (): void {
        $this->data ? $this->update($this->data) : null;
    }

    /**
     * @param array $data
     *
     * @throws ComposerToolsException
     */
    function update (array $data): void {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_null($this->saveSpaceReducer)) {
            $content = $this->spaceReduce($content);
        }

        if (!file_put_contents($this->getFileName(), $content)) {
            throw new ComposerToolsException(sprintf("failed to write %s", $this->getFileName()));
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function spaceReduce (string $content): string {
        $lines = explode("\n", $content);

        foreach ($lines as $key => $line) {
            if (preg_match("#^( )+#", $line, $matches)) {
                $lines[$key] = preg_replace(sprintf("#^%s#", $matches[0]), str_repeat(' ', strlen($matches[0]) / 2), $line);
            }
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @param string $key
     * @param $value
     */
    protected function set (string $key, $value): void {
        $this->data[$key] = $value;
    }
}
