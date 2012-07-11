<?php

namespace Uniplaces\BabelateBundle\Yaml;

class Dumper
{
    public static function dump($yaml_contents, $file)
    {
        if(!is_array($yaml_contents)) {
            return false;
        }
        
        $output='';
        foreach($yaml_contents as $key => $translation) {
            $escaped_translation = str_replace('"', '\"', $translation);
            if(preg_match('/((\r?\n)|(\r\n?))/', $escaped_translation) == 0) {
                $output .= sprintf("%s%s%s: %s%s%s\n", '"', $key, '"', '"', $escaped_translation, '"');
            } else {
                $output .= sprintf("%s%s%s: |\n", '"', $key, '"');
                //$output .= sprintf("    %s", '"');
                foreach(preg_split('/((\r?\n)|(\r\n?))/', $escaped_translation) as $line) {
                    $output .= sprintf("    %s\n", $line);
                }
                //$output .= sprintf("%s\n", '"');
            }
        }
        file_put_contents($file, $output);
        
        return true;
    }
}
