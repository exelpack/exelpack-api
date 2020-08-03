<?php

use Illuminate\Database\Seeder;
use App\Units;
class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

    	$units = [
    		[
    			'unit' => 'pc'
    		],
    		[
    			'unit' => 'pcs'
    		],
    		[
    			'unit' => 'each'
    		],
    		[
    			'unit' => 'rolls'
    		],
    		[
    			'unit' => 'sheet'
    		],
    		[
    			'unit' => 'bale'
    		],
    		[
    			'unit' => 'pair'
    		],
    		[
    			'unit' => 'kg'
    		]
    	];

      Units::insert($units);
    }
}
