<?php

/**
 * @file ReviewReportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewReportPlugin
 * @ingroup plugins_reports_review
 * @see ReviewReportDAO
 *
 * @brief Review report plugin
 */

//$Id$

import('classes.plugins.ReportPlugin');

class ReviewReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ReviewReportDAO');
			$reviewReportDAO = new ReviewReportDAO();
			DAORegistry::registerDAO('ReviewReportDAO', $reviewReportDAO);
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
		return 'ReviewReportPlugin';
	}

	function getDisplayName() {
		return \OjsLocale::translate('plugins.reports.reviews.displayName');
	}

	function getDescription() {
		return \OjsLocale::translate('plugins.reports.reviews.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=reviews-' . date('Ymd') . '.csv');
		\OjsLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		$reviewReportDao =& DAORegistry::getDAO('ReviewReportDAO');
		list($commentsIterator, $reviewsIterator) = $reviewReportDao->getReviewReport($journal->getId());

		$comments = array();
		while ($row =& $commentsIterator->next()) {
			if (isset($comments[$row['article_id']][$row['author_id']])) {
				$comments[$row['article_id']][$row['author_id']] .= "; " . $row['comments'];
			} else {
				$comments[$row['article_id']][$row['author_id']] = $row['comments'];
			}
		}

		$yesnoMessages = array( 0 => \OjsLocale::translate('common.no'), 1 => \OjsLocale::translate('common.yes'));

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$recommendations = ReviewAssignment::getReviewerRecommendationOptions();

		$columns = array(
			'round' => \OjsLocale::translate('plugins.reports.reviews.round'),
			'article' => \OjsLocale::translate('article.articles'),
			'articleid' => \OjsLocale::translate('article.submissionId'),
			'articlestatus' => \OjsLocale::translate('article.status'),
			'reviewerid' => \OjsLocale::translate('plugins.reports.reviews.reviewerId'),
			'reviewer' => \OjsLocale::translate('plugins.reports.reviews.reviewer'),
			'firstname' => \OjsLocale::translate('user.firstName'),
			'middlename' => \OjsLocale::translate('user.middleName'),
			'lastname' => \OjsLocale::translate('user.lastName'),
			'affiliation' =>\OjsLocale::translate('user.affiliations'),
			'dateassigned' => \OjsLocale::translate('plugins.reports.reviews.dateAssigned'),
			'datenotified' => \OjsLocale::translate('plugins.reports.reviews.dateNotified'),
			'dateconfirmed' => \OjsLocale::translate('plugins.reports.reviews.dateConfirmed'),
			'datecompleted' => \OjsLocale::translate('plugins.reports.reviews.dateCompleted'),
			'datereminded' => \OjsLocale::translate('plugins.reports.reviews.dateReminded'),
			'declined' => \OjsLocale::translate('submissions.declined'),
			'cancelled' => \OjsLocale::translate('common.cancelled'),
			'recommendation' => \OjsLocale::translate('reviewer.article.recommendation'),
			'quality' => \OjsLocale::translate('plugins.reports.reviews.quality'),            
			'comments' => \OjsLocale::translate('comments.commentsOnArticle')
		);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		OjsString::fputcsv($fp, array_values($columns));

		while ($row =& $reviewsIterator->next()) {
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
