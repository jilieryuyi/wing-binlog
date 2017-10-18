<?php namespace Wing\Library\Mysql;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/20
 * Time: 15:38
 */
class Stmt
{
	//Get current field offset of a result pointer
	public $current_field;

	//Get the number of fields in a result
	public $field_count;

	//Returns the lengths of the columns of the current row in the result set
	public $lengths;

	//Gets the number of rows in a result
	public $num_rows;

	//Adjusts the result pointer to an arbitrary row in the result
	public function data_seek(){}

	//Fetches all result rows as an associative array, a numeric array, or both
	public function fetch_all(){}

	//Fetch a result row as an associative, a numeric array, or both
	public function fetch_array(){}

	//Fetch a result row as an associative array
	public function fetch_assoc(){}

	//Fetch meta-data for a single field
	public function fetch_field_direct(){}

	//Returns the next field in the result set
	public function fetch_field(){}

	//Returns an array of objects representing the fields in a result set
	public function fetch_fields(){}

	//Returns the current row of a result set as an object
	public function fetch_object(){}

	//Get a result row as an enumerated array
	public function fetch_row(){}

	//Set result pointer to a specified field offset
	public function field_seek(){}

	//Frees the memory associated with a result
	public function free(){}
}