<?php


namespace Richard87\ApiRoute\Service;


use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;

class FormatName
{
    private static ?Inflector $inflector = null;
    private static function getInflector(): Inflector {
        if (!static::$inflector) {
            $languageInflectorFactory = InflectorFactory::createForLanguage(Language::ENGLISH);
            static::$inflector = $languageInflectorFactory->build();
        }

        return static::$inflector;
    }

    public static function format(string $name, bool $shouldBePlural = false): string {

        $lowerName = strtolower($name);
        if (str_ends_with($lowerName, "controller")) {
            $name = substr($name, 0, -10);
        }
        if (str_ends_with($lowerName, "handler")) {
            $name = substr($name, 0, -7);
        }
        if (str_starts_with($lowerName, "get")) {
            $name = substr($name, 3);
        }
        if (str_starts_with($lowerName, "is")) {
            $name = substr($name, 2);
        }
        if (str_starts_with($lowerName, "has")) {
            $name = substr($name, 3);
        }
        if (str_starts_with($lowerName, "set")) {
            $name = substr($name, 3);
        }

        $name = strtolower(ltrim(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $name), '-'));

        if ($shouldBePlural) {
            $name = self::getInflector()->pluralize($name);
        } else {
            $name = self::getInflector()->singularize($name);
        }

        return self::getInflector()->urlize($name);
    }
}