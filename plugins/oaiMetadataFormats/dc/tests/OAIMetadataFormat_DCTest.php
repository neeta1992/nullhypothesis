<?php

/**
 * @defgroup plugins_oaiMetadataFormats_dc_tests Dublin Core OAI Plugin
 */

/**
 * @file plugins/oaiMetadataFormats/dc/tests/OAIMetadataFormat_DCTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DCTest
 * @ingroup plugins_oaiMetadataFormats_dc_tests
 * @see OAIMetadataFormat_DC
 *
 * @brief Test class for OAIMetadataFormat_DC.
 */

require_mock_env('env2');

import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');

class OAIMetadataFormat_DCTest extends PKPTestCase {

	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs() {
		return array('AuthorDAO', 'OAIDAO', 'ArticleGalleyDAO', 'PublishedArticleDAO');
	}

	/**
	 * @see PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('request');
	}

	/**
	 * @covers OAIMetadataFormat_DC
	 * @covers Dc11SchemaArticleAdapter
	 */
	public function testToXml() {
		$this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

		//
		// Create test data.
		//
		$journalId = 1;

		// Enable the DOI plugin.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enabled', 1);
		$pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enableIssueDoi', 1);
		$pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enableSubmissionDoi', 1);
		$pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enableRepresentationyDoi', 1);

		// Author
		import('classes.article.Author');
		$author = new Author();
		$author->setFirstName('author-firstname');
		$author->setLastName('author-lastname');
		$author->setAffiliation('author-affiliation', 'en_US');
		$author->setEmail('someone@example.com');

		// Article
		import('classes.article.PublishedArticle');
		$article = $this->getMock('PublishedArticle', array('getBestArticleId')); /* @var $article PublishedArticle */
		$article->expects($this->any())
		        ->method('getBestArticleId')
		        ->will($this->returnValue(9));
		$article->setId(9);
		$article->setJournalId($journalId);
		$author->setSubmissionId($article->getId());
		$article->setPages(15);
		$article->setType('art-type', 'en_US');
		$article->setTitle('article-title-en', 'en_US');
		$article->setTitle('article-title-de', 'de_DE');
		$article->setDiscipline('article-discipline', 'en_US');
		$article->setSubject('article-subject', 'en_US');
		$article->setAbstract('article-abstract', 'en_US');
		$article->setSponsor('article-sponsor', 'en_US');
		$article->setStoredPubId('doi', 'article-doi');
		$article->setLanguage('en_US');

		// Galleys
		import('classes.article.ArticleGalley');
		$galley = new ArticleGalley();
		$galley->setId(98);
		$galley->setStoredPubId('doi', 'galley-doi');
		$galleys = array($galley);

		// Journal
		import('classes.journal.Journal');
		$journal = $this->getMock('Journal', array('getSetting')); /* @var $journal Journal */
		$journal->expects($this->any())
		        ->method('getSetting') // includes getTitle()
		        ->will($this->returnCallback(array($this, 'getJournalSetting')));
		$journal->setPrimaryLocale('en_US');
		$journal->setPath('journal-path');
		$journal->setId($journalId);

		// Section
		import('classes.journal.Section');
		$section = new Section();
		$section->setIdentifyType('section-identify-type', 'en_US');

		// Issue
		import('classes.issue.Issue');
		$issue = $this->getMock('Issue', array('getIssueIdentification')); /* @var $issue Issue */;
		$issue->expects($this->any())
		      ->method('getIssueIdentification')
		      ->will($this->returnValue('issue-identification'));
		$issue->setId(96);
		$issue->setDatePublished('2010-11-05');
		$issue->setStoredPubId('doi', 'issue-doi');
		$issue->setJournalId($journalId);


		//
		// Create infrastructural support objects
		//

		// Router
		import('lib.pkp.classes.core.PKPRouter');
		$router = $this->getMock('PKPRouter', array('url'));
		$application = PKPApplication::getApplication();
		$router->setApplication($application);
		$router->expects($this->any())
		       ->method('url')
		       ->will($this->returnCallback(array($this, 'routerUrl')));

		// Request
		import('classes.core.Request');
		$request = $this->getMock('Request', array('getRouter'));
		$request->expects($this->any())
		        ->method('getRouter')
		        ->will($this->returnValue($router));
		Registry::set('request', $request);


		//
		// Create mock DAOs
		//

		// Create a mocked AuthorDAO that returns our test author.
		import('classes.article.AuthorDAO');
		$authorDao = $this->getMock('AuthorDAO', array('getBySubmissionId'));
		$authorDao->expects($this->any())
		          ->method('getBySubmissionId')
		          ->will($this->returnValue(array($author)));
		DAORegistry::registerDAO('AuthorDAO', $authorDao);

		// Create a mocked OAIDAO that returns our test data.
		import('classes.oai.ojs.OAIDAO');
		$oaiDao = $this->getMock('OAIDAO', array('getJournal', 'getSection', 'getIssue'));
		$oaiDao->expects($this->any())
		       ->method('getJournal')
		       ->will($this->returnValue($journal));
		$oaiDao->expects($this->any())
		       ->method('getSection')
		       ->will($this->returnValue($section));
		$oaiDao->expects($this->any())
		       ->method('getIssue')
		       ->will($this->returnValue($issue));
		DAORegistry::registerDAO('OAIDAO', $oaiDao);

		// Create a mocked ArticleGalleyDAO that returns our test data.
		import('classes.article.ArticleGalleyDAO');
		$articleGalleyDao = $this->getMock('ArticleGalleyDAO', array('getBySubmissionId'));
		$articleGalleyDao->expects($this->any())
		                 ->method('getBySubmissionId')
		                 ->will($this->returnValue($galleys));
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
		// FIXME: ArticleGalleyDAO::getBySubmissionId returns iterator; array expected here. Fix expectations.

		// Create a mocked PublishedArticleDAO that returns our test article.
		import('classes.article.PublishedArticleDAO');
		$articleDao = $this->getMock('PublishedArticleDAO', array('getPublishedArticleByArticleId'));
		$articleDao->expects($this->any())
		            ->method('getPublishedArticleByArticleId')
		            ->will($this->returnValue($article));
		DAORegistry::registerDAO('PublishedArticleDAO', $articleDao);

		//
		// Test
		//

		// OAI record
		$record = new OAIRecord();
		$record->setData('article', $article);
		$record->setData('galleys', $galleys);
		$record->setData('journal', $journal);
		$record->setData('section', $section);
		$record->setData('issue', $issue);

		// Instantiate the OAI meta-data format.
		$prefix = OAIMetadataFormatPlugin_DC::getMetadataPrefix();
		$schema = OAIMetadataFormatPlugin_DC::getSchema();
		$namespace = OAIMetadataFormatPlugin_DC::getNamespace();
		$mdFormat = new OAIMetadataFormat_DC($prefix, $schema, $namespace);

		$xml = $mdFormat->toXml($record);
		self::assertXmlStringEqualsXmlFile('tests/plugins/oaiMetadataFormats/dc/expectedResult.xml', $xml);
	}


	//
	// Public helper methods
	//
	/**
	 * Callback for journal settings.
	 * @param $settingName string
	 */
	function getJournalSetting($settingName) {
		switch ($settingName) {
			case 'name':
				return array('en_US' => 'journal-title');

			case 'copyrightNotice':
				return array('en_US' => 'journal-copyright');

			case 'publisherInstitution':
				return array('journal-publisher');

			case 'onlineIssn':
				return 'onlineIssn';

			case 'printIssn':
				return null;

			default:
				self::fail('Required journal setting is not necessary for the purpose of this test.');
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Callback for router url construction simulation.
	 */
	function routerUrl($request, $newContext = null, $handler = null, $op = null, $path = null) {
       	return $handler.'-'.$op.'-'.implode('-', $path);
	}
}
?>
