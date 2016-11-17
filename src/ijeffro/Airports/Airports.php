<?php

namespace ijeffro\Airports;

use Illuminate\Database\Eloquent\Model;

/**
 * AirportList
 *
 */
class Airports extends Model {

	/**
	 * @var string
	 * Path to the directory containing airports data.
	 */
	protected $airports;

	/**
	 * @var string
	 * The table for the airports in the database, is "airports" by default.
	 */
	protected $table;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
       $this->table = \Config::get('airports.table_name');
    }

    /**
     * Get the airports from the JSON file, if it hasn't already been loaded.
     *
     * @return array
     */
    protected function getAirports()
    {
        //Get the airports from the JSON file
        if (sizeof($this->airports) == 0){
            $this->airports = json_decode(file_get_contents(__DIR__ . '/Models/airports.json'), true);
        }

        //Return the airports
        return $this->airports;
    }

	/**
	 * Returns one airport
	 *
	 * @param string $id The airport id
     *
	 * @return array
	 */
	public function getOne($id)
	{
    $airports = $this->getAirports();
		return $airports[$id];
	}

	/**
	 * Returns a list of airports
	 *
	 * @param string sort
	 *
	 * @return array
	 */
	public function getList($sort = null)
	{
	    //Get the airports list
	    $airports = $this->getAirports();

	    //Sorting
	    $validSorts = array(
					'iso_3166_3',
					'country_code',
					'name'
        );

	    if (!is_null($sort) && in_array($sort, $validSorts)){
	        uasort($airports, function($a, $b) use ($sort) {
	            if (!isset($a[$sort]) && !isset($b[$sort])){
	                return 0;
	            } elseif (!isset($a[$sort])){
	                return -1;
	            } elseif (!isset($b[$sort])){
	                return 1;
	            } else {
	                return strcasecmp($a[$sort], $b[$sort]);
	            }
	        });
	    }

	    //Return the airports
		return $airports;
	}

	/**
	 * Returns a list of airports suitable to use with a select element in Laravelcollective\html
	 *
	 * @param string sort
	 *
	 * @return array
	 */
	public function getListForSelect($sort = null)
	{
		foreach ($this->getList('name') as $key => $value) {
			$airports[$key] = $value['name'];
		}

		//return the array
		return $airports;
	}
}
