<?php

namespace Diff;

/**
 * Differ that does an associative diff between two arrays,
 * with the option to do this recursively.
 *
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
 * @since 0.4
 *
 * @file
 * @ingroup Diff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapDiffer implements Differ {

	/**
	 * @var boolean
	 */
	protected $recursively;

	/**
	 * @var Differ
	 */
	protected $listDiffer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param bool $recursively
	 * @param Differ $listDiffer
	 */
	public function __construct( $recursively = false, Differ $listDiffer = null ) {
		$this->recursively = $recursively;

		if ( $listDiffer === null ) {
			$listDiffer = new ListDiffer();
		}

		$this->listDiffer = $listDiffer;
	}

	/**
	 * @see Differ::doDiff
	 *
	 * Computes the diff between two associate arrays.
	 *
	 * @since 0.4
	 *
	 * @param array $oldValues The first array
	 * @param array $newValues The second array
	 *
	 * @throws Exception
	 * @return DiffOp[]
	 */
	public function doDiff( array $oldValues, array $newValues ) {
		if ( function_exists( 'wfProfileIn' ) ) {
			wfProfileIn( __METHOD__ );
		}

		$newSet = $this->array_diff_assoc( $newValues, $oldValues );
		$oldSet = $this->array_diff_assoc( $oldValues, $newValues );

		$diffSet = array();

		foreach ( array_merge( array_keys( $oldSet ), array_keys( $newSet ) ) as $key ) {
			$hasOld = array_key_exists( $key, $oldSet );
			$hasNew = array_key_exists( $key, $newSet );

			if ( $this->recursively ) {
				if ( ( !$hasOld || is_array( $oldSet[$key] ) ) && ( !$hasNew || is_array( $newSet[$key] ) ) ) {

					$old = $hasOld ? $oldSet[$key] : array();
					$new = $hasNew ? $newSet[$key] : array();

					if ( $this->isAssociative( $old ) || $this->isAssociative( $new ) ) {
						$diff = new Diff( $this->doDiff( $old, $new ), true );
					}
					else {
						$diff = new Diff( $this->listDiffer->doDiff( $old, $new ), false );
					}

					if ( !$diff->isEmpty() ) {
						$diffSet[$key] = $diff;
					}

					continue;
				}
			}

			if ( $hasOld && $hasNew ) {
				$diffSet[$key] = new DiffOpChange( $oldSet[$key], $newSet[$key] );
			}
			elseif ( $hasOld ) {
				$diffSet[$key] = new DiffOpRemove( $oldSet[$key] );
			}
			elseif ( $hasNew ) {
				$diffSet[$key] = new DiffOpAdd( $newSet[$key] );
			}
		}

		if ( function_exists( 'wfProfileOut' ) ) {
			wfProfileOut( __METHOD__ );
		}

		return $diffSet;
	}

	/**
	 * Returns if an array is associative or not.
	 *
	 * @since 0.4
	 *
	 * @param array $array
	 *
	 * @return boolean
	 */
	protected function isAssociative( array $array ) {
		return $array !== array() && array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * Similar to the native array_diff_assoc function, except that it will
	 * spot differences between array values. Very weird the native
	 * function just ignores these...
	 *
	 * @see http://php.net/manual/en/function.array-diff-assoc.php
	 *
	 * @since 0.4
	 *
	 * @param array $from
	 * @param array $to
	 *
	 * @return array
	 */
	protected function array_diff_assoc( array $from, array $to ) {
		$diff = array();

		foreach ( $from as $key => $value ) {
			if ( !array_key_exists( $key, $to ) || $to[$key] !== $value ) {
				$diff[$key] = $value;
			}
		}

		return $diff;
	}

}