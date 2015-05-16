<?php

namespace Pagewiser\Examples;

/**
 * Product filter
 */
class ProductFilter extends \Pagewiser\DAL\Dibi\Filter
{

	/**
	 * @var int Product ID
	 */
	public $productId;

	/**
	 * @var string SKU
	 */
	public $sku;

	/**
	 * @var string Product name
	 */
	public $name;

	/**
	 * @var int Brand ID
	 */
	public $brandId;

}
