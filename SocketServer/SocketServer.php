#!/usr/bin/env php-cli
<?php

require_once('websockets.php');
require_once '../../Klib/Omega2lib.php';
require_once '../../Klib/omegaPwm.php';

$host = "192.168.1.51";    // BayMax Car
$port = 9000;

//*************************************
// Pins for Motor should be setup here
//

// Throttle
$throttle       = 13;

// Rear motor
$turnBack       = 14;
$turnForward    = 15;

// Steering Motor
$turnLeft       = 11;
$turnRight      = 12;
//*************************************

$BayMax = new Omega2( FALSE ); //FALSE is no logging
$BayMax = new omegaPWM(); //FALSE is no logging


$BayMax->pwmInit(); // Initialize Omega2 PWM extension board
$BayMax->pwmSetOnDelay( $throttle, 100, 0); //this should give full throttle


class echoServer extends WebSocketServer
{
    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    public $y = 0;      // Store y value not to repeat same instructions - Moving
    public $x = 0;      // Store x value not to repeat same instructions - Steering

    protected function process ( $user, $message )
    {
        global $BayMax, $y, $x, $throttle, $turnForward, $turnBack, $turnLeft, $turnRight;
        $response = 0;

        // Fetch $message;
        // Format: <instruction>=<value>
        $instruction = explode( '=', $message );

        switch (trim($instruction[0]))
        {
            case "lights":
            {
                if ($instruction[1] == 1)
                {
                    echo "Turn lights ON\n";
                    $response = "Turn lights ON";
                }
                else if ($instruction[1] == 0)
                {
                    echo "Turn Lights OFF\n";
                    $response = "Turn lights OFF";
                }

                break;
            }

            case "direction":
            {
                if ($instruction[1] == 1)
                {
                    echo "Turn left direction lights ON\n";
                    $response = "Turn left direction lights ON";
                }
                else if ($instruction[1] == 2)
                {
                    echo "Turn Right direction lights ON\n";
                    $response = "Turn Right direction lights ON";
                }
                else if ($instruction[1] == 0)
                {
                    echo "Turn Directions OFF\n";
                    $response = "Turn Directions OFF";
                }

                break;
            }

            // Steering detection
            case "x":
            {
                if ( $x !== $instruction[1] )
                {
                    if ( $instruction[1] == 0 )
                    {
                        $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                        $BayMax->pwmSetOnDelay( $turnRight, 0, 0);
                        $BayMax->pwmSetOnDelay( $turnLeft, 0, 0);

                        echo $instruction[1] . "x ";
                        $x = $instruction[1];
                    }
                    else if ( $instruction[1] < 0 )
                    {
                        $string = str_replace('-', '', $instruction[1]);
                        $x = $instruction[1];
                        echo $string . "x ";

                        if ( $string > 0 && $string < 100 )
                        {
                            //$BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                            $BayMax->pwmSetOnDelay( $turnLeft, 0, 0);
                            $BayMax->pwmSetOnDelay( $turnRight, $string, 0);
                        }
                        else
                        {
                            $string = 100;
                            $BayMax->pwmSetOnDelay( $turnLeft, 0, 0);
                            $BayMax->pwmSetOnDelay( $turnRight, $string, 0);
                        }
                    }
                    else if ( $instruction[1] > 0 )
                    {
                        $string = $instruction[1];
                        echo $string."x ";
                        $x = $instruction[1];

                        if ( $string < 100 )
                        {
                            $BayMax->pwmSetOnDelay( $turnRight, 0, 0 );
                            $BayMax->pwmSetOnDelay( $turnLeft, $string, 0 );
                        }
                        else
                        {
                            $string = 100; // This is a full throttle
                            $BayMax->pwmSetOnDelay( $turnRight, 0, 0 );
                            $BayMax->pwmSetOnDelay( $turnLeft, $string, 0 );
                        }
                    }
                }

                break;
            }

            // Moving detection
            case "y":
            {
                if ( $y !== $instruction[1] )
                {
                    if ( $instruction[1] == 0 )
                    {
                        $string = $instruction[1];
                        $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                        $BayMax->pwmSetOnDelay( $turnBack, $string, 0);
                        $BayMax->pwmSetOnDelay( $turnForward, $string, 0 );

                        echo $instruction[1] . "y ";
                        $y = $instruction[1];
                    }
                    else if ( $instruction[1] < 0 )
                    {
                        $string = str_replace('-', '', $instruction[1]);
                        $y = $instruction[1];
                        echo $string . "y ";

                        if ( $string > 0 && $string < 100 )
                        {
                            //$BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                            $BayMax->pwmSetOnDelay( $turnForward, 0, 0 );
                            $BayMax->pwmSetOnDelay( $turnBack, $string, 0);
                        }
                        else
                        {
                            $string = 100;
                            $BayMax->pwmSetOnDelay( $turnForward, 0, 0 );
                            $BayMax->pwmSetOnDelay($turnBack, $string, 0);
                        }
                    }
                    else if ( $instruction[1] > 0 )
                    {
                        $string = $instruction[1];
                        echo $string."y ";
                        $y = $instruction[1];

                        if ( $string < 100 )
                        {
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0 );
                            $BayMax->pwmSetOnDelay($turnBack, 0, 0);
                            $BayMax->pwmSetOnDelay( $turnForward, $string, 0 );
                        }
                        else
                        {
                            $string = 100; // This is a full throttle
                            $BayMax->pwmSetOnDelay($turnBack, 0, 0);
                            $BayMax->pwmSetOnDelay( $turnForward, $string, 0 );
                        }
                    }
                }

                break;
            }

            default:
                break;
        }

        $this->send($user,$response);
    }

    protected function connected ($user)
    {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed ($user)
    {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
    }
}

$echo = new echoServer( $host, $port );

try { $echo->run(); }
catch (Exception $e) { $echo->stdout($e->getMessage()); }
