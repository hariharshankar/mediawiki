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
 * This class implements the header alteration and entity alteration functions
 * directly accessed Mementos, regardless of Time Negotiation style.
 *
 * This class is for the directly accessed URI-M mentioned in the Memento RFC.
 */
class MementoResourceDirectlyAccessed extends MementoResource {

	/**
	 * alterHeaders
	 *
	 * Put the Memento headers in place for this directly accessed Memento.
	 */
	public function alterHeaders() {

		$out = $this->article->getContext()->getOutput();
		$request = $out->getRequest();
		$response = $request->response();
		$titleObj = $this->article->getTitle();

		$linkEntries = array();

		// if we exclude this Namespace, don't show folks Memento relations
		if ( in_array( $titleObj->getNamespace(),
			$this->conf->get('ExcludeNamespaces') ) ) {

			$entry = '<http://mementoweb.org/terms/donotnegotiate>; rel="type"';
			array_push( $linkEntries, $entry );
		} else {
			$title = $this->getFullNamespacePageTitle( $titleObj );
			$pageID = $titleObj->getArticleID();
			$oldID = $this->article->getOldID();

			$mementoInfo = $this->getInfoForThisMemento( $this->dbr, $oldID );
			$mementoInfoID = $mementoInfo['id'];
			$mementoDatetime = $mementoInfo['timestamp'];

			$memento = $this->convertRevisionData( $this->mwrelurl,
				$this->getCurrentMemento(
					$this->dbr, $mementoInfoID, $mementoDatetime ),
				$title );

			$myuri = $this->getSafelyFormedURI( $this->mwrelurl, $title );

			$entry = $this->constructLinkRelationHeader( $myuri,
					'original latest-version timegate' );
			array_push( $linkEntries, $entry );

			if ( $this->conf->get('RecommendedRelations') ) {

				$first = $this->convertRevisionData( $this->mwrelurl,
					$this->getFirstMemento( $this->dbr, $pageID ),
					$title );

				$last = $this->convertRevisionData( $this->mwrelurl,
					$this->getLastMemento( $this->dbr, $pageID ),
					$title );

				$next = $this->convertRevisionData( $this->mwrelurl,
					$this->getNextMemento(
						$this->dbr, $pageID, $mementoDatetime ),
					$title );

				$prev = $this->convertRevisionData( $this->mwrelurl,
					$this->getPrevMemento(
						$this->dbr, $pageID, $mementoDatetime ),
					$title );

				$entry = $this->constructTimeMapLinkHeaderWithBounds(
						$this->mwrelurl, $title,
						$first['dt'], $last['dt'] );

				array_push( $linkEntries, $entry );

				$linkEntries = implode( ',', $linkEntries );

				// TODO: rewrite this function... somehow
				// so we can move the implode to the end of this function
				$linkEntries .= ',' . $this->constructLinkHeader(
					$first, $last, $memento, $next, $prev );
				$linkEntries = rtrim( $linkEntries, ', ' );

			} else  {
				$entry = $this->constructTimeMapLinkHeader(
					$this->mwrelurl, $title );
				array_push( $linkEntries, $entry );

				$entry = $this->constructMementoLinkHeaderEntry(
					$this->mwrelurl, $title, $oldID,
					$memento['dt'], 'memento' );
				array_push( $linkEntries, $entry );
				$linkEntries = implode( ',', $linkEntries );
			}


			// convert for display
			$mementoDatetime = wfTimestamp( TS_RFC2822, $mementoDatetime );

			$response->header( "Memento-Datetime:  $mementoDatetime", true );
		}

		$response->header( "Link: $linkEntries", true );
	}

	/**
	 * alterEntity
	 *
	 * No entity alterations are necessary for directly accessed Mementos.
	 *
	 */
	public function alterEntity() {
		// do nothing to the body
	}
}