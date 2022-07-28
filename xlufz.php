<?php

/**
 * Xlufz is simple PHP-based web service that can highlight selected words
 * on a web page. Only certain web pages are supported (but you can modify
 * that for your own use). Xlufz is used by the free and open source Exet
 * (http://exet.app) web app for crossword construction, to allow setters
 * to see cryptic indicators etc. relevant to the clue surface that they
 * may be toying with.
 *
 * Word selection is done via the excellent and versatile Datamuse words
 * API (https://www.datamuse.com/api/).
 *
 * You specify the web page via the "srcurl" parameter. All other parameters
 * are passed over to the Datamuse words API for word selection.
 */

/**
 * PHP 7 does not have str_(starts,ends}_with(). We implement my_* versions.
 */
function my_str_starts_with($haystack, $needle) {
  $l = strlen($needle);
  $sub = substr($haystack, 0, $l);
  return ($sub == $needle);
}
function my_str_ends_with($haystack, $needle) {
  $l = strlen($needle);
  $sub = substr($haystack, strlen($haystack) - $l, $l);
  return ($sub == $needle);
}

/**
 * Input: text free from tags.
 * Output: tokens, array of alphabetic tokens and all non-alphabetic character
 *         tokens.
 */
function tokenize($text, &$tokens) {
  $l = strlen($text);
  $tokenStart = 0;
  for ($i = 0; $i < $l; $i++) {
    $c = strtolower($text[$i]);
    if (($c < 'a') || ($c > 'z')) {
      if ($i > $tokenStart) {
        array_push($tokens, substr($text, $tokenStart, $i - $tokenStart));
      }
      array_push($tokens, $text[$i]);
      $tokenStart = $i + 1;
    }
  }
  if ($l > $tokenStart) {
    array_push($tokens, substr($text, $tokenStart, $l - $tokenStart));
  }
}

/**
 * Get an array of keys for a word/phrase.
 */
function get_keys($phrase) {
  $lcPhrase = strtolower($phrase);
  $keys = array($lcPhrase);

  $l = strlen($lcPhrase);
  $suffixes = array('ed', 's', 'ing');
  foreach ($suffixes as $suffix) {
    $suffixLen = strlen($suffix);
    if (my_str_ends_with($lcPhrase, $suffix)) {
      $key = substr($lcPhrase, 0, $l - $suffixLen);
      if (strlen($key) > 1) {
        array_push($keys, $key);
      }
      $key = substr($lcPhrase, 0, $l - $suffixLen - 1);
      if (strlen($key) > 1) {
        array_push($keys, $key);
      }
    }
  }
  return $keys;
}

/**
 * Populate an associative array (phraseMap) with phrases from the Datamuse
 * API output.
 */
function makeMapOfPhrases($phrases, &$phraseMap) {
  $num = count($phrases);
  for ($i = 0; $i < $num; $i++) {
    $phrase = $phrases[$i]['word'];
    $keys = get_keys($phrase);
    foreach ($keys as $key) {
      $phraseMap[$key] = 1;
    }
  }
}

/**
 * Match a token in phraseMap, allow matching some variants too.
 */
function tokenMatch($token, $phraseMap) {
  $keys = get_keys($token);
  foreach ($keys as $key) {
    if (array_key_exists($key, $phraseMap)) {
      return true;
    }
  }
  return false;
}

/**
 * In text, any tokens that match an entry in phraseMap will get surrounded
 * by a highlighting span in the output of this function.
 */
function markPhrasesInText($text, $phraseMap) {
  $output = '';
  $tokens = array();
  tokenize($text, $tokens);
  foreach ($tokens as $token) {
    if (tokenMatch($token, $phraseMap)) {
      $output = $output . '<span class="xlufz">' . $token . '</span>';
    } else {
      $output = $output . $token;
    }
  }
  return $output;
}

/**
 * Apply markPhrasesInTxt() to the text portions of the input html. Also
 * insert a style element and a short right-aligned explanatory heading.
 * Do not make any modifications inside script and style tags.
 */
function markPhrasesInHtml($html, $phrases, $datamuseUrl, $srcurl) {
  $markedHtml = '';
  $phraseMap = array();
  makeMapOfPhrases($phrases, $phraseMap);
  $l = strlen($html);
  $textStart = 0;
  $i = 0;
  while ($i < $l) {
    if ($html[$i] != '<') {
      $i++;
      continue;
    }
    if ($i > $textStart) {
      $markedHtml = $markedHtml . markPhrasesInText(substr(
        $html, $textStart, $i - $textStart), $phraseMap);
    }
    $closer = '>';
    $piece = substr($html, $i, 7);
    if (my_str_starts_with($piece, '<script')) {
      $closer = '</script>';
    } else if (my_str_starts_with($piece, '<style')) {
      $closer = '</style>';
    }
    $closerPos = strpos($html, $closer, $i + 1);
    $closerLen = strlen($closer);
    if ($closerPos === false) {
      $closerPos = $l - $closerLen;
    }
    $closerPos += $closerLen;
    $markedHtml = $markedHtml . substr($html, $i, $closerPos - $i);
    $textStart = $closerPos;
    $i = $closerPos;

    if (my_str_starts_with($piece, '<body')) {
      $xlufzHeader =
        "\n" .
        "<style>\n" .
        ".xlufz {\n" .
        "  color: blue;\n" .
        "  font-weight: bold !important;\n" .
        "}\n" .
        "</style>\n" .
        "<div style=\"text-align:right;font-size:small\">" .
        "<span class=\"xlufz\">Xlufz intersection:</span> Source: " .
        "<a href=\"" . $srcurl . "\">" . $srcurl . "</a>: " .
        "Highlighted words are from Datamuse API output for: " .
        "<a href=\"" . $datamuseUrl . "\">" . $datamuseUrl . "</a>" .
        "</div>\n";
      $markedHtml = $markedHtml . $xlufzHeader;
    }
  }
  if ($l > $textStart) {
    $markedHtml = $markedHtml . markPhrasesInText(substr(
      $html, $textStart, $l - $textStart), $phraseMap);
  }
  return $markedHtml;
}

/**
 * Return the contents of url.
 */
function fetch_url($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $output = curl_exec($ch);
  curl_close($ch);
  return $output;
  /* For local testing, if curl is not available: */
  /*
  return file_get_contents($url);
  */
}

/**
 * Fetch the contents of the web page at the URL parameter "srcurl" and
 * return it after highlighting words found in the output of the Datamuse
 * API invoked using all other URL parameters. Only allow the listed
 * URL roots.
 */
function xlufz() {
  $allowedUrlRoots = array(
    "https://cryptics.georgeho.org",
    "https://www.crosswordunclued.com",
    "https://www.highlightpress.com.au",
    "https://en.wikipedia.org",
    "http://sphinx.mythic-beasts.com"
  );

  $datamuseQuery = "";
  $srcurl = "";

  foreach($_GET as $key => $value) {
    if ($key == "srcurl") {
      $srcurl = $value;
      continue;
    }
    $datamuseQuery = $datamuseQuery . "&" . $key . "=" . $value;
  }

  if ($srcurl == "") {
    return "No srcurl parameter passed";
  }
  $rootSlashPos = strpos($srcurl, "/", 8);
  if (!$rootSlashPos) {
    return "srcurl parameter does not have a slash";
  }
  $srcurlRoot = substr($srcurl, 0, $rootSlashPos);
  if (!in_array($srcurlRoot, $allowedUrlRoots)) {
    return "srcurl root [" . $srcurlRoot . "] is not one that's allowed";
  }

  $lastSlashPos = strrpos($srcurl, "/");
  $srcurlBase = substr($srcurl, 0, $lastSlashPos);
  if ($lastSlashPos <= 8) {
    $srcurlBase = $srcurl;
  }

  $origHtml = fetch_url($srcurl);

  /* replace relative urls in img, script, and link tags */
  $repl = "$1=$2" . $srcurlRoot . "/";
  $origHtml = preg_replace("/( href| src)=(.)\//i", $repl, $origHtml);
  $repl = "$1=$2" . $srcurlBase . "/$3";
  $origHtml = preg_replace('/( href| src)=(.)([a-zA-Z][^:\.\/]*\.)/i',
      $repl, $origHtml);

  $datamuseUrl = "https://api.datamuse.com/words?" . $datamuseQuery;
  $phrasesJSON = fetch_url($datamuseUrl);
  $phrases = json_decode($phrasesJSON, true);

  return markPhrasesInHtml($origHtml, $phrases, $datamuseUrl, $srcurl);
}

echo xlufz();
 
?>
