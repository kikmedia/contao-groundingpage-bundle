<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Tests;

use Contao\Model;

trait ModelMockTrait
{
    private function mockModel(string $class, array $data): Model
    {
        $model = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        $property = new \ReflectionProperty(Model::class, 'arrData');
        $property->setValue($model, $data);

        return $model;
    }
}
