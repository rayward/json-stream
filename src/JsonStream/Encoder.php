<?php

namespace JsonStream;

class Encoder
{
	private $_stream;

	/**
	 * @param resource $stream A stream resource.
	 * @throws \InvalidArgumentException If $stream is not a stream resource.
	 */
	public function __construct($stream)
	{
		if (!is_resource($stream) || get_resource_type($stream) != 'stream') {
			throw new \InvalidArgumentException("Resource is not a stream");
		}

		$this->_stream = $stream;
	}

	/**
	 * Encodes a value and writes it to the stream.
	 *
	 * @param mixed $value
	 */
	public function encode($value)
	{
		// null, bool and scalar values
		if(is_null($value)) {
			$this->_writeValue('null');
			return;
		}
		elseif ($value === false) {
			$this->_writeValue('false');
			return;
		}
		elseif ($value === true) {
			$this->_writeValue('true');
			return;
		}
		elseif (is_scalar($value)) {
			$this->_encodeScalar($value);
			return;
		}

		// array of values
		if ($this->_isList($value)) {
			$this->_encodeList($value);
			return;
		}
		// objects and associative arrays
		else {
			$this->_encodeObject($value);
			return;
		}
	}

	/**
	 * Writes a value to the stream.
	 *
	 * @param string $value
	 */
	private function _writeValue($value)
	{
		fwrite($this->_stream, $value);
	}

	/**
	 * Encodes a scalar value.
	 *
	 * @param mixed $value
	 */
	private function _encodeScalar($value)
	{
		if (is_float($value)) {
			// Always use "." for floats.
			$encodedValue = floatval(str_replace(",", ".", strval($value)));
		}
		elseif (is_string($value)) {
			$encodedValue = $this->_encodeString($value);
		}
		else {
			// otherwise this must be an int
			$encodedValue = $value;
		}

		$this->_writeValue($encodedValue);
	}

	/**
	 * Encodes a string.
	 *
	 * @param $string
	 * @return string
	 */
	private function _encodeString($string)
	{
		static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "\0"), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', '\u0000'));
		return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $string) . '"';
	}

	/**
	 * Checks if a value is a flat list of values (simple array) or a map (assoc. array or object).
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private function _isList($value)
	{
		for($i = 0, reset($value); $i < count($value); $i++, next($value)) {
			if(key($value) !== $i) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Encodes a list of values.
	 *
	 * @param array $list
	 */
	private function _encodeList($list)
	{
		$this->_writeValue('[');

		foreach ($list as $x => $value) {
			$this->encode($value);

			if ($x < count($list) - 1) {
				$this->_writeValue(',');
			}
		}

		$this->_writeValue(']');
	}

	/**
	 * Encodes an object or associative array.
	 *
	 * @param mixed $list
	 */
	private function _encodeObject($list)
	{
		$this->_writeValue('{');

		$x = 1;
		foreach ($list as $key => $value) {
			$this->_encodeScalar((string)$key);
			$this->_writeValue(':');
			$this->encode($value);

			if ($x < count($list)) {
				$this->_writeValue(',');
			}

			$x++;
		}

		$this->_writeValue('}');
	}
}
