<?php namespace Illuminate\Database\Eloquent;

trait SoftDeletingTrait {

	/**
	 * Boot the soft deleting trait for a model.
	 *
	 * @return void
	 */
	public static function bootSoftDeletingTrait()
	{
		static::addGlobalScope(new SoftDeletingScope);
	}

	/**
	 * Force a hard delete on a soft deleted model.
	 *
	 * @return void
	 */
	public function forceDelete()
	{
		$softDelete = $this->softDelete;
		// We will temporarily disable false delete to allow us to perform the real
		// delete operation against the model. We will then restore the deleting
		// state to what this was prior to this given hard deleting operation.
		$this->softDelete = false;
		$this->delete();
		$this->softDelete = $softDelete;
	}

	/**
	 * Perform the actual delete query on this model instance.
	 *
	 * @return void
	 */
	protected function performDeleteOnModel()
	{
		$query = $this->newQuery()->where($this->getKeyName(), $this->getKey());
		if ($this->softDelete)
		{
			$this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();
			$query->update(array($this->getDeletedAtColumn() => $this->fromDateTime($time)));
		}
		else
		{
			$query->forceDelete();
		}
	}

	/**
	 * Restore a soft-deleted model instance.
	 *
	 * @return bool|null
	 */
	public function restore()
	{
		// If the restoring event does not return false, we will proceed with this
		// restore operation. Otherwise, we bail out so the developer will stop
		// the restore totally. We will clear the deleted timestamp and save.
		if ($this->fireModelEvent('restoring') === false)
		{
			return false;
		}

		$this->{$this->getDeletedAtColumn()} = null;

		// Once we have saved the model, we will fire the "restored" event so this
		// developer will do anything they need to after a restore operation is
		// totally finished. Then we will return the result of the save call.
		$this->exists = true;

		$result = $this->save();

		$this->fireModelEvent('restored', false);

		return $result;
	}

	/**
	 * Determine if the model instance has been soft-deleted.
	 *
	 * @return bool
	 */
	public function trashed()
	{
		return ! is_null($this->{$this->getDeletedAtColumn()});
	}

	/**
	 * Get a new query builder that includes soft deletes.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public static function withTrashed()
	{
		return (new static)->newQueryWithoutScope(new SoftDeletingScope);
	}

	/**
	 * Get a new query builder that only includes soft deletes.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public static function onlyTrashed()
	{
		$instance = new static;

		$column = $instance->getQualifiedDeletedAtColumn();

		return $instance->newQueryWithoutScope(new SoftDeletingScope)->whereNotNull($column);
	}

	/**
	 * Register a restoring model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function restoring($callback)
	{
		static::registerModelEvent('restoring', $callback);
	}

	/**
	 * Register a restored model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function restored($callback)
	{
		static::registerModelEvent('restored', $callback);
	}

	/**
	 * Get the name of the "deleted at" column.
	 *
	 * @return string
	 */
	public function getDeletedAtColumn()
	{
		return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
	}

	/**
	 * Get the fully qualified "deleted at" column.
	 *
	 * @return string
	 */
	public function getQualifiedDeletedAtColumn()
	{
		return $this->getTable().'.'.$this->getDeletedAtColumn();
	}

}
