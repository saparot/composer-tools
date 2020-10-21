<?php

namespace Saparot\ComposerTools;

use Exception;

class ComposerToolsException extends Exception {

    const ERROR_REPO_VERSION_RETRIEVE_HTTP_BASE = 10000;
    const ERROR_REPO_VERSION_RETRIEVE_AUTH_FAIL = 10401;
    const ERROR_REPO_VERSION_RETRIEVE_NOT_FOUND = 10402;
}
