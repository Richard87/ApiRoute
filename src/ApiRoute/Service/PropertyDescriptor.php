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
        $reflType = $reflectionProperty->getType();
        $type = (string)($reflType ?? "mixed");
        $nullable = $reflType?->allowsNull() ?? true;

        return new self($property,$name, $type, $nullable);
    }

    public static function FromMethod(Property $property, \ReflectionMethod $reflectionMethod): PropertyDescriptor
    {
        $name = $property->name ?? $reflectionMethod->name;
        $reflType = $reflectionMethod->getReturnType();
        $type = (string)($reflType ?? "mixed");
        $nullable = $reflType?->allowsNull() ?? true;

        return new self($property, $name, $type,$nullable);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSchema(): array {
        $schema = $this->getType();

        if ($this->isReadOnly()) {
            $schema['readOnly'] = true;
        }
        if($this->isWriteOnly()) {
            $schema['writeOnly'] = true;
        }

        return $schema;
    }

    protected function getType(): array {
        return  OpenApiGenerator::mapComplexType($this->type, $this->nullable);
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