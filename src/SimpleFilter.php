<?php

namespace Pagewiser\DAL\Dibi;

/**
 * Simple database filter
 */
class SimpleFilter implements IFilter
{

	protected $filters = array();


	public function __get($key)
	{
		if (!array_key_exists($key, $this->filters))
		{
			return NULL;
		}

		return $this->filters[$key];
	}


	public function __set($key, $value)
	{
		$this->filters[$key] = $value;
	}


	public function getDefinition()
	{
		return $this->filters;
	}


}
