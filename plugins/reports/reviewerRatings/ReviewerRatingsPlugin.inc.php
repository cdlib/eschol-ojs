<?php

/**
 * @file ReviewerRatingsPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewerRatingsPlugin
 * @ingroup plugins_reports_reviewer
 * @see ReviewerCountsDAO
 *
 * @brief Reviewer ratings plugin--average reviews
 */

//$Id$

import('classes.plugins.ReportPlugin');

class ReviewerRatingsPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ReviewerRatingsDAO');
			$reviewerRatingsDAO = new ReviewerRatingsDAO();
			DAORegistry::registerDAO('ReviewerRatingsDAO', $reviewerRatingsDAO);
		}
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ReviewerRatingsPlugin';
	}

	function getDisplayName() {
		return \OjsLocale::translate('plugins.reports.reviewerRatings.displayName');
	}

	function getDescription() {
		return \OjsLocale::translate('plugins.reports.reviewerRatings.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=reviewerRatings-' . date('Ymd') . '.csv');
		\OjsLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		$reviewerRatingsDao =& DAORegistry::getDAO('ReviewerRatingsDAO');
		$reviewerIterator = $reviewerRatingsDao->getReviewerRatings($journal->getId());


		$yesnoMessages = array( 0 => \OjsLocale::translate('common.no'), 1 => \OjsLocale::translate('common.yes'));

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$recommendations = ReviewAssignment::getReviewerRecommendationOptions();

		$columns = array(
			'reviewerid' => \OjsLocale::translate('plugins.reports.reviewerRatings.reviewerId'),
			'lastname' => \OjsLocale::translate('user.lastName'),
			'firstname' => \OjsLocale::translate('user.firstName'),
			'email' =>\OjsLocale::translate('user.email'),
			'totalreviews' => \OjsLocale::translate('plugins.reports.reviewerRatings.totalReviews'),
			'averagerating' => \OjsLocale::translate('plugins.reports.reviewerRatings.averageRating'),            
		);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		OjsString::fputcsv($fp, array_values($columns));

		while ($row =& $reviewerIterator->next()) {
			foreach ($columns as $index => $junk) {
				if (in_array($index, $yesNoArray)) {
					$columns[$index] = $yesnoMessages[$row[$index]];
				} elseif ($index == "recommendation") {
					$columns[$index] = (!isset($row[$index])) ? \OjsLocale::translate('common.none') : \OjsLocale::translate($recommendations[$row[$index]]);
				} elseif ($index == "comments") {
					if (isset($comments[$row['articleid']][$row['reviewerid']])) {
						$columns[$index] = $comments[$row['articleid']][$row['reviewerid']];
					} else {
						$columns[$index] = "";
					}
				} else {
					$columns[$index] = $row[$index];
				}
			}
			OjsString::fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}
}

?>
