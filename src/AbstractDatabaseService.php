<?php

namespace Pagewiser\DAL\Dibi;

/**
 * Abstract class for all database services
 */
abstract class AbstractDatabaseService
{

	use \Nette\SmartObject;

	protected \Dibi\Connection $db;

	/**
	 * @var string $tableName Table name
	 */
	protected $tableName;

	/**
	 * @var array $buffer Short query cache
	 */
	protected $buffer = array();

	/**
	 * @var array $onSave Callbacks called when entity is saved
	 */
	public $onSave = array();

	/**
	 * @var array $onBeforeInsert Callbacks called before entity is inserted
	 */
	public $onBeforeInsert = array();

	/**
	 * @var array $onInserted Callbacks called after entity is inserted
	 */
	public $onInserted = array();

	/**
	 * @var array $onBeforeUpdate Callbacks called before entity is updated
	 */
	public $onBeforeUpdate = array();

	/**
	 * @var array $onUpdated Callbacks called after entity is updated
	 */
	public $onUpdated = array();

	/**
	 * @var array $onBeforeDelete Callbacks called before entity is deleted
	 */
	public $onBeforeDelete = array();

	/**
	 * @var array $onDeleted Callbacks called after entity is deleted
	 */
	public $onDeleted = array();

	protected $slugCache = array();


	/**
	 * Get name of ID column
	 *
	 * @return string Column name
	 */
	protected function getIdColumn()
	{
		return 'id';
	}


	public function getTableName()
	{
		return $this->tableName;
	}


	/**
	 * Create service instance and inject dibi connection
	 *
	 * @param \Dibi\Connection $db
	 */
	public function __construct(\Dibi\Connection $db)
	{
		if (empty($this->tableName))
		{
			throw new \Nette\InvalidStateException('Unknown table to select from.');
		}

		$this->db = $db;
		$this->onSave[] = array($this, 'cleanBuffer');
	}


	public function addEventListener(EventListener $eventListener)
	{
		$this->onBeforeInsert[] = array($eventListener, 'onBeforeInsert');
		$this->onInserted[] = array($eventListener, 'onInserted');
		$this->onBeforeUpdate[] = array($eventListener, 'onBeforeUpdate');
		$this->onUpdate[] = array($eventListener, 'onUpdate');
		$this->onBeforeDelete[] = array($eventListener, 'onBeforeDelete');
		$this->onDeleted[] = array($eventListener, 'onDeleted');
	}


	/**
	 * Create filter object
	 *
	 * @return \Pagewiser\DAL\Dibi\IFilter Filter
	 */
	public function createFilter()
	{
		return new SimpleFilter();
	}


	public function cleanBuffer()
	{
		$this->buffer = array();
	}


	/**
	 * Get base query with fluent interface
	 *
	 * @return \Dibi\Fluent
	 */
	public function baseQuery()
	{
		return $this->db->select('%n.*', $this->tableName)->from($this->tableName);
	}


	/**
	 * Get all records
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->baseQuery()->fetchAll();
	}


	/**
	 * Get number of all records
	 *
	 * @return int
	 */
	public function getCount()
	{
		return $this->baseQuery()->select(FALSE)->select('COUNT(*)')->fetchSingle();
	}


	/**
	 * Get record by id
	 *
	 * @param int $id Record ID
	 *
	 * @return mixed
	 */
	public function getById($id)
	{
		return $this->baseQuery()->where('%n.%n = %i', $this->tableName, $this->getIdColumn(), $id)->fetch();
	}


	public function insert($data)
	{
		if (isset($data[$this->getIdColumn()]))
		{
			throw new \InvalidArgumentException('Data contains database record ID value.');
		}

		$data = $this->remapStoreData($data);

		$this->onBeforeInsert($this, $data);
		$this->db->insert($this->tableName, $data)->execute();
		$id = $this->db->getInsertId();
		$this->onInserted($this, $id, $data);

		$this->onSave($this, $data, $id);

		return $id;
	}


	public function update($id, $data)
	{
		$data = $this->remapStoreData($data);
		unset($data[$this->getIdColumn()]);

		$this->onBeforeUpdate($this, $id, $data);
		$this->db->query('UPDATE %n', $this->tableName, ' SET ', $data, ' WHERE %n = %i', $this->getIdColumn(), $id);
		$this->onUpdated($this, $id, $data);

		$this->onSave($this, $data, $id);

		return TRUE;
	}


	protected function remapStoreData($data)
	{
		return $data;
	}


	/**
	 * Filter baseQuery by given filter
	 *
	 *
	 * @return \Dibi\Fluent
	 */
	public function filterQuery(\Pagewiser\DAL\Dibi\IFilter $filter = NULL)
	{
		$query = $this->baseQuery();

		if (is_object($filter) && (is_countable($filter->getDefinition()) ? count($filter->getDefinition()) : 0))
		{
			foreach ($filter->getDefinition() as $key => $value)
			{
				$this->filterColumn($query, $this->tableName, $key, $value);
			}
		}

		return $query;
	}


	protected function filterColumn($query, $table, $column, $value)
	{
		if (is_array($value))
		{
			$query->where('%n.%n IN %in', $this->tableName, $this->camelToUnderscore($column), $value);
		}
		else
		{
			$query->where('%n.%n = %s', $this->tableName, $this->camelToUnderscore($column), $value);
		}
	}


	/**
	 * Simple search by filter
	 *
	 * @param \Pagewiser\DAL\Dibi\IFilter $filter Filter
	 * @param \Nette\Utils\Paginator $paging Paging
	 *
	 * $return array Found records
	 */
	public function simpleSearch(\Pagewiser\DAL\Dibi\IFilter $filter = NULL, \Nette\Utils\Paginator $paging = NULL)
	{
		$query = $this->filterQuery($filter);

		if (isset($paging))
		{
			$query->limit($paging->getItemsPerPage())->offset($paging->getOffset());
		}

		return $query->fetchAll();
	}


	/**
	 * Get number of records by simple filter
	 *
	 * @param \Pagewiser\DAL\Dibi\IFilter $filter Filter
	 * @param \Nette\Utils\Paginator $paging Paging
	 *
	 * @return int Number of records
	 */
	public function simpleSearchCount(\Pagewiser\DAL\Dibi\IFilter $filter = NULL)
	{
		$query = $this->filterQuery($filter);

		$query->select(FALSE)->select('COUNT(*)');

		return $query->fetchSingle();
	}


	/**
	 * Delete record by ID
	 *
	 * @param int $id Record ID
	 *
	 * @return \DibiResult|int Result
	 */
	public function deleteById($id)
	{
		$result = $this->db->delete($this->tableName)->where('%n = %i', $this->getIdColumn(), $id)->execute();

		$this->onSave($this);

		return $result;
	}


	public function begin($savepoint = NULL)
	{
		return $this->db->begin($savepoint);
	}


	public function rollback($savepoint = NULL)
	{
		return $this->db->rollback($savepoint);
	}


	public function commit($savepoint = NULL)
	{
		return $this->db->commit($savepoint);
	}


	protected function _slugIn($url)
	{
		if (str_contains($url, '~'))
		{
			return substr($url, 0, strpos($url, '~'));
		}

		return $this->db->query('SELECT `id` FROM', $this->tableName, 'WHERE `slug` = %s', $url)->fetchSingle();
	}


	protected function _slugOut($id)
	{
		if ((is_iterable($id)))
		{
			$slug = $id['slug'];
			$id = $id['id'];
		}

		if (!empty($this->slugCache[$id]))
		{
			return $this->slugCache[$id];
		}

		if (!empty($slug))
		{
			$this->slugCache[$id] = $slug;
			return $slug;
		}

		$row = $this->db->query('SELECT `name`, `slug` FROM', $this->tableName, 'WHERE `id` = %i', $id)->fetch();
		if (!empty($row['slug']))
		{
			$this->slugCache[$id] = $row['slug'];
			return $row['slug'];
		}

		$this->slugCache[$id] = $id . '~' . \Nette\Utils\Strings::webalize($row['name']);
		return $id . '~' . \Nette\Utils\Strings::webalize($row['name']);
	}


	protected function camelToUnderscore($string)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}


}
