<?php

/**
 * @defgroup plugins_generic_openAIRE OpenAIRE Plugin
 */
 
/**
 * @file plugins/generic/openAIRE/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_openAIRE
 * @brief Wrapper for openAIRE plugin.
 *
 */
require_once('OpenAIREPlugin.inc.php');

return new OpenAIREPlugin();

?>
