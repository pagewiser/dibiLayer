<?php

namespace Pagewiser\DAL\Dibi;

/**
 * Abstract database filter
 */
abstract class Filter implements IFilter
{


	public function getDefinition()
	{
		return (array) $this;
	}


}
