<?php

namespace Pagewiser\DAL\Dibi;

/**
 * Abstract class for all database events
 */
abstract class EventListener
{


	public function onBeforeInsert(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $data)
	{
	}


	public function onInserted(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $id, $data)
	{
	}


	public function onBeforeUpdate(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $data)
	{
	}


	public function onUpdated(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $id, $data)
	{
	}


	public function onBeforeDelete(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $id)
	{
	}


	public function onDeleted(\Pagewiser\DAL\Dibi\AbstractDatabaseService $class, $id)
	{
	}


}
