<?php

/**
 * @file tests/data/60-content/FpaglieriSubmissionTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FpaglieriSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class FpaglieriSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'fpaglieri',
			'firstName' => 'Fabio',
			'lastName' => 'Paglieri',
			'affiliation' => 'University of Rome',
			'country' => 'Italy',
		));

		$title = 'Hansen & Pinto: Reason Reclaimed';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'abstract' => 'None.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('jjanssen', 'Julie Janssen');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->recordEditorialDecision('Send to Copyediting');
		$this->waitForElementPresent('//a[contains(text(), \'Copyediting\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Copyeditor', 'Sarah Vogt');
		$this->recordEditorialDecision('Send To Production');
		$this->waitForElementPresent('//a[contains(text(), \'Production\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Layout Editor', 'Stephen Hellier');
		$this->assignParticipant('Proofreader', 'Sabine Kumar');
		$this->logOut();
	}
}
