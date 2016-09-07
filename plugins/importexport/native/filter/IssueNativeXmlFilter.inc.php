<?php

/**
 * @file plugins/importexport/native/filter/IssueNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of issues to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class IssueNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function IssueNativeXmlFilter($filterGroup) {
		$this->setDisplayName('Native XML issue export');
		parent::NativeExportFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.IssueNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $issues array Array of issues
	 * @return DOMDocument
	 */
	function &process(&$issues) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$deployment = $this->getDeployment();

		if (count($issues)==1) {
			// Only one issue specified; create root node
			$rootNode = $this->createIssueNode($doc, $issues[0]);
		} else {
			// Multiple issues; wrap in a <issues> element
			$rootNode = $doc->createElementNS($deployment->getNamespace(), 'issues');
			foreach ($issues as $issue) {
				$rootNode->appendChild($this->createIssueNode($doc, $issue));
			}
		}
		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
	}

	//
	// Submission conversion functions
	//
	/**
	 * Create and return an issue node.
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @return DOMElement
	 */
	function createIssueNode($doc, $issue) {
		// Create the root node and attributes
		$deployment = $this->getDeployment();
		$deployment->setIssue($issue);

		$issueNode = $doc->createElementNS($deployment->getNamespace(), 'issue');
		$this->addIdentifiers($doc, $issueNode, $issue);

		$issueNode->setAttribute('volume', $issue->getVolume());
		$issueNode->setAttribute('number', $issue->getNumber());
		$issueNode->setAttribute('year', $issue->getYear());
		$issueNode->setAttribute('published', $issue->getPublished());
		$issueNode->setAttribute('current', $issue->getCurrent());
		$issueNode->setAttribute('access_status', $issue->getAccessStatus());
		$issueNode->setAttribute('show_volume', $issue->getShowVolume());
		$issueNode->setAttribute('show_number', $issue->getShowNumber());
		$issueNode->setAttribute('show_year', $issue->getShowYear());
		$issueNode->setAttribute('show_title', $issue->getShowTitle());

		$this->createLocalizedNodes($doc, $issueNode, 'description', $issue->getDescription(null));
		$this->createLocalizedNodes($doc, $issueNode, 'title', $issue->getTitle(null));

		$this->addDates($doc, $issueNode, $issue);
		$this->addSections($doc, $issueNode, $issue);
		$this->addCoverImage($doc, $issueNode, $issue);
		$this->addIssueGalleys($doc, $issueNode, $issue);
		$this->addArticles($doc, $issueNode, $issue);

		return $issueNode;
	}

	/**
	 * Create and add identifier nodes to a submission node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addIdentifiers($doc, $issueNode, $issue) {
		$deployment = $this->getDeployment();

		// Add internal ID
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $issue->getId()));
		$node->setAttribute('type', 'internal');
		$node->setAttribute('advice', 'ignore');

		// Add public ID
		if ($pubId = $issue->getStoredPubId('publisher-id')) {
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $pubId));
			$node->setAttribute('type', 'public');
			$node->setAttribute('advice', 'update');
		}

		// Add pub IDs by plugin
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
		foreach ((array) $pubIdPlugins as $pubIdPlugin) {
			$this->addPubIdentifier($doc, $issueNode, $issue, $pubIdPlugin);
		}
	}

	/**
	 * Add a single pub ID element for a given plugin to the document.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 * @param $pubIdPlugin PubIdPlugin
	 * @return DOMElement|null
	 */
	function addPubIdentifier($doc, $issueNode, $issue, $pubIdPlugin) {
		$pubId = $issue->getStoredPubId($pubIdPlugin->getPubIdType());
		if ($pubId) {
			$deployment = $this->getDeployment();
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $pubId));
			$node->setAttribute('type', $pubIdPlugin->getPubIdType());
			$node->setAttribute('advice', 'update');
			return $node;
		}
		return null;
	}

	/**
	 * Create and add various date nodes to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addDates($doc, $issueNode, $issue) {
		$deployment = $this->getDeployment();

		if ($issue->getDatePublished())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_published', strftime('%F', strtotime($issue->getDatePublished()))));

		if ($issue->getDateNotified())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_notified', strftime('%F', strtotime($issue->getDateNotified()))));

		if ($issue->getLastModified())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'last_modified', strftime('%F', strtotime($issue->getLastModified()))));

		if ($issue->getOpenAccessDate())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'open_access_date', strftime('%F', strtotime($issue->getOpenAccessDate()))));
	}

	/**
	 * Create and add articles to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addArticles($doc, $issueNode, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('article=>native-xml');
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment($this->getDeployment());
		$exportFilter->setIncludeSubmissionsNode(true);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$articlesDoc = $exportFilter->execute($publishedArticleDao->getPublishedArticles($issue->getId()));
		if ($articlesDoc->documentElement instanceof DOMElement) {
			$clone = $doc->importNode($articlesDoc->documentElement, true);
			$issueNode->appendChild($clone);
		}
	}

	/**
	 * Create and add issue galleys to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addIssueGalleys($doc, $issueNode, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('issuegalley=>native-xml');
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment($this->getDeployment());

		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$issueGalleysDoc = $exportFilter->execute($issueGalleyDao->getByIssueId($issue->getId()));
		if ($issueGalleysDoc->documentElement instanceof DOMElement) {
			$clone = $doc->importNode($issueGalleysDoc->documentElement, true);
			$issueNode->appendChild($clone);
		}
	}

	/**
	 * Add the issue cover image to its DOM element.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addCoverImage($doc, $issueNode, $issue) {

		$coverImage = $issue->getCoverImage();
		if (!empty($coverImage)) {
			$deployment = $this->getDeployment();
			$issueCoverNode = $doc->createElementNS($deployment->getNamespace(), 'issue_cover');
			$issueCoverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image', $issue->getCoverImage()));
			$issueCoverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image_alt_text', $issue->getCoverImage()));

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();

			$filePath = $publicFileManager->getContextFilesPath(ASSOC_TYPE_JOURNAL, $issue->getJournalId()) . '/' . $issue->getCoverImage();
			$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
			$embedNode->setAttribute('encoding', 'base64');
			$issueCoverNode->appendChild($embedNode);

			$issueNode->appendChild($issueCoverNode);
		}
	}

	/**
	 * Add the sections to the Issue DOM element.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addSections($doc, $issueNode, $issue) {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getByIssueId($issue->getId());
		$deployment = $this->getDeployment();
		$journal = $deployment->getContext();

		$sectionsNode = $doc->createElementNS($deployment->getNamespace(), 'sections');
		foreach ($sections as $section) {
			$sectionNode = $doc->createElementNS($deployment->getNamespace(), 'section');

			$sectionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $section->getId()));
			$node->setAttribute('type', 'internal');
			$node->setAttribute('advice', 'ignore');

			if ($section->getReviewFormId()) $sectionNode->setAttribute('review_form_id', $section->getReviewFormId());
			$sectionNode->setAttribute('ref', $section->getAbbrev($journal->getPrimaryLocale()));
			$sectionNode->setAttribute('seq', $section->getSequence());
			$sectionNode->setAttribute('editor_restricted', $section->getEditorRestricted());
			$sectionNode->setAttribute('meta_indexed', $section->getMetaIndexed());
			$sectionNode->setAttribute('meta_reviewed', $section->getMetaReviewed());
			$sectionNode->setAttribute('abstracts_not_required', $section->getAbstractsNotRequired());
			$sectionNode->setAttribute('hide_title', $section->getHideTitle());
			$sectionNode->setAttribute('hide_author', $section->getHideAuthor());
			$sectionNode->setAttribute('hide_about', $section->getHideAbout());
			$sectionNode->setAttribute('abstract_word_count', (int) $section->getAbstractWordCount());

			$this->createLocalizedNodes($doc, $sectionNode, 'abbrev', $section->getAbbrev(null));
			$this->createLocalizedNodes($doc, $sectionNode, 'policy', $section->getPolicy(null));
			$this->createLocalizedNodes($doc, $sectionNode, 'title', $section->getTitle(null));

			$sectionsNode->appendChild($sectionNode);
		}

		$issueNode->appendChild($sectionsNode);
	}
}

?>
