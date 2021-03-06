<?php

namespace SlimAPI;

use cli;
use Composer\Script\Event;
use Incenteev\ParameterHandler\Processor;
use Exception;

class Composer
{
    // CLI text behavior options
    
    const COLOR_YELLOW = '%y';
    const COLOR_GREEN = '%g';
    const COLOR_BLUE = '%b';
    const COLOR_RED = '%r';
    const COLOR_MAGENTA = '%m';
    const COLOR_CYAN = '%c';
    const COLOR_GREY = '%w';
    const COLOR_BLACK = '%k';

    const COLOR_YELLOW_BRIGHT = '%Y';
    const COLOR_GREEN_BRIGHT = '%G';
    const COLOR_BLUE_BRIGHT = '%B';
    const COLOR_RED_BRIGHT = '%R';
    const COLOR_MAGENTA_BRIGHT = '%M';
    const COLOR_CYAN_BRIGHT = '%C';
    const COLOR_GREY_BRIGHT = '%W';
    const COLOR_BLACK_BRIGHT = '%K';

    const BG_YELLOW = '%3';
    const BG_GREEN = '%2';
    const BG_BLUE = '%4';
    const BG_RED = '%1';
    const BG_MAGENTA = '%5';
    const BG_CYAN = '%6';
    const BG_GREY = '%7';
    const BG_BLACK = '%0';

    const STYLE_BLINK = '%F';
    const STYLE_UNDERLINE = '%U';
    const STYLE_INVERSE = '%8';
    const STYLE_BRIGHT = '%9';

    const FONT_RESET = '%n';

    /**
     * 
     * @param $event
     */
    public static function postCreateProjectInstall(Event $event)
    {
//        echo "Would you like to start the configuration process? [yes]: ";
        $stream = new cli\Streams();

        // Set currently working directory to root
        chdir(__DIR__ . "/../");

        $io = $event->getIO();

        self::outSolidGreen('Verifying the "config/parameters.yml" file');
        if ( file_exists("config/parameters.yml") ) {
            self::outSolidGreen('The file "config/parameters.yml" already exists');
            return 0;
        }

        if ( !$io->isInteractive() ) {
            self::outInBox(self::BG_YELLOW, self::COLOR_BLACK, [
                [["Warning!",self::STYLE_BRIGHT.self::STYLE_UNDERLINE],"You cannot use this application without the",["'config/parameters.yml'",self::COLOR_GREEN_BRIGHT],"file."],
                ["You will have to create the",["'config/parameters.yml'",self::COLOR_GREEN_BRIGHT],"file manually."],
                ["You may create one by cloning the",["'config/parameters.yml.sample'",self::COLOR_GREEN_BRIGHT],"file, and changing the value correspondingly."],
                ["Without it, the application will not work."]
            ]);
            return 0;
        }


        //set environment variable
        putenv("SLIM_API_SECRET=" . self::generateSecret(1));

        // Create the parameter file
        $processor = new Processor($io);
        $processor->processFile([
            "file" => "config/parameters.yml",
            "dist-file" => "config/parameters.yml.sample",
            "env-map" => [
                "auth.secret" => "SLIM_API_SECRET"
            ]
        ]);

        //remove environment variable
        putenv("SLIM_API_SECRET");

        //TODO: Create user table and insert a user from input

//        $result = copy("config/parameters.yml.sample", "config/parameters__.yml");
//
//        if ( !$result ) {
//            self::outInBox(self::BG_RED, self::COLOR_GREY_BRIGHT, [
//                [["ERROR!",self::STYLE_BRIGHT.self::COLOR_BLACK_BRIGHT]],
//                ["Error occurred when the process tried to create the 'config/parameters.yml' file."],
//                ["Make sure the directory 'config' is writable and try again."],
//            ]);
//            return 1;
//        }
//        echo "'config/parameter.yml' file has been generated successfully.\n" .
//            "You can now start your using the API. Just make sure your web server points to '" . getcwd() . "/public' path.\n" .
//            "For more information, go to: https://github.com/slavikme/slim-api-skeleton.\n\n" .
//            "Enjoy developing!\n\n";
        return 0;
    }

    /**
     * 
     * @param $bgColor
     * @param $textStyle
     * @param $text
     */
    public static function outInBox($bgColor, $textStyle, $text)
    {
        $lines = [];
        $width = 0;
        $reset = self::FONT_RESET . $textStyle . $bgColor;
        foreach ( $text as $line )
        {
            $line_width = count($line)-1;
            $special_width = 0;
            foreach ( $line as &$part )
            {
                $text = $part;
                if ( is_array($part) )
                {
                    $text = $part[0];
                    $special_width += strlen($part[1] . $reset);
                    $part = $part[1] . $text . $reset;
                }
                $line_width += strlen($text);
            }
            $width = max($width, $line_width);
            $lines[] = [implode(" ", $line), $special_width];
        }

        array_unshift($lines, ["",0]);
        array_push($lines, ["",0]);

        cli\Streams::line();
        foreach ( $lines as $line )
        {
            cli\Streams::line("  $bgColor$textStyle   " . str_pad($line[0], $width+$line[1]) . "   " . self::FONT_RESET);
        }
        cli\Streams::line();
    }

    /**
     * 
     * @param $color
     * @param $text
     */
    private static function outSolid($color, $text)
    {
        cli\Streams::line($color . $text . self::FONT_RESET);
    }

    /**
     * 
     * @param $text
     */
    private static function outSolidGreen($text)
    {
        self::outSolid(self::COLOR_GREEN, $text);
    }

    /**
     * 
     * @param $text
     */
    private static function outSolidYellow($text)
    {
        self::outSolid(self::COLOR_YELLOW, $text);
    }

    /**
     * 
     * @param $length
     */
    private static function generateRandomString($length = 64)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * 
     * @param $rows
     * @param $rowsize
     * @param $separator
     */
    private static function generateSecret($rows = 20, $rowsize = 64, $separator = "")
    {
        $lines = [];
        for ( $i = 0; $i < $rows; $i++ ) {
            $lines[] = self::generateRandomString($rowsize);
        }
        return implode($separator, $lines);
    }
}
