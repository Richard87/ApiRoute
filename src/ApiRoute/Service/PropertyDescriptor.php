<?php


namespace Richard87\ApiRoute\Service;


use Richard87\ApiRoute\Attributes\Property;

class PropertyDescriptor
{
    public function __construct(
        public Property $property,
        public string $name,
        public string $type,
        public bool $nullable
    ){
    }

    public static function FromProperty(Property $property, \ReflectionProperty $reflectionProperty): PropertyDescriptor
    {
        $name = $property->name ?? $reflectionProperty->name;
        $type = "null";
        $nullable = true;

        if (($reflType = $reflectionProperty->getType()) && $reflType instanceof \ReflectionNamedType) {
            $nullable = $reflType->allowsNull();
            $type = $reflType->getName();

            // TODO:
            // - If target object is a Resource, load IRI, if not load properties
            // - Load Docblock for more descriptive type
        }

        return new self($property,$name, $type, $nullable);
    }

    public static function FromMethod(Property $property, \ReflectionMethod $reflectionMethod): PropertyDescriptor
    {
        $name = $property->name ?? $reflectionMethod->name;
        $type = "null";
        $nullable = true;
        if (($reflType = $reflectionMethod->getReturnType()) && $reflType instanceof \ReflectionNamedType) {
            $nullable = $reflType->allowsNull();
            $type = $reflType->getName();

            // TODO:
            // - If target object is a Resource, load IRI, if not load properties
            // - Load Docblock for more descriptive type
        }

        return new self($property, $name, $type,$nullable);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSchema(): array {
        $schema = [
            "nullable" => $this->nullable,
            "type" => $this->type,
        ];

        if ($this->isReadOnly()) {
            $schema['readOnly'] = true;
        }
        if($this->isWriteOnly()) {
            $schema['writeOnly'] = true;
        }

        return $schema;
    }

    protected function isReadOnly(): bool
    {
        return $this->property->read !== false && !$this->property->write;
    }

    private function isWriteOnly(): bool
    {
        return $this->property->write !== false && !$this->property->read;
    }
}