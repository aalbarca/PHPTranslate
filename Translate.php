<?php

/*
 * Copyright (C) 2012 Alejandro Albarca Martínez <albarcam [arroba] gmail.com> and NETFLIE. (http://www.netflie.es)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program, in the file called "COPYING".  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @name       Translate
 * @package    MeteoLive
 * @copyright  Copyright (c) 2012 NETFLIE. (http://meteolive.netflie.es)
 * @license    http://www.gnu.org/licenses/     GNU GPLv3
 * @author     Alejandro Albarca Martinez
 */
class Translate {
  
  /**
   * Scan for the locale within the name of the directory
   * @constant string
   */
  const LANGUAGE_PATH = 'languages';
  
  /**
   * Automatic language search
   * @var boolean
   */
  private $_automatic = true;
  
  /**
   * Locale buffer
   * @var string
   */
  private $_bufLocale = null;

  /**
   * Content to translate
   * @var array
   */
  private $_content = null;

  /**
   * The actual set locale use
   * @var string
   */
  private $_locale = 'auto';

  /**
   * Translation table
   * @var array
   */
  private $_translate = array();

  /**
   * Generates a translate instance.
   * 
   * @param array $options Translation to be added
   * @return void
   */
  public function __construct($options = array()) {
    
    $this->addTranslation($options);

    if (is_array($options)) {
      if (array_key_exists('automatic', $options)) {
        $this->setAutomatic($options['automatic']);
      }
      
      if (array_key_exists('locale', $options)) {
        $this->setLocale($options['locale']);
      } else {
        $this->setLocale();
      }
    } else {
      $this->setLocale();
    }
    
  }

  /**
   * Add translation in to the translations table
   * 
   * If the language exists then translations for the specified language will be replaced
   * and added otherwise
   * 
   * @param array $options Translation to be added
   * @return boolean True if the language was added, or false on failure
   */
  public function addTranslation($options = array()) {

    if (is_array($options)) {
      if (array_key_exists('content', $options) && array_key_exists('locale', $options)) {
        $locale = strtolower(trim($options['locale']));

        $buf = array($locale => $options['content']);
        $this->_translate = array_merge($this->_translate, $buf);
        return true;
      }
    }

    return false;
  }

  /**
   * Returns the available languages from this instance
   * 
   * @return array|null
   */
  public function getList() {

    $list = array_keys($this->_translate);
    $result = null;
    foreach ($list as $value) {
      if (!empty($this->_translate[$value])) {
        $result[$value] = $value;
      }
    }

    return $result;
  }
  
  /**
   * Get Locale
   * 
   * @return array
   */
  public function getLocale() {
    return $this->_locale;
  }

  /**
   * Is the wished language available ?
   * 
   * @param string Language to search for, identical with locale identifier.
   * @return boolean
   */
  public function isAvailable($locale) {

    $locale = strtolower(trim($locale));
    $return = true;

    if (!isset($this->_translate[(string) $locale])) {
      $file = realpath(self::LANGUAGE_PATH . '/' . $locale . '.php');
      $return = file_exists($file);
    }

    return $return;
  }
  
  /**
   * Sets automatic
   * 
   * @param boolean Automatic value to set 
   * @return void
   */
  public function setAutomatic($automatic) {
    
    if (is_bool($automatic)) {
      $this->_automatic = $automatic;
      $this->setLocale();
    }
  }

  /**
   * Sets locale
   * 
   * @param array $locale Locale to set
   * @return boolean True on success, or false on failure
   */
  public function setLocale($locale = 'auto') {
    
    static $automaticMode = false;
    $locale = strtolower(trim($locale));
    
    if (!$automaticMode && $locale != 'auto')
      $this->_bufLocale = $locale;
    
    if (!$this->_automatic && $this->_bufLocale !== null && $locale == 'auto')
      $this->setLocale($this->_bufLocale);
    
    if ((!$this->_automatic || $automaticMode) && $locale != 'auto' && $this->isAvailable($locale)) {
      $this->_locale = $locale;
      $this->_content = $this->_loadTranslationData($locale);
      return true;
    }

    if($automaticMode) return false;

    if ($this->_automatic) {
      $languages = $this->_getBrowserLanguages();
      
      foreach ($languages as $lang) {
        $automaticMode = true;
        if ($this->setLocale($lang)) {
          $automaticMode = false;
          return true;
        }
      }
      
      // If the browser lang not exists try with $locale language
      $this->setLocale($locale);
      
      $automaticMode = false;
    }

    return false;
  }

  /**
   * Translates the given string, returns the translation
   * 
   * @param string $message Translation string
   * @return string
   */
  public function translate($message = '') {
    if (is_string($message)) {
      $data = $this->_content;

      if (is_array($data)) {
        if ((array_key_exists($message, $data))) {
          return $data[$message];
        }
      }
    }

    return $message;
  }

  /**
   * Translates the given string, returns the translation
   * 
   * @param string $message Translation string
   * @return string
   */
  public function _($message = '') {
    return $this->translate($message);
  }

  /**
   * Get the user browser languages
   * This is used to set the language in case locale = 'automatic'
   * 
   * @return array
   */
  private function _getBrowserLanguages() {
    
    // check if environment variable HTTP_ACCEPT_LANGUAGE exists
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      return array();
    }

    $browserLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

    // convert the headers string to an array
    $browserLanguagesSize = sizeof($browserLanguages);
    for ($i = 0; $i < $browserLanguagesSize; $i++) {
      # explode string at ;
      $browserLanguage = explode(';', $browserLanguages[$i]);
      # cut string and place into array
      $browserLanguages[$i] = substr($browserLanguage[0], 0, 2);
      if ($browserLanguages[$i] === 'auto')
        unset($browserLanguages[$i]);
    }

    return array_values(array_unique($browserLanguages));
  }

  /**
   * Internal function for adding translation data
   * 
   * @param string $locale Language of the data will be added
   * @return array|null Array with the language content or null
   */
  private function _loadTranslationData($locale) {
    $this->_content = null;

    if (isset($this->_translate[$locale])) {
      $data = $this->_translate[$locale];
    } else {
      $data = self::LANGUAGE_PATH . '/' . $locale . '.php';
    }

    if (!is_array($data)) {
      $data = realpath($data);
      if (file_exists($data)) {
        ob_start();
        $data = require($data);
        ob_end_clean();
      }
    }

    if (!is_array($data)) {
      throw new Exception("Error including array or file '" . $data . "'");
    }

    $this->_content = $data;

    return $this->_content;
  }

}
