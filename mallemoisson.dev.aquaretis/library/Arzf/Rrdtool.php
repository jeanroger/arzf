<?php

//namespace library\Arzf;

/**
 *
 * @author YRDA7553
 *        
 */
class Arzf_Rrdtool
{
	public function __construct(){
		
	}
	
	public function virtualDataset ($start, $stop, $step, $range = array(), $forJavascript = false)
	{
		if ($stop == 0) $stop = time();
		if ($start == 0) $start = $stop - 604800;
		
		$items = array();
		for ($i=$start; $i<=$stop; $i+=$step ){
			$value = rand($range[0], $range[1]);
			$timestamp = $i;
			if ($forJavascript) $timestamp *= 1000;
			$items[$timestamp] = $value;
		}
		
		return $items;
	}
}

?>