<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;

/**
 * Array Tool Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * This is a helper or service class containing frequently used
 * array functions for dealing with SQL queries.
 * 
 * @file    RedBeanPHP/Util/ArrayTool.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ArrayTool
{
	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array  $array array to generate question mark slots for
	 *
	 * @return string
	 */
	public static function genSlots( $array, $template = NULL )
	{
		$str = count( $array ) ? implode( ',', array_fill( 0, count( $array ), '?' ) ) : '';
		return ( is_null( $template ) ||  $str === '' ) ? $str : sprintf( $template, $str );
	}

	/**
	 * Flattens a multi dimensional bindings array for use with genSlots().
	 *
	 * @param array $array  array to flatten
	 * @param array $result result array parameter (for recursion)
	 *
	 * @return array
	 */
	public static function flat( $array, $result = array() )
	{		
		foreach( $array as $value ) {
			if ( is_array( $value ) ) $result = self::flat( $value, $result );
			else $result[] = $value;
		}
		return $result;
	}
}
