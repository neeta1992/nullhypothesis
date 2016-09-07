<?php

/**
 * @file plugins/metadata/dc11/tests/Dc11MetadataPluginTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dc11MetadataPluginTest
 * @ingroup plugins_metadata_dc11_tests
 * @see Dc11MetadataPlugin
 *
 * @brief Test class for Dc11MetadataPlugin.
 */

import('lib.pkp.plugins.metadata.dc11.tests.PKPDc11MetadataPluginTest');

class Dc11MetadataPluginTest extends PKPDc11MetadataPluginTest {
	/**
	 * @covers Dc11MetadataPlugin
	 * @covers PKPDc11MetadataPlugin
	 */
	public function testDc11MetadataPlugin() {
		parent::testDc11MetadataPlugin(array('article=>dc11'));
	}
}
?>
