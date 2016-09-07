<?php

/**
 * @file tests/data/60-content/CmontgomerieSubmissionTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CmontgomerieSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class CmontgomerieSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'cmontgomerie',
			'firstName' => 'Craig',
			'lastName' => 'Montgomerie',
			'affiliation' => 'University of Alberta',
			'country' => 'Canada',
		));

		$this->createSubmission(array(
			'title' => 'Computer Skill Requirements for New and Existing Teachers: Implications for Policy and Practice',
			'abstract' => 'The integration of technology into the classroom is a major issue in education today. Many national and provincial initiatives specify the technology skills that students must demonstrate at each grade level. The Government of the Province of Alberta in Canada, has mandated the implementation of a new curriculum which began in September of 2000, called Information and Communication Technology. This curriculum is infused within core courses and specifies what students are “expected to know, be able to do, and be like with respect to technology” (Alberta Learning, 2000). Since teachers are required to implement this new curriculum, school jurisdictions are turning to professional development strategies and hiring standards to upgrade teachers’ computer skills to meet this goal. This paper summarizes the results of a telephone survey administered to all public school jurisdictions in the Province of Alberta with a 100% response rate. We examined the computer skills that school jurisdictions require of newly hired teachers, and the support strategies employed for currently employed teachers.',
			'keywords' => array(
				'Integrating Technology',
				'Computer Skills',
				'Survey',
				'Alberta',
				'National',
				'Provincial',
				'Professional Development',
			),
			'additionalAuthors' => array(
				array(
					'firstName' => 'Mark',
					'lastName' => 'Irvine',
					'country' => 'Canada',
					'affiliation' => 'University of Victoria',
					'email' => 'mirvine@mailinator.com',
				)
			),
		));

		$this->logOut();
	}
}
