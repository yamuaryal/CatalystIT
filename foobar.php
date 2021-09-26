<?php
$number = 1;
//Generate a random number using the rand function.
while($number < 100){
	$output =  ( $number%3 )?( $number%5 ? $number : 'bar' ):(( $number%5 )? 'foo' : 'foobar');
	echo $output.', ';
	$number++;
}
