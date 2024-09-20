<?php

/**
 * @file ArticleReportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ArticleReportPlugin
 * @ingroup plugins_reports_article
 *
 * @brief Article report plugin
 */

// $Id$


import('classes.plugins.ReportPlugin');

class ArticleReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ArticleReportDAO');
			$articleReportDAO = new ArticleReportDAO();
			DAORegistry::registerDAO('ArticleReportDAO', $articleReportDAO);
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
		return 'ArticleReportPlugin';
	}

	function getDisplayName() {
		return \OjsLocale::translate('plugins.reports.articles.displayName');
	}

	function getDescription() {
		return \OjsLocale::translate('plugins.reports.articles.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=articles-' . date('Ymd') . '.csv');

		$articleReportDao =& DAORegistry::getDAO('ArticleReportDAO');
		list($articlesIterator, $authorsIterator, $decisionsIteratorsArray) = $articleReportDao->getArticleReport($journal->getId());

		$maxAuthors = $this->getMaxAuthorCount($authorsIterator);

		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator) {
			while ($row =& $decisionsIterator->next()) {
				$decisions[$row['article_id']] = $row['decision'];
			}
		}

		\OjsLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION));

		import('classes.article.Article');
		$decisionMessages = array(
			SUBMISSION_EDITOR_DECISION_ACCEPT => \OjsLocale::translate('editor.article.decision.accept'),
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => \OjsLocale::translate('editor.article.decision.pendingRevisions'),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => \OjsLocale::translate('editor.article.decision.resubmit'),
			SUBMISSION_EDITOR_DECISION_DECLINE => \OjsLocale::translate('editor.article.decision.decline'),
			null => \OjsLocale::translate('plugins.reports.articles.nodecision')
		);

		$columns = array(
			'article_id' => \OjsLocale::translate('article.submissionId'),
			'title' => \OjsLocale::translate('article.title'),
			'abstract' => \OjsLocale::translate('article.abstract')
		);
			
		for ($a = 1; $a <= $maxAuthors; $a++) {
			$columns = array_merge($columns, array(
				'fname' . $a => \OjsLocale::translate('user.firstName') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'mname' . $a => \OjsLocale::translate('user.middleName') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'lname' . $a => \OjsLocale::translate('user.lastName') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'country' . $a => \OjsLocale::translate('common.country') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'affiliation' . $a => \OjsLocale::translate('user.affiliation') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'email' . $a => \OjsLocale::translate('user.email') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'url' . $a => \OjsLocale::translate('user.url') . " (" . \OjsLocale::translate('user.role.author') . " $a)",
				'biography' . $a => \OjsLocale::translate('user.biography') . " (" . \OjsLocale::translate('user.role.author') . " $a)"
			));
		}
			
		$columns = array_merge($columns, array(
			'section_title' => \OjsLocale::translate('section.title'),
			'language' => \OjsLocale::translate('common.language'),
			'editor_decision' => \OjsLocale::translate('submission.editorDecision'),
			'status' => \OjsLocale::translate('common.status'),
			'submission_date' =>\OjsLocale::translate('article.submissionDate'),
			'publication_date' =>\OjsLocale::translate('article.publicationDate')
		));

		$fp = fopen('php://output', 'wt');
		OjsString::fputcsv($fp, array_values($columns));

		import('classes.article.Article'); // Bring in getStatusMap function
		$statusMap =& Article::getStatusMap();

		$authorIndex = 0;
		while ($row =& $articlesIterator->next()) {
			$authors = $this->mergeAuthors($authorsIterator[$row['article_id']]->toArray());

			foreach ($columns as $index => $junk) {
				if ($index == 'editor_decision') {
					if (isset($decisions[$row['article_id']])) {
						$columns[$index] = $decisionMessages[$decisions[$row['article_id']]];
					} else {
						$columns[$index] = $decisionMessages[null];
					}
				} elseif ($index == 'status') {
					$columns[$index] = \OjsLocale::translate($statusMap[$row[$index]]);
				} elseif ($index == 'abstract') {
					$columns[$index] = html_entity_decode(strip_tags($row[$index]));
				} elseif (strstr($index, 'biography') !== false) {
					// "Convert" HTML to text for export
					$columns[$index] = isset($authors[$index])?html_entity_decode(strip_tags($authors[$index])):'';
				} else {
					if (isset($row[$index])) {
						$columns[$index] = $row[$index];
					} else if (isset($authors[$index])) {
						$columns[$index] = $authors[$index];
					} else $columns[$index] = '';
				}
			}
			OjsString::fputcsv($fp, $columns);
			unset($row);
			$authorIndex++;
		}
		
		fclose($fp);
	}
	
	/**
	 * Get the highest author count for any article (to determine how many columns to set)
	 * @param $authorsIterator DBRowIterator
	 * @return int
	 */
	function getMaxAuthorCount($authorsIterator) {
		$maxAuthors = 0;
		foreach ($authorsIterator as $authorIterator) {
			$maxAuthors = $authorIterator->getCount() > $maxAuthors ? $authorIterator->getCount() : $maxAuthors;
		}
		return $maxAuthors;
	}
	
	/**
	 * Flatten an array of author information into one array and append author sequence to each key
	 * @param $authors array
	 * @return array
	 */
	function mergeAuthors($authors) {
		$returner = array();
		$seq = 0;
		foreach($authors as $author) {
			$seq++;
			
			$returner['fname' . $seq] = isset($author['fname']) ? $author['fname'] : '';
			$returner['mname' . $seq] = isset($author['mname']) ? $author['mname'] : '';
			$returner['lname' . $seq] = isset($author['lname']) ? $author['lname'] : '';
			$returner['email' . $seq] = isset($author['email']) ? $author['email'] : '';
			$returner['affiliation'] = isset($author['affiliation']) ? $author['affiliation'] : '';
			$returner['country' . $seq] = isset($author['country']) ? $author['country'] : '';
			$returner['url' . $seq] = isset($author['url']) ? $author['url'] : '';
			$returner['biography' . $seq] = isset($author['biography']) ? $author['biography'] : '';
		}
		return $returner;
	}

}

?>
