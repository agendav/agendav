<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: NPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Netscape Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/NPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Mozilla Communicator client code.
 *
 * The Initial Developer of the Original Code is
 * Netscape Communications Corporation.
 * Portions created by the Initial Developer are Copyright (C) 1998
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Henri Sivonen, hsivonen@iki.fi
 *
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the NPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the NPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

/*
 * For the original C++ code, see
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUTF8ToUnicode.cpp
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUnicodeToUTF8.cpp
 *
 * The latest version of this file can be obtained from
 * http://iki.fi/hsivonen/php-utf8/
 *
 * Version 1.0, 2003-05-30
 */

/**
 * Takes an UTF-8 string and returns an array of ints representing the
 * Unicode characters. Astral planes are supported ie. the ints in the
 * output can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
 * are not allowed.
 *
 * Returns false if the input string isn't a valid UTF-8 octet sequence.
 */
function utf8ToUnicode(&$str)
{
  $mState = 0;     // cached expected number of octets after the current octet
                   // until the beginning of the next UTF8 character sequence
  $mUcs4  = 0;     // cached Unicode character
  $mBytes = 1;     // cached expected number of octets in the current sequence

  $out = array();

  $len = strlen($str);
  for($i = 0; $i < $len; $i++) {
    $in = ord($str{$i});
    if (0 == $mState) {
      // When mState is zero we expect either a US-ASCII character or a
      // multi-octet sequence.
      if (0 == (0x80 & ($in))) {
        // US-ASCII, pass straight through.
        $out[] = $in;
        $mBytes = 1;
      } else if (0xC0 == (0xE0 & ($in))) {
        // First octet of 2 octet sequence
        $mUcs4 = ($in);
        $mUcs4 = ($mUcs4 & 0x1F) << 6;
        $mState = 1;
        $mBytes = 2;
      } else if (0xE0 == (0xF0 & ($in))) {
        // First octet of 3 octet sequence
        $mUcs4 = ($in);
        $mUcs4 = ($mUcs4 & 0x0F) << 12;
        $mState = 2;
        $mBytes = 3;
      } else if (0xF0 == (0xF8 & ($in))) {
        // First octet of 4 octet sequence
        $mUcs4 = ($in);
        $mUcs4 = ($mUcs4 & 0x07) << 18;
        $mState = 3;
        $mBytes = 4;
      } else if (0xF8 == (0xFC & ($in))) {
        /* First octet of 5 octet sequence.
         *
         * This is illegal because the encoded codepoint must be either
         * (a) not the shortest form or
         * (b) outside the Unicode range of 0-0x10FFFF.
         * Rather than trying to resynchronize, we will carry on until the end
         * of the sequence and let the later error handling code catch it.
         */
        $mUcs4 = ($in);
        $mUcs4 = ($mUcs4 & 0x03) << 24;
        $mState = 4;
        $mBytes = 5;
      } else if (0xFC == (0xFE & ($in))) {
        // First octet of 6 octet sequence, see comments for 5 octet sequence.
        $mUcs4 = ($in);
        $mUcs4 = ($mUcs4 & 1) << 30;
        $mState = 5;
        $mBytes = 6;
      } else {
        /* Current octet is neither in the US-ASCII range nor a legal first
         * octet of a multi-octet sequence.
         */
        return false;
      }
    } else {
      // When mState is non-zero, we expect a continuation of the multi-octet
      // sequence
      if (0x80 == (0xC0 & ($in))) {
        // Legal continuation.
        $shift = ($mState - 1) * 6;
        $tmp = $in;
        $tmp = ($tmp & 0x0000003F) << $shift;
        $mUcs4 |= $tmp;

        if (0 == --$mState) {
          /* End of the multi-octet sequence. mUcs4 now contains the final
           * Unicode codepoint to be output
           *
           * Check for illegal sequences and codepoints.
           */

          // From Unicode 3.1, non-shortest form is illegal
          if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
              ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
              ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
              (4 < $mBytes) ||
              // From Unicode 3.2, surrogate characters are illegal
              (($mUcs4 & 0xFFFFF800) == 0xD800) ||
              // Codepoints outside the Unicode range are illegal
              ($mUcs4 > 0x10FFFF)) {
            return false;
          }
          if (0xFEFF != $mUcs4) {
            // BOM is legal but we don't want to output it
            $out[] = $mUcs4;
          }
          //initialize UTF8 cache
          $mState = 0;
          $mUcs4  = 0;
          $mBytes = 1;
        }
      } else {
        /* ((0xC0 & (*in) != 0x80) && (mState != 0))
         *
         * Incomplete multi-octet sequence.
         */
        return false;
      }
    }
  }
  return $out;
}

/**
 * Takes an array of ints representing the Unicode characters and returns
 * a UTF-8 string. Astral planes are supported ie. the ints in the
 * input can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
 * are not allowed.
 *
 * Returns false if the input array contains ints that represent
 * surrogates or are outside the Unicode range.
 */
function unicodeToUtf8(&$arr)
{
  $dest = '';
  foreach ($arr as $src) {
    if($src < 0) {
      return false;
    } else if ( $src <= 0x007f) {
      $dest .= chr($src);
    } else if ($src <= 0x07ff) {
      $dest .= chr(0xc0 | ($src >> 6));
      $dest .= chr(0x80 | ($src & 0x003f));
    } else if($src == 0xFEFF) {
      // nop -- zap the BOM
    } else if ($src >= 0xD800 && $src <= 0xDFFF) {
      // found a surrogate
      return false;
    } else if ($src <= 0xffff) {
      $dest .= chr(0xe0 | ($src >> 12));
      $dest .= chr(0x80 | (($src >> 6) & 0x003f));
      $dest .= chr(0x80 | ($src & 0x003f));
    } else if ($src <= 0x10ffff) {
      $dest .= chr(0xf0 | ($src >> 18));
      $dest .= chr(0x80 | (($src >> 12) & 0x3f));
      $dest .= chr(0x80 | (($src >> 6) & 0x3f));
      $dest .= chr(0x80 | ($src & 0x3f));
    } else {
      // out of range
      return false;
    }
  }
  return $dest;
}
function check_string($ics){
    $ics_file = explode("\n",$ics);
    foreach($ics_file as $line => $str){
        if(false === utf8ToUnicode($str)){
            $error[] = $line;
        }
    }
    if(isset($error) && is_array($error)){
        foreach($error as $line){
            dbg_error_log( "LOG check_string","error on lines %  invalid character in string %s" , ($line +1),$ics_file[$line]  );
            return false;
        }
    } else {
        dbg_error_log( "LOG check_string","the string is UTF8 compliant");
        return true;
    }
}
?>