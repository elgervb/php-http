<?php
namespace http\filter\impl;

use \http\filter\ITextFilter;

class InputFilterXss implements ITextFilter
{

    /**
     * Random Hash for protecting URLs
     *
     * @var string
     * @access protected
     */
    private $xssMd5Hash = '';

    /**
     * List of never allowed strings
     *
     * @var array
     * @access protected
     */
    private $never_allowed_str = array(
        'document.cookie' => '[removed]',
        'document.write' => '[removed]',
        '.parentNode' => '[removed]',
        '.innerHTML' => '[removed]',
        'window.location' => '[removed]',
        '-moz-binding' => '[removed]',
        '<!--' => '&lt;!--',
        '-->' => '--&gt;',
        '<![CDATA[' => '&lt;![CDATA[',
        '<comment>' => '&lt;comment&gt;'
    );

    /**
     * List of never allowed regex replacement
     *
     * @var array
     */
    private $never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)',
        'vbscript\s*:',
        'Redirect\s+302',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );

    /**
     * When filtering out a string, replace it with this string
     *
     * @var string
     */
    private $removeReplacementString = "[removed]";

    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * When filtering out a string, replace it with this string
     *
     * @param unknown_type $aMessage            
     */
    public function setRemoveReplacementString($aMessage)
    {
        $this->removeReplacementString = $aMessage;
    }

    /**
     * Compact Exploded Words
     *
     * Callback function for xss_clean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @param
     *            type
     * @return type
     */
    public function compactExplodedWordsCallback($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @param
     *            array
     * @return string
     */
    public function convertAttributeCallback($aMatch)
    {
        return str_replace(array(
            '>',
            '<',
            '\\'
        ), array(
            '&gt;',
            '&lt;',
            '\\\\'
        ), $aMatch[0]);
    }

    /**
     * Remove Strings that are never allowed
     *
     * A utility function for xss_clean()
     *
     * @param
     *            string
     * @return string
     */
    private function doNeverAllowed($str)
    {
        $str = str_replace(array_keys($this->never_allowed_str), $this->never_allowed_str, $str);
        
        foreach ($this->never_allowed_regex as $regex) {
            $str = preg_replace('#' . $regex . '#is', $this->removeReplacementString, $str);
        }
        
        return $str;
    }

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @param
     *            string
     * @param
     *            string
     * @return string
     */
    public function entityDecode($aStr, $aCharset = 'UTF-8')
    {
        if (stristr($aStr, '&') === FALSE) {
            return $aStr;
        }
        
        $aStr = html_entity_decode($aStr, ENT_COMPAT, $aCharset);
        $aStr = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $aStr);
        return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $aStr);
    }

    /**
     *
     * @param string $aString            
     *
     * @return string
     */
    public function filter($aString)
    {
        return $this->xssClean($aString);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety
     *
     * @param
     *            string
     * @return string
     */
    private function filterAttributes($str)
    {
        $out = '';
        
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }
        
        return $out;
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @param
     *            array
     * @return string
     */
    public function jsImgRemovalCallback($match)
    {
        return str_replace($match[1], preg_replace('#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $this->filterAttributes(str_replace(array(
            '<',
            '>'
        ), '', $match[1]))), $match[0]);
    }

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @param
     *            array
     * @return string
     */
    public function jsLinkRemovalCallback($match)
    {
        return str_replace($match[1], preg_replace('#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si', '', $this->filterAttributes(str_replace(array(
            '<',
            '>'
        ), '', $match[1]))), $match[0]);
    }

    /**
     * Remove all invisible chars
     *
     * @param string $aStr            
     * @param boolean $aUrlEncoded            
     * @return string
     */
    private function removeInvisibleChars($aStr, $aUrlEncoded = true)
    {
        $non_displayables = array();
        $str = $aStr;
        
        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)
        if ($aUrlEncoded) {
            $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        }
        
        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127
        
        do {
            $str = preg_replace($non_displayables, '', $str, - 1, $count);
        } while ($count);
        
        return $str;
    }

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented. This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts. Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission. It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://channel.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @param
     *            mixed	string or array
     * @param
     *            bool
     * @return string
     */
    public function xssClean($aStr, $aIsImage = false)
    {
        // Is the string an array?
        if (is_array($aStr)) {
            while (list ($key) = each($aStr)) {
                $aStr[$key] = $this->xssClean($aStr[$key]);
            }
            
            return $aStr;
        }
        
        // Remove Invisible Characters
        $aStr = $this->removeInvisibleChars($aStr);
        
        // Validate Entities in URLs
        $aStr = $this->validateEntities($aStr);
        
        /*
         * URL Decode Just in case stuff like this is submitted: <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a> Note: Use rawurldecode() so it does not remove plus signs
         */
        $aStr = rawurldecode($aStr);
        
        /*
         * Convert character entities to ASCII This permits our tests below to work reliably. We only convert entities that are within tags since these are the ones that will pose security problems.
         */
        $aStr = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array(
            $this,
            'convertAttributeCallback'
        ), $aStr);
        
        $aStr = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array(
            $this,
            'decodeEntityCallback'
        ), $aStr);
        
        /*
         * Remove Invisible Characters Again!
         */
        $aStr = $this->removeInvisibleChars($aStr);
        
        /*
         * Convert all tabs to spaces This prevents strings like this: ja	vascript NOTE: we deal with spaces between characters later. NOTE: preg_replace was found to be amazingly slow here on large blocks of data, so we use str_replace.
         */
        if (strpos($aStr, "\t") !== false) {
            $aStr = str_replace("\t", ' ', $aStr);
        }
        
        /*
         * Capture converted string for later comparison
         */
        $converted_string = $aStr;
        
        // Remove Strings that are never allowed
        $aStr = $this->doNeverAllowed($aStr);
        
        /*
         * Makes PHP tags safe Note: XML tags are inadvertently replaced too: <?xml But it doesn't seem to pose a problem.
         */
        if ($aIsImage === true) {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $aStr = preg_replace('/<\?(php)/i', "&lt;?\\1", $aStr);
        } else {
            $aStr = str_replace(array(
                '<?',
                '?' . '>'
            ), array(
                '&lt;?',
                '?&gt;'
            ), $aStr);
        }
        
        /*
         * Compact any exploded words This corrects words like: j a v a s c r i p t These words are compacted back to their correct state.
         */
        $words = array(
            'javascript',
            'expression',
            'vbscript',
            'script',
            'base64',
            'applet',
            'alert',
            'document',
            'write',
            'cookie',
            'window'
        );
        
        foreach ($words as $word) {
            $temp = '';
            
            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i ++) {
                $temp .= substr($word, $i, 1) . "\s*";
            }
            
            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $aStr = preg_replace_callback('#(' . substr($temp, 0, - 3) . ')(\W)#is', array(
                $this,
                'compactExplodedWordsCallback'
            ), $aStr);
        }
        
        /*
         * Remove disallowed Javascript in links or img tags We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared to these simplified non-capturing preg_match(), especially if the pattern exists in the string
         */
        do {
            $original = $aStr;
            
            if (preg_match("/<a/i", $aStr)) {
                $aStr = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array(
                    $this,
                    'jsLinkRemovalCallback'
                ), $aStr);
            }
            
            if (preg_match("/<img/i", $aStr)) {
                $aStr = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array(
                    $this,
                    'jsImgRemovalCallback'
                ), $aStr);
            }
            
            if (preg_match("/script/i", $aStr) or preg_match("/xss/i", $aStr)) {
                $aStr = preg_replace("#<(/*)(script|xss)(.*?)\>#si", $this->removeReplacementString, $aStr);
            }
        } while ($original != $aStr);
        
        unset($original);
        
        // Remove evil attributes such as style, onclick and xmlns
        $aStr = $this->removeEvilAttributes($aStr, $aIsImage);
        
        /*
         * Sanitize naughty HTML elements If a tag containing any of the words in the list below is found, the tag gets converted to entities. So this: <blink> Becomes: &lt;blink&gt;
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $aStr = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', array(
            $this,
            'sanitizeNaughtyHtmlCallback'
        ), $aStr);
        
        /*
         * Sanitize naughty scripting elements Similar to above, only instead of looking for tags it looks for PHP and JavaScript commands that are disallowed. Rather than removing the code, it simply converts the parenthesis to entities rendering the code un-executable. For example:	eval('some code') Becomes:		eval&#40;'some code'&#41;
         */
        $aStr = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $aStr);
        
        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $aStr = $this->doNeverAllowed($aStr);
        
        /*
         * Images are Handled in a Special Way - Essentially, we want to know that after all of the character conversion is done whether any unwanted, likely XSS, code was found. If not, we return TRUE, as the image is clean. However, if the string post-conversion does not matched the string post-removal of XSS, then it fails, as there was unwanted XSS code found and removed/changed during processing.
         */
        if ($aIsImage === true) {
            return ($aStr == $converted_string) ? true : false;
        }
        
        return $aStr;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Random Hash for protecting URLs
     *
     * @return string
     */
    private function xssHash()
    {
        if ($this->xssMd5Hash == '') {
            mt_srand();
            $this->xssMd5Hash = md5(time() + mt_rand(0, 1999999999));
        }
        
        return $this->xssMd5Hash;
    }
    
    // --------------------------------------------------------------------
    
    // --------------------------------------------------------------------
    
    /**
     * Filename InputFilterXss
     *
     * @param
     *            string
     * @param
     *            bool
     * @return string
     */
    public function sanitizeFilename($str, $relative_path = FALSE)
    {
        $bad = array(
            "../",
            "<!--",
            "-->",
            "<",
            ">",
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c" /* < */, "%253c" /* < */,  "%3e" /* > */,"%0e" /* > */, "%28"/* ( */, "%29"/* ) */ , "%2528" /* ( */,"%26" /* & */, "%24"/* $ */, "%3f" /* ? */, 	"%3b" /* ; */, "%3d"
        ); /* = */
        
        if (! $relative_path) {
            $bad[] = './';
            $bad[] = '/';
        }
        
        $str = $this->removeInvisibleChars($str, FALSE);
        return stripslashes(str_replace($bad, '', $str));
    }
    
    /*
     * Remove Evil HTML Attributes (like evenhandlers and style) It removes the evil attribute and either: - Everything up until a space For example, everything between the pipes: <a |style=document.write('hello');alert('world');| class=link> - Everything inside the quotes For example, everything between the pipes: <a |style="document.write('hello'); alert('world');"| class="link"> @param string $str The string to check @param boolean $is_image TRUE if this is an image @return string The string with the evil attributes removed
     */
    protected function removeEvilAttributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        $evil_attributes = array(
            'on\w*',
            'style',
            'xmlns',
            'formaction'
        );
        
        if ($is_image === TRUE) {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images, including namespacing, so we have to allow this for images.
             */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
        }
        
        do {
            $count = 0;
            $attribs = array();
            
            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $attr) {
                
                $attribs[] = preg_quote($attr[0], '/');
            }
            
            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all("/(" . implode('|', $evil_attributes) . ")\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is", $str, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }
            
            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0) {
                $str = preg_replace("/<(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(" . implode('|', $attribs) . ")(.*?)([\s><])([><]*)/i", '<$1 $3$5$6$7', $str, - 1, $count);
            }
        } while ($count);
        
        return $str;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @param
     *            array
     * @return string
     */
    public function sanitizeNaughtyHtmlCallback($matches)
    {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];
        
        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array(
            '>',
            '<'
        ), array(
            '&gt;',
            '&lt;'
        ), $matches[4]);
        
        return $str;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @param
     *            array
     * @return string
     */
    private function decodeEntityCallback($aMatch)
    {
        return $this->entityDecode($aMatch[0]);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Validate URL entities
     *
     * Called by xss_clean()
     *
     * @param
     *            string
     * @return string
     */
    private function validateEntities($aStr)
    {
        /*
         * Protect GET variables in URLs
         */
        // 901119URL5918AMP18930PROTECT8198
        $aStr = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xssHash() . "\\1=\\2", $aStr);
        
        /*
         * Validate standard character entities Add a semicolon if missing. We do this to enable the conversion of entities to ASCII later.
         */
        $aStr = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $aStr);
        
        /*
         * Validate UTF16 two byte encoding (x00) Just as above, adds a semicolon if missing.
         */
        $aStr = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $aStr);
        
        /*
         * Un-Protect GET variables in URLs
         */
        $aStr = str_replace($this->xssHash(), '&', $aStr);
        
        return $aStr;
    }
    
    // ----------------------------------------------------------------------
}
