#!/usr/bin/env php-cli
<?php

require_once('websockets.php');
require_once '../../Klib/Omega2lib.php';
require_once '../../Klib/omegaPwm.php';

// $host = "192.168.1.50"; // BayMax
$host = "192.168.1.51";    // BayMax Car
$port = 9000;

//
// Pins for Motor should be setup here
//

// Throttle
$throttle = 13;

// Rear motor
$turnBack = 14;
$turnForward = 15;

// Steering Motor
$turnLeft = 12;
$turnRight = 11;



const SHOW_ON_LCD = "omegaLCD -w %s %s";

$BayMax = new Omega2( FALSE ); //FALSE is no logging
$BayMax = new omegaPWM( FALSE ); //FALSE is no logging

$availableServos = Array ( 1 => "SG90", 2 => "S3003" );
$BayMax->pwmInit();
$BayMax->pwmSetOnDelay( $throttle, 100, 0); //this should give full throttle


class echoServer extends WebSocketServer {
    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    public $y = 0;      // Store y value not to repeat same requests
    public $x = 0;      // Store x value not to repeat same requests

    protected function process ($user, $message)
    {
        global $BayMax,$availableServos,$y,$x, $throttle, $turnForward, $turnBack, $turnLeft, $turnRight;
        $response = 0;


        //echo $message;
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
            case "x":
            {
                //$BayMax->pwmInit();
                //$BayMax->pwmSetOnDelay( 2, 100, 0);

                if ( $y !== $instruction[1] )
                {
                    if ($instruction[1] == 0)
                    {
                        $BayMax->pwmInit();
                        $BayMax->pwmSetOnDelay( $throttle, 100, 0);

                        //$BayMax->pwmSetOnDelay(0, 0, 0);
                        //$BayMax->pwmSetOnDelay(1, 0, 0);
                        echo $instruction[1] . "x ";
                        $y = $instruction[1];
                    }
                    else if ($instruction[1] < 0)
                    {
                        $string = str_replace('-', '', $instruction[1]);
                        $y = $instruction[1];
                        echo $string . "x ";

                        if ($string > 0 && $string < 100)
                        {
                            $BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                            $BayMax->pwmSetOnDelay( $turnRight, $string, 0);
                        }
                        else
                        {
                            $string = 100;
                            $BayMax->pwmSetOnDelay( $turnRight, $string, 0);
                        }

                    }
                    else if ($instruction[1] > 0)
                    {
                        $string = $instruction[1];
                        echo $string."x ";
                        $y = $instruction[1];

                        if ($string < 100)
                        {
                            $BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0 );
                            // $string = 9 - $string;
                            $BayMax->pwmSetOnDelay( $turnLeft, $string, 0 );
                        }
                        else
                        {
                            $string = 100; // This is a full throttle
                            $BayMax->pwmSetOnDelay( $turnLeft, $string, 0 );
                        }

                    }

                }

                //$BayMax->pwmSleep();
                break;
            }

            case "y":
            {
                //$BayMax->pwmInit();
                //$BayMax->pwmSetOnDelay( 2, 100, 0);

                if ( $y !== $instruction[1] )
                {
                    if ($instruction[1] == 0)
                    {
                        $BayMax->pwmInit();
                        $BayMax->pwmSetOnDelay( $throttle, 100, 0);

                        //$BayMax->pwmSetOnDelay(0, 0, 0);
                        //$BayMax->pwmSetOnDelay(1, 0, 0);
                        echo $instruction[1] . "y ";
                        $y = $instruction[1];
                    }
                    else if ($instruction[1] < 0)
                    {
                        $string = str_replace('-', '', $instruction[1]);
                        $y = $instruction[1];
                        echo $string . "y ";

                        if ($string > 0 && $string < 100)
                        {
                            $BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0);
                            $BayMax->pwmSetOnDelay( $turnBack, $string, 0);
                        }
                        else
                        {
                            $string = 100;
                            $BayMax->pwmSetOnDelay($turnBack, $string, 0);
                        }

                    }
                    else if ($instruction[1] > 0)
                    {
                        $string = $instruction[1];
                        echo $string."y ";
                        $y = $instruction[1];

                        if ($string < 100)
                        {
                            $BayMax->pwmInit();
                            $BayMax->pwmSetOnDelay( $throttle, 100, 0 );
                            // $string = 9 - $string;
                            $BayMax->pwmSetOnDelay( $turnForward, $string, 0 );
                        }
                        else
                        {
                            $string = 100; // This is a full throttle
                            $BayMax->pwmSetOnDelay( $turnForward, $string, 0 );
                        }

                    }

                }

                //$BayMax->pwmSleep();
                break;
            }

            default:
                break;
        }

        $this->send($user,$response);
    }

    protected function connected ($user) {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed ($user) {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
    }
}

$echo = new echoServer( $host, $port );

try { $echo->run(); }
catch (Exception $e) { $echo->stdout($e->getMessage()); }
