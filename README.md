# Xlufz

## A simple PHP script that highlights selected words in a web page

### Author: Viresh Ratnakar

This is a simple PHP script used by a web service that I host at
[xlufz.ratnakar.org/xlufz.php](http://xlufz.ratnakar.org/xlufz.php) for use
in the [Exet](http://exet.app) crossword construction web app.

The web service allows highlighting words (such as synonyms of something)
on selected web pages. For example:

- Cryptic container indicators that "mean like" <i>glass</i>:
  - [http://xlufz.ratnakar.org/xlufz.php?ml=glass&max=1000&srcurl=https%3A%2F%2Fwww.highlightpress.com.au%2Fcontainers.html](http://xlufz.ratnakar.org/xlufz.php?ml=glass&max=1000&srcurl=https%3A%2F%2Fwww.highlightpress.com.au%2Fcontainers.html)
- All published cryptic indicators related to the topic of <i>climate</i>:
  - [http://xlufz.ratnakar.org/xlufz.php?topics=climate&max=1000&srcurl=https%3A%2F%2Fcryptics.georgeho.org%2Fdata%3Fsql%3Dselect%2B%252A%2Bfrom%2Bindicators_consolidated%26_hide_sql%3D1](http://xlufz.ratnakar.org/xlufz.php?topics=climate&max=1000&srcurl=https%3A%2F%2Fcryptics.georgeho.org%2Fdata%3Fsql%3Dselect%2B%252A%2Bfrom%2Bindicators_consolidated%26_hide_sql%3D1)
- Abbreviations of words that can be described by the adjective <i>blue</i>:
  - [http://xlufz.ratnakar.org/xlufz.php?rel_jja=blue&max=1000&srcurl=https%3A%2F%2Fen.wikipedia.org%2Fwiki%2FCrossword_abbreviations](http://xlufz.ratnakar.org/xlufz.php?rel_jja=blue&max=1000&srcurl=https%3A%2F%2Fen.wikipedia.org%2Fwiki%2FCrossword_abbreviations)
- Anagram indicators that often precede the word <i>drink</i>:
  - [http://xlufz.ratnakar.org/xlufz.php?rel_bgb=drink&max=1000&srcurl=https%3A%2F%2Fwww.crosswordunclued.com%2F2008%2F09%2Fanagram-indicators.html](http://xlufz.ratnakar.org/xlufz.php?rel_bgb=drink&max=1000&srcurl=https%3A%2F%2Fwww.crosswordunclued.com%2F2008%2F09%2Fanagram-indicators.html)


As can be seen from the examples above, you pass the URL of the web page
as a URLEncoded parameter named `srcurl`. All other parameters
that you pass are used for word selection in the Datamuse words API.

Only a small set of web pages can be processed by Xlufz. You an easily
modify the code to change this set and run your own instance of Xlufz.
This set currently consists of the following URL roots:

- `cryptics.georgeho.org`
- `www.crosswordunclued.com`
- `www.highlightpress.com.au`
- `en.wikipedia.org`
- `sphinx.mythic-beasts.com`

Xlufz uses the excellent and versatile
[Datamuse words API](https://www.datamuse.com/api/).
Please refer to its documentation to see how you can modify the word selection
parameters to highlight words that have a certain letter pattern, or rhyme
with something, etc.
