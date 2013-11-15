#!/usr/bin/php
<?php

require( 'hue.php' );

function echoHelp()
{
	echo "./huecli.php".
			"\n\t-i [Hue bridge's ip]".
			"\n".
			"\n\tIf you don't already have a valid registered key for this Hue bridge:".
			"\n\t\t-g [register a new key with the Hue bridge]".
			"\n".
			"\n\tOnce have a registered key, you need to specify it with -k:".
			"\n\t\t-k [valid key that is registered with your Hue hub]".
			"\n".
			"\n\tUse it in combination with one of the following parameters:".
			"\n".
			"\n\t\tTo get information from the bridge:".
			"\n\t\t\t-f [fetch full state from Hue hub]".
			"\n\t\t\t-c [check light states: returns 0 when a light is off, 1 when on]".
			"\n".
			"\n\t\tTo control the bridge:".
			"\n\t\t\tIf you don't specify a light number, huecli will target all bulbs:".
			"\n\t\t\t\t-l [light number]".
			"\n".
			"\n\t\t\tTurning a light on or off:".
			"\n\t\t\t\t-o [0 for turning the light off, 1 for turning it on]".
			"\n".
			"\n\t\t\tTo set a new color, pick one of the following options:".
			"\n\t\t\t\t-n [color name (see below)]".
			"\n".
			"\n\t\t\t\tor".
			"\n\t\t\t\t-h [hue in degrees on the color circle 0-360]".
			"\n\t\t\t\t-s [saturation 0-254]".
			"\n\t\t\t\t-b [brightness 0-254]".
			"\n".
			"\n\t\t\t\tor".
			"\n\t\t\t\t-t [white color temp 150-500]".
			"\n".
			"\n\t\t\tAdditionally options are:".
			"\n\t\t\t\t-r [transition time, in seconds. Decimals are legal (\".1\", for instance)]".
			"\n\t\t\t\t-e [command to execute before changing light setting]";
}

function echoLightState( $lights )
{
	global $hue;
	$state = '';

	foreach ( $lights as $light )
	{
		$state = $hue->lights()[ $light ]->state();
		echo "Light " .$light. " (" .$hue->lights()[ $light ]->name(). ") is " . ( $state ? "on" : "off" ) . "\n";
	}

	return $state;
}

$args = getopt( 'i:k:l:h:s:b:t:o:r:n:e:cfg' );
$oneParamSet = isset( $args['h'] ) || isset( $args['s'] ) || isset( $args['b'] ) || isset( $args['t'] ) || isset( $args['o'] ) || isset( $args['n'] ) || isset( $args['f'] ) || isset( $args['c'] );
$command = array();

if ( isset( $args['i'] ) && isset( $args['g'] ) )
{
	$hue = new Hue( $args['i'], '' );
	$data = json_decode( $hue->register(), true );

	if ( isset( $data[0]["error"] ) )
	{
		echo "Error: Registering new key with Hue bridge failed. Did you forget to press the link button?\n";
	}
	else if ( isset( $data[0]["success"] ) )
	{
		echo "Registered new key with Hue bridge: " .$data[0]["success"]["username"]. "\n";
		echo "You can now try to turn on a light like this:".
			 "\n\n\t./huecli.php -i " .$args['i']. " -k " .$data[0]["success"]["username"]. " -o 1\n";
	}
	exit( 0 );
}

// we require a bridge ip and key to be specified
if ( !isset( $args['i'] ) || !isset( $args['k'] ) || !$oneParamSet )
{
	$oneParamHelp = $oneParamSet ? "" : " and at least one of the following options: -f, -c, -h, -s, -b, -t, -o or -n";
	echo "Error: You need to specify an ip (-i) & key (-k)$oneParamHelp.\n\n";

	echoHelp();
	exit( 0 );
}

$hue = new Hue( $args['i'], $args['k'] );

// do we want to set ot get the bridge's state
if ( isset( $args['f'] ) )
{
	$state = $hue->state();
	var_dump( json_decode( $state, true ) );
	exit( 0 );
}

// if we didn't get a -l parameter, build an array of all lights
$lights = array();
if ( !isset( $args['l'] ) )
	$lights = $hue->lightIds();
else
	$lights[] = $args['l'];

// do we want to get the lights' state
if ( isset( $args['c'] ) )
{
	$state = echoLightState( $lights );
	exit( ( count( $lights ) == 1 && $state ) ? 1 : 0 );
}

// handle predefined colors
if ( isset( $args['n'] ) )
{
	$command = $hue->predefinedColors( $args['n'] );
	$command['on'] = true;
}

// clean up other inputs
// the hue interface will keep numeric parms within range for us, just sanitize the
// types for clean json encoding, and do the math on the hue input.
$fields = array( 'h' => 'hue', 's' => 'sat', 'b' => 'bri',
				 't' => 'ct', 'o' => 'on', 'r' => 'transitiontime' );
foreach ( $fields as $name => $value )
{
	if ( isset( $args[$name] ) )
	{
		if ( $name == 'o' )
		{
			$command[$value] = (bool)$args[$name];
		}
		else if ( $name == 'h' )
		{
			$command['hue'] = 182 * $args['h'];
			$command['on'] = true;
		}
		else if ( $name == 'r' )
		{
			$command['transitiontime'] = 10 * $args['r'];
		}
		else
		{
			$command[$value] = (int)$args[$name];
			$command['on'] = true;
		}
	}
}

if ( isset( $args['e'] ) )
{
	passthru( $args['e'] );
	echo "\n";
}

foreach ( $lights as $light )
{
	$hue->lights()[$light]->setLight( $command );
}
echoLightState( $lights );

?>
