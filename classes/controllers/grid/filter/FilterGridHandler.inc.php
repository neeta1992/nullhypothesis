<?php

/**
 * @file classes/controllers/grid/filter/FilterGridHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGridHandler
 * @ingroup classes_controllers_grid_filter
 *
 * @brief Handle OJS specific parts of filter grid requests.
 */

import('lib.pkp.classes.controllers.grid.filter.PKPFilterGridHandler');

class FilterGridHandler extends PKPFilterGridHandler {
	/**
	 * Constructor
	 */
	function FilterGridHandler() {
		parent::PKPFilterGridHandler();
		$this->addRoleAssignment(
				ROLE_ID_MANAGER,
				array('fetchGrid', 'addFilter', 'editFilter', 'updateFilter', 'deleteFilter'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Make sure the user can change the journal setup.
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
}
