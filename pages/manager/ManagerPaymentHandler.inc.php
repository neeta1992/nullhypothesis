<?php

/**
 * @file pages/manager/ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerPaymentHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for configuring payments. 
 *
 */

import('pages.manager.ManagerHandler');

class ManagerPaymentHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerPaymentHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display Settings Form (main payments page)
	 */
	 function payments($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payments($args, $request);
	 }
	 
	 /**
	  * Execute the form or display it again if there are problems
	  */
	 function savePaymentSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePaymentSettings($args, $request);

		if ($success) {
 			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, 'payments'),
				'pageTitle' => 'manager.payment.feePaymentOptions',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, 'payments'),
				'backLinkLabel' => 'manager.payment.feePaymentOptions'
			));
			$templateMgr->display('frontend/pages/message.tpl');		
		}
	 }	 
	 
	 /** 
	  * Display all payments previously made
	  */
	 function viewPayments($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayments($args, $request);
	 }

	 /** 
	  * Display a single Completed payment 
	  */
	 function viewPayment($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayment($args, $request);
	 }
}

?>
