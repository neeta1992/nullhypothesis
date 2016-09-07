<?php

/**
 * @file tests/data/60-content/JmwandengaSubmissionTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JmwandengaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class JmwandengaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'jmwandenga',
			'firstName' => 'John',
			'lastName' => 'Mwandenga',
			'affiliation' => 'University of Cape Town',
			'country' => 'South Africa',
		));

		$title = 'Signalling Theory Dividends: A Review Of The Literature And Empirical Evidence';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The signaling theory suggests that dividends signal future prospects of a firm. However, recent empirical evidence from the US and the Uk does not offer a conclusive evidence on this issue. There are conflicting policy implications among financial economists so much that there is no practical dividend policy guidance to management, existing and potential investors in shareholding. Since corporate investment, financing and distribution decisions are a continuous function of management, the dividend decisions seem to rely on intuitive evaluation.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('jjanssen', 'Julie Janssen');
		$this->assignReviewer('amccrae', 'Aisla McCrae');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->recordEditorialDecision('Send to Copyediting');
		$this->waitForElementPresent('//a[contains(text(), \'Copyediting\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Copyeditor', 'Sarah Vogt');
		$this->recordEditorialDecision('Send To Production');
		$this->waitForElementPresent('//a[contains(text(), \'Production\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Layout Editor', 'Stephen Hellier');
		$this->assignParticipant('Proofreader', 'Sabine Kumar');

		// Create a galley
		$this->waitForElementPresent($selector='css=[id^=component-grid-articlegalleys-articlegalleygrid-addGalley-button-]');
		$this->click($selector);
		$this->waitForElementPresent('css=[id^=label-]');
		$this->type('css=[id^=label-]', 'PDF');
		$this->click('//button[text()=\'Save\']');
		$this->uploadWizardFile('PDF');

		$this->logOut();
	}
}
