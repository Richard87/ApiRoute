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
            "type" => $this->getType(),
        ];

        if ($this->isReadOnly()) {
            $schema['readOnly'] = true;
        }
        if($this->isWriteOnly()) {
            $schema['writeOnly'] = true;
        }

        return $schema;
    }

    protected function getType(): array|string {
        // Todo: If target is custom ojbect, create Ref / else create schema
        return $this->type;
    }
    private function generateSchema(array $schema, string $class, bool $void, bool $nullable, array &$refs): array
    {
        if (str_starts_with($class, "?")) {
            $nullable = true;
            $class    = substr($class, 1);
        }

        if ($class === "null" || $class === "void" || $class === "false") {
            $void = true;
        }

        if ($void) {
            $schema["responses"]["200"] = ['description' => 'Ok'];
        } elseif ($class === \DateTime::class) {
            $schema["responses"]["200"] = $this->createResponseSchema("date-time", "1985-04-12T23:20:50.52Z", $nullable);
        } elseif ($class === "string") {
            $schema["responses"]["200"] = $this->createResponseSchema("string", "Some text", $nullable);
        } elseif ($class === "bool") {
            $schema["responses"]["200"] = $this->createResponseSchema("boolean", "true", $nullable);
        } elseif ($class === "float") {
            $schema["responses"]["200"] = $this->createResponseSchema("number", "5.0", $nullable);
        } elseif ($class === "int") {
            $schema["responses"]["200"] = $this->createResponseSchema("number", "5", $nullable);
        } elseif ($class === "object") {
            $schema["responses"]["200"] = $this->createResponseSchema("string", "", $nullable);
        } elseif ($class === "mixed") {
            $schema["responses"]["200"] = $this->createResponseSchema("string", "", $nullable);
        } elseif ($class === "void") {
            $schema["responses"]["201"] = $this->createResponseSchema("array", "", $nullable);
        } elseif ($class === "array") {
            $schema["responses"]["200"] = $this->createResponseSchema("array", "", $nullable);
        } else {
            $ref                        = OpenApiGenerator::ConvertClassToRef($class);
            $schema["responses"]["200"] = ['description' => 'Resource', "content" => ['application/json' => ['schema' => ['$ref' => $ref]]]];
            $refs[$ref]                 = $class;
        }
        return $schema;
    }
    protected function createResponseSchema(string $type, string $example, bool $nullable = false): array
    {
        $schema = [
            "200" => [
                'description' => 'Results',
                "content"     => [
                    'application/json' => [
                        'schema' => [
                            "type"    => $type,
                            "example" => $example,
                        ],
                    ],
                ],
            ],
        ];

        if ($nullable) {
            $schema["202"] = [
                'description' => 'Accepted',
            ];
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