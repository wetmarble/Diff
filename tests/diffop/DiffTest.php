<?php

namespace Diff\Test;
use Diff\Diff;
use Diff\DiffOp;
use Diff\MapDiff;
use Diff\ListDiff;
use Diff\DiffOpAdd;
use Diff\DiffOpRemove;
use Diff\DiffOpChange;

/**
 * Tests for the Diff\Diff class.
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
 * @file
 * @since 0.1
 *
 * @ingroup DiffTest
 *
 * @group Diff
 * @group DiffOp
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffTest extends \GenericArrayObjectTest {

	public function elementInstancesProvider() {
		return array(
			array( array(
			) ),
			array( array(
				new DiffOpAdd( 'ohi' )
			) ),
			array( array(
				new DiffOpRemove( 'ohi' )
			) ),
			array( array(
				new DiffOpAdd( 'ohi' ),
				new DiffOpRemove( 'there' )
			) ),
			array( array(
			) ),
			array( array(
				new DiffOpAdd( 'ohi' ),
				new DiffOpRemove( 'there' ),
				new DiffOpChange( 'ohi', 'there' )
			) ),
			array( array(
				'1' => new DiffOpAdd( 'ohi' ),
				'33' => new DiffOpRemove( 'there' ),
				'7' => new DiffOpChange( 'ohi', 'there' )
			) ),
		);
	}

	/**
	 * @dataProvider elementInstancesProvider
	 */
	public function testGetAdditions( array $operations ) {
		$diff = new MapDiff( $operations );

		$additions = array();

		/**
		 * @var DiffOp $operation
		 */
		foreach ( $operations as $operation ) {
			if ( $operation->getType() == 'add' ) {
				$additions[] = $operation;
			}
		}

		$this->assertArrayEquals( $additions, $diff->getAdditions() );
	}

	/**
	 * @dataProvider elementInstancesProvider
	 */
	public function testGetRemovals( array $operations ) {
		$diff = new MapDiff( $operations );

		$removals = array();

		/**
		 * @var DiffOp $operation
		 */
		foreach ( $operations as $operation ) {
			if ( $operation->getType() == 'remove' ) {
				$removals[] = $operation;
			}
		}

		$this->assertArrayEquals( $removals, $diff->getRemovals() );
	}

	public function testGetType() {
		$diff = new Diff();
		$this->assertInternalType( 'string', $diff->getType() );
	}

	public function testPreSetElement() {
		$pokemons = null;

		$diff = new Diff( array(), false );

		try {
			$diff[] = new DiffOpChange( 0, 1 );
		}
		catch( \Exception $pokemons ) {}

		$this->assertInstanceOf( '\Exception', $pokemons );
	}

	/**
	 * @dataProvider elementInstancesProvider
	 */
	public function testAddOperations( array $operations ) {
		$diff = new Diff();

		$diff->addOperations( $operations );

		$this->assertArrayEquals( $operations, $diff->getOperations() );
	}

	/**
	 * @dataProvider elementInstancesProvider
	 */
	public function testStuff( array $operations ) {
		$diff = new Diff( $operations );

		$this->assertInstanceOf( '\Diff\Diff', $diff );
		$this->assertInstanceOf( '\ArrayObject', $diff );

		$types = array();

		foreach ( $diff as $operation ) {
			$this->assertInstanceOf( '\Diff\DiffOp', $operation );
			if ( !in_array( $operation->getType(), $types ) ) {
				$types[] = $operation->getType();
			}
		}

		$count = 0;

		foreach ( $types as $type ) {
			$count += count( $diff->getTypeOperations( $type ) );
		}

		$this->assertEquals( $count, $diff->count() );
	}

	public function instanceProvider() {
		$instances = array();

		foreach ( $this->elementInstancesProvider() as $args ) {
			$diffOps = $args[0];
			$instances[] = array( new Diff( $diffOps ) );
		}

		return $instances;
	}

	public function getInstanceClass() {
		return '\Diff\Diff';
	}

	public function getApplicableDiffProvider() {
		// Diff, current object, expected
		$argLists = array();

		$diff = new Diff();
		$currentObject = array();
		$expected = clone $diff;

		$argLists[] = array( $diff, $currentObject, $expected, 'Empty diff should remain empty on empty base' );


		$diff = new Diff( array(), true );

		$currentObject = array( 'foo' => 0, 'bar' => 1 );

		$expected = clone $diff;

		$argLists[] = array( $diff, $currentObject, $expected, 'Empty diff should remain empty on non-empty base' );


		$diff = new Diff( array(
			'foo' => new DiffOpChange( 0, 42 ),
			'bar' => new DiffOpChange( 1, 9001 ),
		), true );

		$currentObject = array( 'foo' => 0, 'bar' => 1 );

		$expected = clone $diff;

		$argLists[] = array( $diff, $currentObject, $expected, 'Diff should not be altered on matching base' );


		$diff = new MapDiff( array(
			'foo' => new DiffOpChange( 0, 42 ),
			'bar' => new DiffOpChange( 1, 9001 ),
		) );
		$currentObject = array();

		$expected = new Diff( array(), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Diff with only change ops should be empty on empty base' );


		$diff = new Diff( array(
			'foo' => new DiffOpChange( 0, 42 ),
			'bar' => new DiffOpChange( 1, 9001 ),
		), true );

		$currentObject = array( 'foo' => 'something else', 'bar' => 1, 'baz' => 'o_O' );

		$expected = new Diff( array(
			'bar' => new DiffOpChange( 1, 9001 ),
		), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Only change ops present in the base should be retained' );


		$diff = new Diff( array(
			'bar' => new DiffOpRemove( 9001 ),
		), true );

		$currentObject = array();

		$expected = new Diff( array(), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Remove ops should be removed on empty base' );


		$diff = new Diff( array(
			'foo' => new DiffOpAdd( 42 ),
			'bar' => new DiffOpRemove( 9001 ),
		), true );

		$currentObject = array( 'foo' => 'bar' );

		$expected = new Diff( array(), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Mismatching add ops and remove ops not present in base should be removed' );


		$diff = new Diff( array(
			'foo' => new DiffOpAdd( 42 ),
			'bar' => new DiffOpRemove( 9001 ),
		), true );

		$currentObject = array( 'foo' => 42, 'bar' => 9001 );

		$expected = new Diff( array(
			'bar' => new DiffOpRemove( 9001 ),
		), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Remove ops present in base should be retained' );


		$diff = new Diff( array(
			'foo' => new DiffOpAdd( 42 ),
			'bar' => new DiffOpRemove( 9001 ),
		), true );

		$currentObject = array();

		$expected = new Diff( array(
			'foo' => new DiffOpAdd( 42 ),
		), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Add ops not present in the base should be retained (MapDiff)' );


		$diff = new Diff( array(
			new DiffOpAdd( 42 ),
			new DiffOpRemove( 9001 ),
		), false );

		$currentObject = array();

		$expected = new Diff( array(
			new DiffOpAdd( 42 ),
		), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Add ops not present in the base should be retained (ListDiff)' );


		$diff = new Diff( array(
			new DiffOpAdd( 42 ),
			new DiffOpRemove( 9001 ),
		), false );

		$currentObject = array( 1, 42, 9001 );

		$expected = new Diff( array(
			new DiffOpAdd( 42 ),
			new DiffOpRemove( 9001 ),
		), false );

		$argLists[] = array( $diff, $currentObject, $expected, 'Add ops with values present in the base should be retained in ListDiff' );


		$diff = new Diff( array(
			'foo' => new Diff( array( 'bar' => new DiffOpChange( 0, 1 ) ), true ),
			'le-non-existing-element' => new Diff( array( 'bar' => new DiffOpChange( 0, 1 ) ), true ),
			'spam' => new Diff( array( new DiffOpAdd( 42 ) ), false ),
			new DiffOpAdd( 9001 ),
		), true );

		$currentObject = array(
			'foo' => array( 'bar' => 0, 'baz' => 'O_o' ),
			'spam' => array( 23, 'ohi' )
		);

		$expected = new Diff( array(
			'foo' => new Diff( array( 'bar' => new DiffOpChange( 0, 1 ) ), true ),
			'spam' => new Diff( array( new DiffOpAdd( 42 ) ), false ),
			new DiffOpAdd( 9001 ),
		), true );

		$argLists[] = array( $diff, $currentObject, $expected, 'Recursion should work properly' );


		$diff = new Diff( array(
			'en' => new Diff( array( new DiffOpAdd( 42 ) ), false ),
		), true );

		$currentObject = array();

		$expected = clone $diff;

		$argLists[] = array( $diff, $currentObject, $expected, 'list diffs containing only add ops should be retained even when not in the base' );


		$diff = new Diff( array(
			'en' => new Diff( array( new DiffOpRemove( 42 ) ), false ),
		), true );

		$currentObject = array(
			'en' => array( 42 ),
		);

		$expected = clone $diff;

		$argLists[] = array( $diff, $currentObject, $expected, 'list diffs containing only remove ops should be retained when present in the base' );

		return $argLists;
	}

	/**
	 * @dataProvider getApplicableDiffProvider
	 *
	 * @param Diff $diff
	 * @param array $currentObject
	 * @param Diff $expected
	 * @param string|null $message
	 */
	public function testGetApplicableDiff( Diff $diff, array $currentObject, Diff $expected, $message = null ) {
		$actual = $diff->getApplicableDiff( $currentObject );

		$this->assertEquals( $expected->getOperations(), $actual->getOperations(), $message );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetOperations( Diff $diff ) {
		$ops = $diff->getOperations();

		$this->assertInternalType( 'array', $ops );

		foreach ( $ops as $diffOp ) {
			$this->assertInstanceOf( '\Diff\DiffOp', $diffOp );
		}

		$this->assertArrayEquals( $ops, $diff->getOperations() );
	}

	public function testRemoveEmptyOperations() {
		$diff = new Diff( array() );

		$diff['foo'] = new DiffOpAdd( 1 );
		$diff['bar'] = new Diff( array( new DiffOpAdd( 1 ) ), true );
		$diff['baz'] = new Diff( array( new DiffOpAdd( 1 ) ), false );
		$diff['bah'] = new Diff( array(), false );
		$diff['spam'] = new Diff( array(), true );

		$diff->removeEmptyOperations();

		$this->assertTrue( $diff->offsetExists( 'foo' ) );
		$this->assertTrue( $diff->offsetExists( 'bar' ) );
		$this->assertTrue( $diff->offsetExists( 'baz' ) );
		$this->assertFalse( $diff->offsetExists( 'bah' ) );
		$this->assertFalse( $diff->offsetExists( 'spam' ) );
	}

}
	