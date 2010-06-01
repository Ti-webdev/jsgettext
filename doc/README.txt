=Overview=

A PHP script which extracts strings from Javascript code and merges with an existing .po file. Then you can translate strings using Poedit.

Includes a small script that converts .po files into JSON so you can include it as an external file in your web application and invoke a function that translates.

The script is smart enough to extract strings excluding regular expressions and comments.

=Usage=

Open Poedit, go to preferences and add a new parser. The command must point to the script "jsgettext.php". Eg: `php /home/www/projects/jsgettext/jsgettext.php -o %o -k "%K" %F`

Create a new catalog and set the base folder which contains the Javascript files you want to parse. Proceed to update from source, translate and save file.

Exec the following command (check the command location and update path):
{{{
/*
Options:
-i input file name
-o output file name
-k javascript global var name which holds the object with the translations.
*/
php /home/www/projects/jsgettext/po2json.php -i catalog.po -o l10n.js -k l10n
}}}

From JS:
{{{
function _(s) {
    return typeof l10n[s] != 'undefined' ? l10n[s] : s;
}
alert(_("Hello world"))
}}}

=Example source file=
see test.js
