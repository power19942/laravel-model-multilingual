<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

abstract class LocalizableModel extends Model {
  /**
   * Localized attributes
   *
   * @var array
   */
  protected $localizable = [];
  /**
   * Whether or not to eager load translations
   *
   * @var boolean
   */
  protected $eagerLoadTranslations = true;
  /**
   * Whether or not to hide translations
   *
   * @var boolean
   */
  protected $hideTranslations = true;
  /**
   * Whether or not to append translatable attributes to array output
   *
   * @var boolean
   */
  protected $appendLocalizedAttributes = true;
  /**
   * Make a new translatable model
   *
   * @param array $attributes
   */
  public function __construct($attributes = [])
  {
      if ($this->eagerLoadTranslations) {
          $this->with[] = 'translations';
      }
      if($this->hideTranslations) {
          $this->hidden[] = 'translations';
      }
      // We dynamically append localizable attributes to array output
      if ($this->appendLocalizedAttributes) {
          foreach($this->localizable as $localizableAttribute) {
              $this->appends[] = $localizableAttribute;
          }
      }
      parent::__construct($attributes);
  }
  /**
   * This model's translations
   *
   * @return Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function translations()
  {
      $modelName = class_basename(get_class($this));
      return $this->hasMany("App\\{$modelName}Translation");
  }
  /**
   * Magic method for retrieving a missing attribute
   *
   * @param string $attribute
   * @return mixed
   */
  public function __get($attribute)
  {
      // If the attribute is localizable, we retrieve its translation
      // for the current locale
      foreach($this->localizable as $localizableAttribute) {
          if (in_array($attribute, $this->localizable)) {
              return $this->translations
                          ->where('locale', app()->getLocale())
                          ->first()
                          ->{$localizableAttribute};
          }
      }
      return parent::__get($attribute);
  }
  /**
   * Magic method for calling a missing instance method
   *
   * @param string $method
   * @param array $arguments
   * @return mixed
   */
  public function __call($method, $arguments)
  {
      foreach($this->localizable as $localizableAttribute) {
          // e.g. "getNameAttribute"
          if ($method === 'get'.Str::studly($localizableAttribute).'Attribute') {
              return $this->{$localizableAttribute};
          }
      }
      return parent::__call($method, $arguments);
  }
}