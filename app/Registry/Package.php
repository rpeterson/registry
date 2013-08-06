<?php
namespace Registry;

use Registry\Abstracts\AbstractModel;
use Registry\Services\PackagesEndpoints;

/**
 * A Package in the registry
 */
class Package extends AbstractModel
{
	use Traits\HasKeywords;

	////////////////////////////////////////////////////////////////////
	//////////////////////////// RELATIONSHIPS /////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Get all of the Package's Maintainers
	 *
	 * @return Collection
	 */
	public function maintainers()
	{
		return $this->belongsToMany('Registry\Maintainer');
	}

	/**
	 * Get all of the Package's Versions
	 *
	 * @return Collection
	 */
	public function versions()
	{
		return $this->hasMany('Registry\Version')->latest();
	}

	/**
	 * Get the Package's Comments
	 *
	 * @return Collection
	 */
	public function comments()
	{
		return $this->hasMany('Registry\Comment');
	}

	////////////////////////////////////////////////////////////////////
	////////////////////////// RAW INFORMATIONS ////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Get the Packagist informations of a package
	 *
	 * @return object
	 */
	public function getPackagist()
	{
		return (object) $this->getFromApi('guzzle', '/packages/'.$this->name.'.json')['package'];
	}

	/**
	 * Get the Github informations of a package
	 *
	 * @return array
	 */
	public function getRepository()
	{
		return $this->getFromApi('scm', '');
	}

	/**
	 * Get the issues of a package
	 *
	 * @return array
	 */
	public function getRepositoryIssues()
	{
		return $this->getFromApi('scm', '/issues');
	}

	/**
	 * Get Travis informations
	 *
	 * @return array
	 */
	public function getTravis()
	{
		return $this->getFromApi('travis', $this->travis);
	}

	/**
	 * Get Travis builds
	 *
	 * @return array
	 */
	public function getTravisBuilds()
	{
		return $this->getFromApi('travis', $this->travis.'/builds');
	}

	/**
	 * Get Scrutinizer
	 *
	 * @return array
	 */
	public function getScrutinizer()
	{
		return $this->getFromApi('scrutinizer', $this->repositoryName.'/metrics');
	}

	/**
	 * Get a PackagesEndpoints Service
	 *
	 * @param  string $source
	 * @param  string $url
	 *
	 * @return PackagesEndpoints
	 */
	protected function getFromApi($source, $url)
	{
		return App::make('packages.endpoints')->getFromApi($this, $source, $url);
	}

	////////////////////////////////////////////////////////////////////
	///////////////////////////// ATTRIBUTES ///////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Get the shorthand name for the package
	 *
	 * @return string  A vendor/package string
	 */
	public function getRepositoryNameAttribute()
	{
		list($package, $vendor) = array_reverse(explode('/', $this->repository));

		return $vendor.'/'.$package;
	}

	/**
	 * Get the Travis status of the Package
	 *
	 * @return string
	 */
	public function getTravisBuildAttribute()
	{
		$status = array('unknown', 'failing', 'passing');

		return $status[(int) $this->build_status];
	}

	/**
	 * Get Maintainers as a string list
	 *
	 * @return string
	 */
	public function getMaintainersListAttribute()
	{
		return $this->maintainers->implode('link', ', ');
	}

	/**
	 * The DateTime fields of the model
	 *
	 * @return array
	 */
	public function getDates()
	{
		return array_merge(parent::getDates(), array('pushed_at'));
	}

	////////////////////////////////////////////////////////////////////
	///////////////////////////// QUERY SCOPES /////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Get packages by most popular
	 *
	 * @param  Query $query
	 *
	 * @return Query
	 */
	public function scopePopular($query)
	{
		return $query->orderBy('popularity', 'DESC');
	}

	/**
	 * Return packages similar to another one
	 *
	 * @param  Query $query
	 * @param  Package $package
	 *
	 * @return Query
	 */
	public function scopeSimilar($query, $package)
	{
		return $query->where('name', '!=', $package->name)->where(function($query) use ($package) {
			foreach ($package->keywords as $keyword) {
				$query->orWhere('keywords', 'LIKE', "%$keyword%");
			}
		});
	}

}