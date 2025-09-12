<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Log;
use CustomHelper;
use Illuminate\Http\Request;

class TableHelper
{
	public static function transform_headers_readonly($array)
	{
		$result = array();
		foreach ($array as $key => $value) {
			$result[] = array('data' => $key, "attr" => "align=center", 'title' => $value, 'sortable' => $value != '', 'switchable' => !preg_match('(^$|&nbsp)', $value));
		}

		return json_encode($result);
	}

	public static function transform_headers($array, $detail = FALSE, $readonly = FALSE,  $editable = TRUE, $delete = FALSE, $actions = FALSE)
	{
		//print_r($actions); exit;
		$result = [];

		if (!$readonly) {
			$array = array_merge(array(array('checkbox' => 'select', 'sortable' => FALSE)), $array);
		}
		// if ($actions) {
		// 	$array[] = array('actions' => '', 'width' => '20');
		// }
		// if ($editable) {
		// 	$array[] = array('edit' => '', 'width' => '20', 'bVisible' => true);
		// }

		// if ($detail) {
		// 	$array[] = array('details' => '', 'width' => '20', 'bVisible' => true);
		// }
		// if ($delete) {
		// 	$array[] = array('delete' => '', 'width' => '20', 'bVisible' => true);
		// }


		foreach ($array as $element) {
			reset($element);
			$result[] = array(
				'data' => key($element),
				'title' => current($element),
				'switchable' => isset($element['switchable']) ?
					$element['switchable'] : !preg_match('(^$|&nbsp)', current($element)),
				'sortable' => isset($element['sortable']) ?
					$element['sortable'] : current($element) != '',
				'checkbox' => isset($element['checkbox']) ?
					$element['checkbox'] : FALSE,
				'class' => isset($element['checkbox']) || preg_match('(^$|&nbsp)', current($element)) ?
					'print_hide' : '',
				'sorter' => isset($element['sorter']) ?
					$element['sorter'] : '',
				'width' => isset($element['width']) ? $element['width'] : 20,
				'type' => isset($element['type']) ? $element['type'] : '',
				'className' => isset($element['className']) ? $element['className'] : '',
				// 'bVisible' => isset($element['bVisible']) ? $element['bVisible'] : false,

			);
		}
		return json_encode($result);
	}

	public static function get_manage_table_headers($table_headers, $detail, $readonly = false, $editable = false, $delete = false, $actions = false)
	{
		$instance = new self();
		return $instance->transform_headers($table_headers, $detail, $readonly, $editable, $delete, $actions);
	}

	public static function is_edit_permitted()
	{
		$request = new Request();
		$grantedactionsArray = session()->get('grantedactionsArray');
		//$controller_name = strtolower(get_class($CI));
		$controller_name = $request->segment(1);

		if (isset($grantedactionsArray[$controller_name]) && in_array('EDIT', $grantedactionsArray[$controller_name])) {
			return true;
		}
		return false;
	}
}