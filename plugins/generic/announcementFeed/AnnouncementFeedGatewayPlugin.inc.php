<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedGatewayPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Gateway component of announcement feed plugin
 *
 */

import('lib.pkp.classes.plugins.GatewayPlugin');

class AnnouncementFeedGatewayPlugin extends GatewayPlugin {
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function AnnouncementFeedGatewayPlugin($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AnnouncementFeedGatewayPlugin';
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function getAnnouncementFeedPlugin() {
		return PluginRegistry::getPlugin('generic', $this->parentPluginName);
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 */
	function getPluginPath() {
		return $this->getAnnouncementFeedPlugin()->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		return $this->getAnnouncementFeedPlugin()->getTemplatePath() . 'templates/';
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		return $this->getAnnouncementFeedPlugin()->getEnabled();
	}

	/**
	 * Get the management verbs for this plugin (override to none so that the parent
	 * plugin can handle this)
	 * @return array
	 */
	function getManagementVerbs() {
		return array();
	}

	/**
	 * Handle fetch requests for this plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		// Make sure we're within a Journal context
		$journal = $request->getJournal();
		if (!$journal) return false;

		// Make sure announcements and plugin are enabled
		$announcementsEnabled = $journal->getSetting('enableAnnouncements');
		$announcementFeedPlugin = $this->getAnnouncementFeedPlugin();
		if (!$announcementsEnabled || !$announcementFeedPlugin->getEnabled()) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		// Get limit setting, if any
		$recentItems = (int) $announcementFeedPlugin->getSetting($journal->getId(), 'recentItems');

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$journalId = $journal->getId();
		if ($recentItems > 0) {
			import('lib.pkp.classes.db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$announcements = $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journalId, $rangeInfo);
		} else {
			$announcements =  $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
		}

		// Get date of most recent announcement
		$lastDateUpdated = $announcementFeedPlugin->getSetting($journal->getId(), 'dateUpdated');
		if ($announcements->wasEmpty()) {
			if (empty($lastDateUpdated)) {
				$dateUpdated = Core::getCurrentDate();
				$announcementFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentAnnouncement = $announcementDao->getMostRecentAnnouncementByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
			$dateUpdated = $mostRecentAnnouncement->getDatetimePosted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) {
				$announcementFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');
			}
		}

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$version = $versionDao->getCurrentVersion();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('ojsVersion', $version->getVersionString());
		$templateMgr->assign('selfUrl', $request->getCompleteUrl());
		$templateMgr->assign('dateUpdated', $dateUpdated);
		$templateMgr->assign('announcements', $announcements->toArray());
		$templateMgr->assign('journal', $journal);

		$templateMgr->display($this->getTemplatePath() . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}
}

?>
