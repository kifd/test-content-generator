<?php
/*
Name: Lipsum 0.1
Description: Quick and simple lipsum generator for making test content.
Author: Keith Drakard
Author URI: https://drakard.com/
*/

if (! defined('WPINC')) die;

class Lipsum {
    
    private static string $filename = 'latin.txt';
    private static array $words = [];
    
    
    public static function title(int $min = 1, int $max = 5): string {
        $words = [];
        $amount = rand($min, $max);
        while (sizeof($words) < $amount) {
            array_push($words, Lipsum::word(capitalise: true));
        }
        return implode(" ", $words);
    }
    
    public static function paragraphs(int $min = 1, int $max = 20, bool $wrap = false, bool $bold = false, bool $italic = false): string {
        $paragraphs = [];
        $amount = rand($min, $max);
        while (sizeof($paragraphs) < $amount) {
            $paragraphs[]= Lipsum::paragraph(min_sentences: 2, max_sentences: 6, wrap: $wrap, bold: $bold, italic: $italic);
        }
        return implode("\n\n", $paragraphs);
    }
    
    
    
    public static function blockquote(int $max_sentences = 2): string {
        return sprintf(
            '<blockquote>%s<footer>- %s, <cite>%s</cite></footer></blockquote>',
                Lipsum::paragraph(max_sentences: $max_sentences, wrap: true),
                Lipsum::sentence(min_words: 2, max_words: 2, capslock: true, punctuation: false),
                Lipsum::sentence(min_words: 2, max_words: 4, capslock: true, punctuation: false),
        );
    }
    
    
    public static function ulol_list(int $min_items = 2, int $max_items = 20, string $type = 'ul'): string {
        $items = [];
        $amount = rand($min_items, $max_items);
        while (sizeof($items) < $amount) {
            $items[] = Lipsum::sentence(min_words: 6, max_words: 24);
        }
        return sprintf(
            '<%s><li>%s</li></%s>',
                $type, implode('</li><li>', $items), $type
        );
    }
    
    
    
    
    
    public static function paragraph(int $min_sentences = 1, int $max_sentences = 5, bool $wrap = false, bool $bold = false, bool $italic = false): string {
        $sentences = [];
        $amount = rand($min_sentences, $max_sentences);
        while (sizeof($sentences) < $amount) {
            array_push($sentences, Lipsum::sentence(min_words: 4, bold: $bold, italic: $italic));
        }
        $paragraph = implode(' ', $sentences);
        return ($wrap) ? '<p>'.$paragraph.'</p>' : $paragraph;
    }
    
    public static function sentence(int $min_words = 1, int $max_words = 16, bool $capslock = false, bool $punctuation = true, bool $bold = false, bool $italic = false): string {
        $capitalise = true;
        $words = [];
        $amount = rand($min_words, $max_words);
        while (sizeof($words) < $amount) {
            $add_bold = ($bold and rand(1,100) > 95) ? true : false;
            $add_italic = ($italic and rand(1,100) > 95) ? true : false;
            $word = Lipsum::word(capitalise: $capitalise, bold: $add_bold, italic: $add_italic);
            if ($capitalise) {
                $capitalise = $capslock; // capslock = ucfirst each word (ie a name)
            } else if ($punctuation) {
                $punctuation = rand(1,100);
                if ($punctuation > 98) {
                    $word.= ';';
                } else if ($punctuation > 83) {
                    $word.= ',';
                }
            }
            array_push($words, $word);
        }
        $sentence = rtrim(implode(' ', $words), ' ;,').(($punctuation) ? '.' : '');
        return $sentence;
    }
    
    public static function word(int $min_length = 1, int $max_length = -1, bool $capitalise = false, bool $bold = false, bool $italic = false): string {
        // TODO: if you're supporting 8.3+ when https://wiki.php.net/rfc/arbitrary_static_variable_initializers comes in
        if (empty(self::$words)) self::$words = self::load(self::$filename);
        do {
            $word = Lipsum::$words[array_rand(Lipsum::$words)];
        } while (strlen($word) < $min_length or ($max_length > $min_length and strlen($word) > $max_length));
        $word = ($capitalise) ? ucfirst($word) : $word;
        
        if ($bold) $word = '<b>'.$word.'</b>';
        if ($italic) $word = '<i>'.$word.'</i>';
        
        return $word;
    }
    
    
    
    private static function load(string $filename): array {
        $text = file(__DIR__.'/'.$filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $text;
    }
    
}
