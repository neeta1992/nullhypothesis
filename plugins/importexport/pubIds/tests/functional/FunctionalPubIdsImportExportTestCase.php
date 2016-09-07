<?php

/**
 * @file plugins/importexport/pubIds/tests/functional/FunctionalPubIdsImportExportTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalPubIdsImportExportTest
 * @ingroup plugins_importexport_pubIds_tests_functional
 *
 * @brief Test the pubIds import/exoprt plugin.
 *
 * FEATURE: Import and Export of the public identifiers
 *   AS A    journal manager
 *   I WANT  to be able to import and exoprt the public identifiers for issues, articles, and galleys
 *   SO THAT already used public identifiers can be integrated and managed in the system.
 */

require_mock_env('env1');

import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalPubIdsImportExportTest extends FunctionalImportExportBaseTestCase {

	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'submissions', 'submission_settings', 'submission_galleys', 'submission_galley_settings',
			'issues', 'issue_settings',
			'plugin_settings'
		);
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
	}

	public function testPubIdsImport() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		PKPTestHelper::xdebugScream(false);
		$journalDao->deleteAllPubIds(1, 'other::urn');
		PKPTestHelper::xdebugScream(true);

		$testfile = 'tests/functional/plugins/importexport/pubIds/sample.xml';
		$args = array('import', $testfile, 'test', 'admin');
		$result = $this->executeCli('PubIdImportExportPlugin', $args);
		self::assertRegExp('/##plugins.importexport.pubIds.import.success.description##/', $result);

		// Export single object.
		$export = $this->getXpathOnExport('PubIdImportExportPlugin/exportIssue/1');
		$testCases = array(
			'//pubId[@pubIdType="other::urn"][@pubObjectType="Issue"]' => 'urn:nbn:de:1234-issue1URNSuffix',
			'//pubId[@pubIdType="other::urn"][@pubObjectType="Article"]' => 'urn:nbn:de:1234-article1URNSuffix',
			'//pubId[@pubIdType="other::urn"][@pubObjectType="Galley"][@pubObjectId="1"]' => 'urn:nbn:de:1234-galley1URNSuffix',
			'//pubId[@pubIdType="other::urn"][@pubObjectType="Galley"][@pubObjectId="2"]' => 'urn:nbn:de:1234-galley2URNSuffix',
			);
		foreach($testCases as $xPath => $expectedPubId) {
			self::assertEquals(
				$expectedPubId,
				$export->evaluate("string($xPath)"),
				"Error while evaluating xPath for $expectedPubId:"
			);
		}

		// Trying to import the same file again should lead to an error.
		$args = array('import', $testfile, 'test', 'admin');
		$result = $this->executeCli('PubIdImportExportPlugin', $args);
		self::assertRegExp('/##plugins.importexport.pubIds.import.error.pubIdExists##/', $result);

	}

}
?>
