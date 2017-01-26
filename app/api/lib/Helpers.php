<?php

namespace api\lib;

class Helpers {
	public function convertToJSON($data) {
		if(empty($data)) return false;

		$res = json_encode(
			$data
		);

		return $res;
	}

	public function convertToJSONPretty($data) {
		if(empty($data)) return false;

		$res = json_encode(
			$data,
			JSON_PRETTY_PRINT
		);

		return $res;
	}
}