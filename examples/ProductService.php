<?php

namespace Pagewiser\Examples;

/**
 * Product service
 */
class ProductService extends \Ecp\Model\Database\AbstractDatabaseService
{

	protected $tableName = 'products';

	protected $currency;

	protected $setup = array(
		'onlyActive' => FALSE,
	);

	protected $slugCache = array();


	public function setup($setup)
	{
		$this->setup = array_merge($this->setup, $setup);
	}


	public function __construct(\DibiConnection $dibi, \Ecp\ProductModule\Model\Currency $currency = NULL)
	{
		parent::__construct($dibi);

		$this->currency = $currency;
	}


	/**
	 * Create product filter object
	 *
	 * @return \Ecp\ProductModule\Model\ProductFilter Product filter
	 */
	public function createFilter()
	{
		return new ProductFilter;
	}


	/**
	 * Base query with joined tables
	 *
	 * @return \DibiFluent
	 */
	public function baseQuery()
	{
		$query = parent::baseQuery();

		if (is_object($this->currency) && $this->currency->id > 0)
		{
			if ($this->setup['onlyActive'] == TRUE)
			{
				$query->innerJoin('product_prices');
				$query->on('`product_prices.product_id` = `products.id` AND `product_prices.currency_id` = %i AND product_prices.price > 0', $this->currency->id);
			}
			else
			{
				$query->leftJoin('product_prices');
				$query->on('`product_prices.product_id` = `products.id` AND `product_prices.currency_id` = %i', $this->currency->id);
			}

			if ($this->currency->getHasVat())
			{
				$query->select('`product_prices.price`, `product_prices.rrp`');
			}
			else
			{
				$query->select('ROUND(ROUND(`product_prices`.`price` / 121 * 100) / 5) * 5 AS `price`,
				ROUND(ROUND(`product_prices`.`rrp` / 121 * 100) / 5) * 5 AS `rrp`');
			}
		}

		if ($this->setup['onlyActive'] == TRUE)
		{
			$query->where('`products.enabled` = 1');
		}

		$query->orderBy('`products`.`id` DESC');

		return $query;
	}


	/**
	 * Search products by product filter
	 *
	 * @param ProductFilter $filter Product filter
	 */
	public function search(\Ecp\ProductModule\Model\ProductFilter $filter = NULL, \Nette\Utils\Paginator $paging = NULL)
	{
		$query = $this->baseQuery();

		if (!empty($filter->sku))
		{
			$query->where('`products`.`sku` = %s', $filter->sku);
		}

		if (!empty($filter->brandId))
		{
			$query->where('`products`.`brand_id` = %i', $filter->brandId);
		}

		if (!empty($filter->productId))
		{
			$query->where('`products`.`id` = %i', $filter->productId);
		}

		if (!empty($filter->name))
		{
			$query->where('`products`.`name` like %~like~', $filter->name);
		}

		if (!empty($paging))
		{
			$query->limit($paging->getItemsPerPage())->offset($paging->getOffset());
		}
		else
		{
			$query->limit(10);
		}

		return $query->fetchAll();
	}


	/**
	 * Search products by product filter
	 *
	 * @param ProductFilter $filter Product filter
	 */
	public function searchCount(\Ecp\ProductModule\Model\ProductFilter $filter = NULL)
	{
		$query = $this->baseQuery();

		if (!empty($filter->sku))
		{
			$query->where('`products`.`sku` = %s', $filter->sku);
		}

		if (!empty($filter->brandId))
		{
			$query->where('`products`.`brand_id` = %i', $filter->brandId);
		}

		if (!empty($filter->productId))
		{
			$query->where('`products`.`id` = %i', $filter->productId);
		}

		if (!empty($filter->name))
		{
			$query->where('`products`.`name` like %~like~', $filter->name);
		}

		$query->select(FALSE)->select('COUNT(*)');

		return $query->fetchSingle();
	}


	public function insert($data)
	{
		if (empty($data['slug']))
		{
			$data['slug'] = \Nette\Utils\Strings::webalize($data['name']);
		}

		$slugs = $this->db->select('id, slug')->from($this->tableName)
			->where('`slug` LIKE %like~', $data['slug'])
			->fetchPairs('slug', 'id');

		if (isset($slugs[$data['slug']]))
		{
			for ($i = 1; $i < 100; $i++)
			{
				$newSlug = \Nette\Utils\Strings::webalize($data['slug'].' '.$i);
				if (!isset($slugs[$newSlug]))
				{
					$data['slug'] = $newSlug;
					break;
				}
			}
		}

		return parent::insert($data);
	}


	public function update($id, $data)
	{
		if (empty($data['stock']) && $this->hasVariants($id))
		{
			$data['stock'] = $this->getVariantStock($id);
		}

		return parent::update($id, $data);
	}


	public function slugIn($url)
	{
		return $this->_slugIn($url);
	}


	public function slugOut($id)
	{
		return $this->_slugOut($id);
	}


}
