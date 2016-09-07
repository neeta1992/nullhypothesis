<?php

/**
 * @defgroup plugins_generic_phpMyVisites phpMyVisites Plugin
 */
 
/**
 * @file plugins/generic/phpMyVisites/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_phpMyVisites
 * @brief Wrapper for phpMyVisites plugin.
 *
 */

require_once('PhpMyVisitesPlugin.inc.php');

return new PhpMyVisitesPlugin();

?>
