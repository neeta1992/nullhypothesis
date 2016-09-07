<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlIssueFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlIssueFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a set of issues
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class NativeXmlIssueFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function NativeXmlIssueFilter($filterGroup) {
		$this->setDisplayName('Native XML issue import');
		parent::NativeImportFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlIssueFilter';
	}


	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'issues';
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'issue';
	}

	/**
	 * Handle a singular element import.
	 * @param $node DOMElement
	 * @return Issue
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$user = $deployment->getUser();

		// Create and insert the issue (ID needed for other entities)
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->newDataObject();
		$issue->setJournalId($context->getId());
		$issue->setVolume($node->getAttribute('volume'));
		$issue->setYear($node->getAttribute('year'));
		$issue->setNumber($node->getAttribute('number'));
		$issue->setPublished($node->getAttribute('published'));
		$issue->setCurrent($node->getAttribute('current'));
		$issue->setAccessStatus($node->getAttribute('access_status'));
		$issue->setShowVolume($node->getAttribute('show_volume'));
		$issue->setShowNumber($node->getAttribute('show_number'));
		$issue->setShowYear($node->getAttribute('show_year'));
		$issue->setShowTitle($node->getAttribute('show_title'));

		$issueDao->insertObject($issue);
		$deployment->setIssue($issue);

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				$this->handleChildElement($n, $issue);
			}
		}
		$issueDao->updateObject($issue); // Persist setters
		return $issue;
	}

	/**
	 * Handle an element whose parent is the issue element.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function handleChildElement($n, $issue) {
		$localizedSetterMappings = $this->_getLocalizedIssueSetterMappings();
		$dateSetterMappings = $this->_getDateIssueSetterMappings();

		if (isset($localizedSetterMappings[$n->tagName])) {
			// If applicable, call a setter for localized content.
			$setterFunction = $localizedSetterMappings[$n->tagName];
			list($locale, $value) = $this->parseLocalizedContent($n);
			$issue->$setterFunction($value, $locale);
		} else if (isset($dateSetterMappings[$n->tagName])) {
			// Not a localized element?  Check for a date.
			$setterFunction = $dateSetterMappings[$n->tagName];
			$issue->$setterFunction(strtotime($n->textContent));
		} else switch ($n->tagName) {
			// Otherwise, delegate to specific parsing code
			case 'id':
				$this->parseIdentifier($n, $issue);
				break;
			case 'articles':
				$this->parseArticles($n, $issue);
				break;
			case 'issue_galleys':
				$this->parseIssueGalleys($n, $issue);
				break;
			case 'sections':
				$this->parseSections($n, $issue);
				break;
			case 'issue_cover':
				$this->parseIssueCover($n, $issue);
				break;
			default:
				fatalError('Unknown element ' . $n->tagName);
		}
	}

	//
	// Element parsing
	//
	/**
	 * Parse an identifier node and set up the issue object accordingly
	 * @param $element DOMElement
	 * @param $issue Issue
	 */
	function parseIdentifier($element, $issue) {
		$deployment = $this->getDeployment();
		$advice = $element->getAttribute('advice');
		switch ($element->getAttribute('type')) {
			case 'internal':
				// "update" advice not supported yet.
				assert(!$advice || $advice == 'ignore');
				break;
			case 'public':
				if ($advice == 'update') {
					$issue->setStoredPubId('publisher-id', $element->textContent);
				}
				break;
			default:
				if ($advice == 'update') {
					// Load pub id plugins
					$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
					$issue->setStoredPubId($element->getAttribute('type'), $element->textContent);
				}
		}
	}

	/**
	 * Parse an articles element
	 * @param $node DOMElement
	 * @param $issue Issue
	 */
	function parseIssueGalleys($node, $issue) {
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				assert($n->tagName == 'issue_galley');
				$this->parseIssueGalley($n, $issue);
			}
		}
	}

	/**
	 * Parse an issue galley and add it to the issue.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function parseIssueGalley($n, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>IssueGalley');
		assert(count($importFilters)==1); // Assert only a single unserialization filter
		$importFilter = array_shift($importFilters);
		$importFilter->setDeployment($this->getDeployment());
		$issueGalleyDoc = new DOMDocument();
		$issueGalleyDoc->appendChild($issueGalleyDoc->importNode($n, true));
		return $importFilter->execute($issueGalleyDoc);
	}

	/**
	 * Parse an articles element
	 * @param $node DOMElement
	 * @param $issue Issue
	 */
	function parseArticles($node, $issue) {
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				assert($n->tagName == 'article');
				$this->parseArticle($n, $issue);
			}
		}
	}

	/**
	 * Parse an article and add it to the issue.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function parseArticle($n, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>article');
		assert(count($importFilters)==1); // Assert only a single unserialization filter
		$importFilter = array_shift($importFilters);
		$importFilter->setDeployment($this->getDeployment());
		$articleDoc = new DOMDocument();
		$articleDoc->appendChild($articleDoc->importNode($n, true));
		return $importFilter->execute($articleDoc);
	}

	/**
	 * Parse a submission file and add it to the submission.
	 * @param $n DOMElement
	 * @param $submission Submission
	 */
	function parseSections($node, $issue) {
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				assert($n->tagName == 'section');
				$this->parseSection($n, $issue);
			}
		}
	}

	/**
	 * Parse a section stored in an issue.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function parseSection($node, $issue) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$issue = $deployment->getIssue();
		assert(is_a($issue, 'Issue'));

		// Create the data object
		$sectionDao  = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->newDataObject();
		$section->setContextId($context->getId());
		$section->setReviewFormId($node->getAttribute('review_form_id'));
		$section->setSequence($node->getAttribute('seq'));
		$section->setEditorRestricted($node->getAttribute('editor_restricted'));
		$section->setMetaIndexed($node->getAttribute('meta_indexed'));
		$section->setMetaReviewed($node->getAttribute('meta_reviewed'));
		$section->setAbstractsNotRequired($node->getAttribute('abstracts_not_required'));
		$section->setHideAuthor($node->getAttribute('hide_author'));
		$section->setHideTitle($node->getAttribute('hide_title'));
		$section->setHideAbout($node->getAttribute('hide_about'));
		$section->setAbstractWordCount($node->getAttribute('abstract_word_count'));

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'id':
						// Only support "ignore" advice for now
						$advice = $n->getAttribute('advice');
						assert(!$advice || $advice == 'ignore');
						break;
					case 'abbrev':
						list($locale, $value) = $this->parseLocalizedContent($n);
						$section->setAbbrev($value, $locale);
						break;
					case 'policy':
						list($locale, $value) = $this->parseLocalizedContent($n);
						$section->setPolicy($value, $locale);
						break;
					case 'title':
						list($locale, $value) = $this->parseLocalizedContent($n);
						$section->setTitle($value, $locale);
						break;
				}
			}
		}

		$sectionDao->insertObject($section);
	}

	/**
	 * Parse out the issue cover and store it in an issue.
	 * @param DOMElement $node
	 * @param Issue $issue
	 */
	function parseIssueCover($node, $issue) {
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover_image': $issue->setCoverImage($n->textContent); break;
					case 'cover_image_alt_text': $issue->setCoverImageAltText($n->textContent); break;
					case 'embed':
						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath(ASSOC_TYPE_JOURNAL, $issue->getJournalId()) . '/' . $issue->getCoverImage();
						file_put_contents($filePath, base64_decode($n->textContent));
						break;
				}
			}
		}
	}

	//
	// Helper functions
	//
	/**
	 * Get node name to setter function mapping for localized data.
	 * @return array
	 */
	function _getLocalizedIssueSetterMappings() {
		return array(
			'description' => 'setDescription',
			'title' => 'setTitle',
		);
	}

	/**
	 * Get node name to setter function mapping for issue date fields.
	 * @return array
	 */
	function _getDateIssueSetterMappings() {
		return array(
			'date_published'	=> 'setDatePublished',
			'date_notified'		=> 'setDateNotified',
			'last_modified'		=> 'setLastModified',
			'open_access_date'	=> 'setOpenAccessDate',
		);
	}
}

?>
