<?php

namespace App\Toriom;

use App\Translation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Translatable
{
  public function allTranslations(): Collection
  {
    $translations = collect([]);

    $attributes = $this->getAttributes();

    $locales = $this->translations()->get()->groupBy('locale')->keys();

    foreach ($locales as $locale) {
      $translation = collect([]);

      foreach ($attributes as $attribute => $value) {
        if ($this->isTranslatableAttribute($attribute) && $this->hasTranslation($locale, $attribute)) {
          $translation->put($attribute, $this->getTranslation($attribute, $locale));
        } else {
          $translation->put($attribute, parent::getAttributeValue($attribute));
        }
      }

      $translations->put($locale, $translation);
    }

    return $translations;
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  public function getAttributeValue($key)
  {
    if (!$this->isTranslatableAttribute($key) || config('app.locale') == App::getLocale()) {
      return parent::getAttributeValue($key);
    }

    return $this->getTranslation($key, App::getLocale());
  }

  public function getTranslatableAttributes(): array
  {
    /* @noinspection PhpUndefinedFieldInspection */
    return (property_exists(static::class, 'translatable') && is_array($this->translatable))
      ? $translatableAttributes = $this->translatable
      : [];
  }

  /**
   * @return Translatable
   */
  public function in(string $locale)
  {
    $translatedModel = new self();

    foreach ($this->getAttributes() as $attribute => $value) {
      if ($this->isTranslatableAttribute($attribute)) {
        if ($this->hasTranslation($locale, $attribute)) {
          $translatedModel->setAttribute($attribute, $this->getTranslation($attribute, $locale));
        } else {
          $translatedModel->setAttribute($attribute, $this->getAttribute($attribute));
        }
      } else {
        $translatedModel->setAttribute($attribute, $this->getAttribute($attribute));
      }
    }
    if ($this->relations) {
      $translatedModel->relations = $this->relations;
    }
    return $translatedModel;
  }

  public static function translate($items)
  {
    for ($i = 0; $i < count($items); $i++) {
      $items[$i] = $items[$i]->in(App::getLocale());
    }
    return $items;
  }

  public function removeTranslationIn(string $locale)
  {
    $this->translations()
      ->where('locale', $locale)
      ->delete();
  }

  public function removeTranslations()
  {
    $this->translations()->delete();
  }

  public function removeTranslation(string $locale, string $attribute)
  {
    $this->translations()
      ->where('locale', $locale)
      ->where('key', $attribute)
      ->delete();
  }

  /**
   * @return mixed
   */
  public function translations()
  {
    return $this->morphMany(Translation::class, 'translatable')
      ->whereIn('locale', array_keys(config('multilang.locales', [])));
  }

  /**
   * returns the translation of a key for a given key/locale pair.
   *
   * @return mixed
   */
  protected function getTranslation(string $key, string $locale)
  {
    return $this->translations()
      ->where('key', $key)
      ->where('locale', $locale)
      ->value('value');
  }

  protected function hasTranslation(string $locale, string $attribute): bool
  {
    $translation = $this->translations()
      ->where('locale', $locale)
      ->where('key', $attribute)
      ->first();

    return $translation !== null;
  }

  protected function isTranslatableAttribute(string $key): bool
  {
    return in_array($key, $this->getTranslatableAttributes());
  }

  protected function setTranslation(string $locale, string $attribute, string $translation): void
  {
    $this->translations()->create([
      'translatable_id' => $this->id,
      'translatable_type' => get_class($this),
      'key'    => $attribute,
      'value'  => $translation,
      'locale' => $locale,
    ]);
  }

  protected function setTranslations(string $locale, array $attributes): void
  {
    $translations = [];
    foreach ($attributes as $key => $value) {
      if ($this->isTranslatableAttribute($key)) {
        if ($this->hasTranslation($locale, $key)) {
          $this->translations()->where([
            ['key', '=', $key],
            ['locale', '=', $locale],
          ])->update([
            'value' => $value,
          ]);
        } else {
          $translations[] = [
            'translatable_id' => $this->id,
            'translatable_type' => get_class($this),
            'key'    => $key,
            'value'  => $value,
            'locale' => $locale,
          ];
        }
      } else {
        dd('no 1');
      }
    }

    $this->translations()->insert($translations);
  }

  protected function setTranslationByArray(string $locale, array $translations): void
  {
    foreach ($translations as $attribute => $translation) {
      if ($this->isTranslatableAttribute($attribute)) {
        $storedTranslation = $this->translations()
          ->where('locale', $locale)
          ->where('key', $attribute)
          ->first();

        if ($storedTranslation) {
          $this->updateTranslation($locale, $attribute, $translation);
        } else {
          $this->setTranslation($locale, $attribute, $translation);
        }
      }
    }
  }

  /**
   * @return mixed
   */
  protected function translateAttribute(string $key, string $locale)
  {
    if (!$this->isTranslatableAttribute($key) || config('app.fallback_locale') == $locale) {
      return parent::getAttributeValue($key);
    }

    return $this->getTranslation($key, $locale);
  }

  protected function updateTranslation(string $locale, string $attribute, string $translation): void
  {
    $this->translations()
      ->where('key', $attribute)
      ->where('locale', $locale)
      ->update([
        'value' => $translation,
      ]);
  }

  public function scopeWithTranslation(Builder $query, $locale = null, $fallback = true)
  {
    if (is_null($locale)) {
      $locale = app()->getLocale();
    }
    if ($fallback === true) {
      $fallback = config('app.fallback_locale', 'en');
    }
    $query->with(['translations' => function (Relation $query) use ($locale, $fallback) {
      $query->where(function ($q) use ($locale, $fallback) {
        $q->where('locale', $locale);
        if ($fallback !== false) {
          $q->orWhere('locale', $fallback);
        }
      });
    }]);
  }
  /**
   * This scope eager loads the translations for the default and the fallback locale only.
   * We can use this as a shortcut to improve performance in our application.
   *
   * @param Builder           $query
   * @param string|null|array $locales
   * @param string|bool       $fallback
   */
  public function scopeWithTranslations(Builder $query, $locales = null, $fallback = true)
  {
    if (is_null($locales)) {
      $locales = app()->getLocale();
    }
    if ($fallback === true) {
      $fallback = config('app.fallback_locale', 'en');
    }
    $query->with(['translations' => function (Relation $query) use ($locales, $fallback) {
      if (is_null($locales)) {
        return;
      }
      $query->where(function ($q) use ($locales, $fallback) {
        if (is_array($locales)) {
          $q->whereIn('locale', $locales);
        } else {
          $q->where('locale', $locales);
        }
        if ($fallback !== false) {
          $q->orWhere('locale', $fallback);
        }
      });
    }]);
  }

  public function fillTranslation($attributes)
  {
    foreach ($attributes as $locale => $values) {
      if ($this->isKeyALocale($locale)) {
        $this->setTranslations($locale, $values);
      } else {
        dd('no');
      }
    }
  }

  protected function isKeyALocale(string $key): bool
  {
    return in_array($key, array_keys(config('multilang.locales')));
  }
}
