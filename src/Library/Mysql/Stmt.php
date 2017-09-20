<?php namespace Wing\Library\Mysql;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/20
 * Time: 15:38
 */
class Stmt
{
public $current_field;//(){}//Get current field offset of a result pointer
    public $field_count;//(){}//Get the number of fields in a result
    public $lengths;//(){}//Returns the lengths of the columns of the current row in the result set
    public $num_rows;//(){}//Gets the number of rows in a result

    public function data_seek(){}//Adjusts the result pointer to an arbitrary row in the result
public function fetch_all(){}//Fetches all result rows as an associative array, a numeric array, or both
public function fetch_array(){}//Fetch a result row as an associative, a numeric array, or both
public function fetch_assoc(){}//Fetch a result row as an associative array
public function fetch_field_direct(){}//Fetch meta-data for a single field
public function fetch_field(){}//Returns the next field in the result set
public function fetch_fields(){}//Returns an array of objects representing the fields in a result set
public function fetch_object(){}//Returns the current row of a result set as an object
public function fetch_row(){}//Get a result row as an enumerated array
public function field_seek(){}//Set result pointer to a specified field offset
public function free(){}//Frees the memory associated with a result
}