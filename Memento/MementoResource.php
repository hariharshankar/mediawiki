<?php
/**
 * This file is part of the Memento Extension to MediaWiki
 * http://www.mediawiki.org/wiki/Extension:Memento
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 */

/**
 * Ensure that this file is only executed in the right context.
 *

 */
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/**
 * This abstract class is the parent of all MementoResource types.
 * As such, it contains the methods used by all of the Memento Pages.
 * 
 */
abstract class MementoResource {

	/**
	 * @var object $conf: configuration object for Memento Extension
	 */
	protected $conf;

	/**
	 * @var object $db: DatabaseBase object for Memento Extension
	 */
	protected $db;

	/**
	 * @var $article - Article Object of this Resource
	 */
	protected $article;

	/**
	 * @var $mementoOldID - timestamp of the Memento
	 */
	protected $mementoOldID;

	/**
	 * getArticleObject
	 *
	 * Getter for Article Object used in constructor.
	 *
	 * @return Article $article
	 */
	public function getArticleObject() {
		return $this->article;
	}

	/**
	 * getConfig
	 *
	 * Getter for MementoConfig object used in constructor.
	 *
	 * @return MementoConfig $config
	 */
	public function getConfig() {
		return $this->conf;
	}

	/**
	 * fetchMementoFromDatabase
	 *
	 * Make the actual database call.
	 *
	 * @param $sqlCondition - the conditional statement
	 * @param $sqlOrder - order of the data returned (e.g. ASC, DESC)
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function fetchMementoFromDatabase( $sqlCondition, $sqlOrder ) {

		$db = $this->db;

		$results = $db->select(
			'revision',
			array( 'rev_id', 'rev_timestamp'),
			$sqlCondition,
			__METHOD__,
			array( 'ORDER BY' => $sqlOrder, 'LIMIT' => '1' )
			);

		$row = $db->fetchObject( $results );

		$revision = array();

		if ($row) {
			$revision['id'] = $row->rev_id;
			$revision['timestamp'] = wfTimestamp(
				TS_RFC2822, $row->rev_timestamp );
		}

		return $revision;

	}

	/**
	 * getFirstMemento
	 *
	 * Extract the first memento from the database.
	 *
	 * @param $title - title object
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getFirstMemento( $title ) {
		$revision = array();

		$firstRevision = $title->getFirstRevision();

		$revision['timestamp'] =
			wfTimestamp( TS_RFC2822, $firstRevision->getTimestamp());
		$revision['id'] = $firstRevision->getId();

		return $revision;
	}

	/**
	 * getLastMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $title - title object
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getLastMemento( $title ) {

		$revision = array();

		$lastRevision = WikiPage::factory( $title )->getRevision();

		$revision['timestamp'] =
			wfTimestamp( TS_RFC2822, $lastRevision->getTimestamp());
		$revision['id'] = $lastRevision->getId();

		return $revision;
	}

	/**
	 * getCurrentMemento
	 *
	 * Extract the memento that best matches from the database.
	 *
	 * @param $db - DatabaseBase object
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getCurrentMemento( $pageID, $pageTimestamp ) {

		$db = $this->db;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<=' . $db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getNextMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getNextMemento( $pageID, $pageTimestamp ) {

		$db = $this->db;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp>' . $db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp ASC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * getPrevMemento
	 *
	 * Extract the last memento from the database.
	 *
	 * @param $pageID - page identifier
	 * @param $pageTimestamp - timestamp used for finding the last memento
	 *
	 * @return $revision - associative array with id and timestamp keys
	 */
	public function getPrevMemento( $pageID, $pageTimestamp ) {

		$db = $this->db;

		$sqlCondition =
			array(
				'rev_page' => $pageID,
				'rev_timestamp<' . $db->addQuotes( $pageTimestamp )
				);
		$sqlOrder = 'rev_timestamp DESC';

		return $this->fetchMementoFromDatabase(
			$sqlCondition, $sqlOrder );
	}

	/**
	 * parseRequestDateTime
	 *
	 * Take in the RFC2822 datetime and convert it to the format used by
	 * Mediawiki.
	 *
	 * @param $requestDateTime
	 *
	 * @return $dt - datetime in mediawiki database format
	 */
	public function parseRequestDateTime( $requestDateTime ) {

		$reqDT = str_replace( '"', '', $requestDateTime );

		$dt = wfTimestamp( TS_MW, $reqDT );

		return $dt;
	}

	/**
	 * chooseBestTimestamp
	 *
	 * If the requested time is earlier than the first memento,
	 * the first memento will be returned.
	 * If the requested time is past the last memento, or in the future,
	 * the last memento will be returned.
	 * Otherwise, go with the one we've got because the future database call
	 * will get the nearest memento.
	 *
	 * @param $firstTimestamp - the first timestamp for which we have a memento
	 *				formatted in the TS_MW format
	 * @param $lastTimestamp - the last timestamp for which we have a memento
	 * @param $givenTimestamp - the timestamp given by the request header
	 *
	 * @return $chosenTimestamp - the timestamp to use
	 */
	public function chooseBestTimestamp(
		$firstTimestamp, $lastTimestamp, $givenTimestamp ) {

		$firstTimestamp = wfTimestamp( TS_MW, $firstTimestamp );
		$lastTimestamp = wfTimestamp( TS_MW, $lastTimestamp );

		$chosenTimestamp = null;

		if ( $givenTimestamp < $firstTimestamp ) {
			$chosenTimestamp = $firstTimestamp;
		} elseif ( $givenTimestamp > $lastTimestamp ) {
			$chosenTimestamp = $lastTimestamp;
		} else {
			$chosenTimestamp = $givenTimestamp;
		}

		return $chosenTimestamp;
	}

	/**
	 * constructMementoLinkHeaderRelationEntry
	 *
	 * This creates the entry for a memento for the Link Header.
	 *
	 * @param $url - the URL of the given page
	 * @param $timestamp - the timestamp of this Memento
	 * @param $relation - the relation type of this Memento
	 *
	 * @return $entry - full Memenot Link header entry
	 */
	public function constructMementoLinkHeaderRelationEntry(
		$url, $timestamp, $relation ) {

		$entry = '<' . $url . '>; rel="' . $relation . '"; datetime="' .
			$timestamp . '"';

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeaderWithBounds
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $title - the title string of the given page
	 * @param $from - the from timestamp for the TimeMap
	 * @param $until - the until timestamp for the TimeMap
	 *
	 * @return $entry - full Memento TimeMap relation with from and until
	 */
	public function constructTimeMapLinkHeaderWithBounds(
		$title, $from, $until ) {

		$entry = $this->constructTimeMapLinkHeader( $title );

		$entry .= "; from=\"$from\"; until=\"$until\"";

		return $entry;
	}

	/**
	 * constructTimeMapLinkHeader
	 *
	 * This creates the entry for timemap in the Link Header.
	 *
	 * @param $title - the title string of the given page
	 *
	 * @return $entry - Memento TimeMap relation
	 */
	public function constructTimeMapLinkHeader( $title ) {

		$uri = SpecialPage::getTitleFor( 'TimeMap', $title )->getFullURL();

		$entry = '<' . $uri .  '>; rel="timemap"; type="application/link-format"';

		return $entry;
	}

	/**
	 * getFullNamespacePageTitle
	 * 
	 * This function returns the namespace:title string from the URI
	 * corresponding to this resource.
	 *
	 * @param $titleObj - title object corresponding to this resource
	 *
	 * @return $title - the namespace:title string for the given page
	 */
	public function getFullNamespacePageTitle( $titleObj ) {
		$title = $titleObj->getDBkey();
		$namespace = $titleObj->getNsText();

		if ( $namespace ) {
			$title = "$namespace:$title";
		}

		return $title;
	}

	/**
	 * constructLinkRelationHeader
	 *
	 * This creates a link header entry for the given URI, with no
	 * extra information, just URL and relation.
	 *
	 * @param $url
	 * @param $relation
	 *
	 * @return relation string
	 */
	public function constructLinkRelationHeader( $url, $relation ) {
		return '<' . $url . '>; rel="' . $relation . '"';
	}

	/**
	 * generateRecommendedLinkHeaderRelations
	 *
	 * This function generates the recommended link header relations,
	 * handling cases such as 'first memento' and 'last memento' vs.
	 * 'first last memento', etc.
	 *
	 * @param $titleObj - the article title object
	 * @param $first - associative array containing info on the first memento
	 * 					with the keys 'timestamp' and 'id'
	 * @param $last	- associative array containing info on the last memento
	 * 					with the keys 'timestamp' and 'id'
	 *
	 * @return $linkRelations - array of link relations
	 */
	public function generateRecommendedLinkHeaderRelations(
		$titleObj, $first, $last ) {

		$linkRelations = array();

		$title = $this->getFullNamespacePageTitle( $titleObj );

		$entry = $this->constructTimeMapLinkHeaderWithBounds(
			$title, $first['timestamp'], $last['timestamp'] );
		array_push( $linkRelations, $entry );

		$firsturi = $titleObj->getFullURL( array( "oldid" => $first['id'] ) );
		$lasturi = $titleObj->getFullURL( array( "oldid" => $last['id'] ) );

		if ( $first['id'] == $last['id'] ) {
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$firsturi, $first['timestamp'], 'first last memento' );
			array_push( $linkRelations, $entry );
		} else {
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$firsturi, $first['timestamp'], 'first memento' );
			array_push( $linkRelations, $entry );
			$entry = $this->constructMementoLinkHeaderRelationEntry(
				$lasturi, $last['timestamp'], 'last memento' );
			array_push( $linkRelations, $entry );
		}

		return $linkRelations;
	}

	/**
	 * setMementoTimestamp
	 *
	 * Set the Memento Timestamp for future calls.
	 *
	 * @param $timestamp - the timestamp to set
	 */
	public function setMementoOldID( $id ) {
		$this->mementoOldID = $id;
	}

	/**
	 * getMementoTimestamp
	 *
	 * Get the Memento Timestamp
	 *
	 * @return $this->mementoOldID - the OldID stored previously
	 */
	public function getMementoOldID() {
		return $this->mementoOldID;
	}

	/**
	 * getTimeGateURI
	 *
	 * Get the URI for the TimeGate.
	 *
	 * @param $title - wiki page title text
	 *
	 * @return $uri
	 */
	public function getTimeGateURI( $title ) {

		if ( $this->conf->get('Negotiation') == '302' ) {
			// return Special Page URI
			$tguri = SpecialPage::getTitleFor( 'TimeGate', $title )->getFullURL();
		} else {
			// return myuri
			$tguri = $this->article->getTitle()->getFullURL();
		}

		return $tguri;
	}

	/**
	 * renderError
	 *
	 * Render error page.  This is only used for 40* and 50* HTTP statuses.
	 * This function is static so it can be called in cases where we have
	 * no MementoResource object.
	 *
	 * @param OutputPage $out
	 * @param MementoResourceException $error
	 * @param string $errorPageType - the error page type 'traditional' or 'friendly'
	 *
	 */
	public static function renderError( $out, $error, $errorPageType ) {
		if ( $errorPageType == 'traditional' ) {

			$msg = wfMessage(
				$error->getTextMessage(), $error->getParams()
				)->text();

			$error->getResponse()->header(
				"HTTP", true, $error->getStatusCode());

			echo $msg;

			$out->disable();
		} else {

			$out->showErrorPage(
				$error->getTitleMessage(),
				$error->getTextMessage(),
				$error->getParams()
				);
		}
	}

	/**
	 * mementoPageResourceFactory
	 *
	 * A factory for creating the correct MementoPageResource type.
	 *
	 * @param $conf - MementoConfig object, passed to constructor
	 * @param $db - DatabaseBase object, passed to constructor
	 * @param $oldID - string indicating revision ID
	 *		used in decision
	 *
	 * @return $resource - the correct instance of MementoResource based
	 *						on current conditions
	 */
	public static function mementoPageResourceFactory(
		$conf, $db, $article, $oldID, $request ) {

		$resource = null;

		if ( $oldID == 0 ) {

			if ( ( $request->getHeader('ACCEPT-DATETIME') ) &&
				( $conf->get('Negotiation') == "200" ) ) {
					/* we are requesting a Memento, but via 200-style
						Time Negotiation */
					$resource = new MementoResourceFrom200TimeNegotiation(
						$conf, $db, $article );

			} else {
				$resource = new OriginalResourceDirectlyAccessed(
						$conf, $db, $article );
			}

		} else {
			// we are requesting a Memento directly (an oldID URI)
			$resource = new MementoResourceDirectlyAccessed(
				$conf, $db, $article );
		}

		return $resource;
	}

	/**
	 * fixTemplate
	 *
	 * This code ensures that the version of the Template that was in existence
	 * at the same time as the Memento gets loaded and displayed with the
	 * Memento.
	 *
	 * @fixme make this compatible with parser cache
	 * @param Title $title
	 * @param Parser $parser
	 * @param int $id
	 *
	 * @return array containing the text, finalTitle, and deps
	 */
	public function fixTemplate( $title, $parser, &$id ) {

		$request = $parser->getUser()->getRequest();

		if ( $request->getHeader('ACCEPT-DATETIME') ) {

			$requestDatetime = $request->getHeader('ACCEPT-DATETIME');

			$mwMementoTimestamp = $this->parseRequestDateTime(
				$requestDatetime );

			$firstRev = $title->getFirstRevision();

			if ( $firstRev->getTimestamp() < $mwMementoTimestamp ) {

				$pgID = $title->getArticleID();

				$this->db->begin();

				$res = $this->db->select(
					'revision',
					array( 'rev_id' ),
					array(
						'rev_page' => $pgID,
						'rev_timestamp <=' .
							$this->db->addQuotes( $mwMementoTimestamp )
						),
					__METHOD__,
					array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => '1' )
				);

				if( $res ) {
					$row = $this->db->fetchObject( $res );
					$id = $row->rev_id;
				}
			} else {
				// if we get something prior to the first memento, just
				// go with the first one
				$id = $firstRev->getId();
			}
		}
	}

	/**
	 * Constructor for MementoResource and its children
	 * 
	 * @param $conf - configuration object
	 * @param $db - database object
	 * @param $article - article object
	 *
	 */
	public function __construct( $conf, $db, $article ) {

		$this->conf = $conf;
		$this->db = $db;
		$this->article = $article;

	}


	/**
	 * alterHeaders
	 *
	 * This function is used to alter the headers of the outgoing response,
	 * and must be implemented by the MementoResource implementation.
	 * It is expected to be called from the ArticleViewHeader hook.
	 *
	 */
	abstract public function alterHeaders();

	/**
	 * alterEntity
	 *
	 * This function is used to alter the entity of the outgoing response,
	 * and must be implemented by the MementoResource implementation.
	 * It is expected to be callsed from the BeforePageDisplay hook.
	 *
	 */
	abstract public function alterEntity();

}
